<?php

require_once VENDOR_DIR . "/autoload.php";
require_once INCLUDES_DIR . "/utilities/util.php";

function init_process($filePath)
{
    ErrorList::clear();

    $studentsExcel = [];
    $studentsDB = [];
    $missingStudents = [];
    $outputFile = null;

    try {
        $studentsDB = getStudents();
        $studentsExcel = processExcel($filePath);
        $missingStudents = filterMissingStudents($studentsExcel, $studentsDB);
        $programCount = getProgramCount($studentsDB, $missingStudents);
        $outputFile = createExcel($missingStudents, $programCount);
        $studentsArray = array_map(fn ($student) => $student->getJSON(), $missingStudents);
        _updateInDB($studentsExcel, $missingStudents);

        return [
            'students' => $studentsArray,
            'excel' => filePathToUrl($outputFile),
            'totalDB' => count($studentsDB),
            'totalFiltered' => count($studentsArray),
            'graphData' => getGraphData($missingStudents)
        ];
    } catch (RuntimeException $e) {
        throw new RuntimeException($e->getMessage());
    }
}

function processExcel($filePath)
{
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);

    //^ Verificar estructura por medio de columnas
    $headerMap = [];
    $headerRow = $spreadsheet->getActiveSheet()->getRowIterator(1, 1)->current();
    foreach ($headerRow->getCellIterator() as $cell) {
        $headerMap[strtolower(trim($cell->getValue()))] = $cell->getColumn();
    }

    if (!_validateExcel($headerMap)) {
        throw new RuntimeException('Archivo con estructura invalida.');
    }

    //^ Obtener datos y pasar a clase
    $claveColumn = $headerMap["clave ulsa (sin al, sólo las 6 cifras)"];
    $apellidoPColumn = $headerMap["apellido paterno"];
    $apellidoMColumn = $headerMap["apellido materno"];
    $nombreColumn = $headerMap["nombre(s)"];
    $tipoColumn = $headerMap["tipo de programa (especialidad o maestría)"];
    $areaColumn = $headerMap["Área de programa (especialidad en ó maestría en)"];

    $students = [];
    $dataRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
    array_shift($dataRows);
    foreach ($dataRows as $row) {
        try {
            $students[] = new Student(
                strtolower(trim($row[$nombreColumn])),
                strtolower(trim($row[$apellidoMColumn]) . ' ' . trim($row[$apellidoPColumn])),
                $row[$claveColumn],
                strtolower($row[$tipoColumn] . ' ' . $row[$areaColumn]),
                "",
            );
        } catch (InvalidArgumentException $e) {
            ErrorList::add($e->getMessage());
            continue;
        }
    }

    return $students;
}

function _validateExcel($headerMap)
{
    $requiredHeaders = [
        "clave ulsa (sin al, sólo las 6 cifras)",
        "apellido paterno",
        "apellido materno",
        "nombre(s)",
        "tipo de programa (especialidad o maestría)",
        "Área de programa (especialidad en ó maestría en)"
    ];

    foreach ($requiredHeaders as $header) {
        if (!isset($headerMap[$header])) {
            throw new RuntimeException("Columna requerida no encontrada: $header");
        }
    }
    return true;
}

function getProgramCount($db, $filtered)
{
    $totalCounts = [];
    $filteredCounts = [];

    foreach ($db as $student) {
        $program = $student->getProgram();
        $totalCounts[$program] = ($totalCounts[$program] ?? 0) + 1;
    }

    foreach ($filtered as $student) {
        $program = $student->getProgram();
        $filteredCounts[$program] = ($filteredCounts[$program] ?? 0) + 1;
    }

    $allPrograms = array_unique(array_merge(array_keys($totalCounts), array_keys($filteredCounts)));
    sort($allPrograms);

    return [
        'total' => $totalCounts,
        'filtered' => $filteredCounts,
        'programs' => $allPrograms
    ];
}

function filterMissingStudents($studentsExcel, $studentsDB)
{
    $excelById = [];
    $excelByName = [];
    foreach ($studentsExcel as $excelStudent) {
        if ($excelStudent->getUlsaId() !== null) {
            $excelById[$excelStudent->getUlsaId()] = true;
        } else {
            $nameKey = implode('|', [
                $excelStudent->getLastName(),
                $excelStudent->getName()
            ]);
            $excelByName[$nameKey] = true;
        }
    }

    $filteredStudents = [];
    foreach ($studentsDB as $student) {
        $ulsaId = $student->getUlsaId();
        $existsInExcel = isset($excelById[$ulsaId]);

        if (!$existsInExcel) {
            $nameKey = implode('|', [
                $student->getLastName(),
                $student->getName()
            ]);
            $existsInExcel = isset($excelByName[$nameKey]);
        }

        if (!$existsInExcel) {
            $filteredStudents[] = $student;
        }
    }

    return $filteredStudents;
}

function createExcel($students, $programCount)
{
    $newSpreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet1 = $newSpreadsheet->getActiveSheet();
    $headers = [
        "Apellidos",
        "Nombre(s)",
        "Clave Ulsa",
        "Programa",
        "Correo institucional"
    ];

    //* Format
    $sheet1->setTitle('Estudiantes sin confirmar AFI');
    foreach (range('A', 'E') as $index => $col) {
        $cell = "{$col}1";
        $sheet1->setCellValue($cell, $headers[$index]);
        $sheet1->getStyle($cell)->getFont()->setBold(true);
    }

    $sheet1->fromArray(
        [$headers],
        null,
        'A1'
    );
    $dataRows = [];
    foreach ($students as $student) {
        $dataRows[] = [
            $student->getLastName(),
            $student->getName(),
            $student->getUlsaId(),
            $student->getProgram(),
            $student->getEmail()
        ];
    }
    $sheet1->fromArray($dataRows, null, 'A2');

    //* Format
    foreach (range('A', 'E') as $col) {
        $sheet1->getColumnDimension($col)->setAutoSize(true);
    }


    $sheet2 = $newSpreadsheet->createSheet();
    $sheet2->setTitle('Conteos por Programa');
    $sheet2->setCellValue('A1', 'Programa');
    $sheet2->setCellValue('B1', 'Conteo Parcial');
    $sheet2->setCellValue('C1', 'Conteo Total');
    $sheet2->setCellValue('D1', 'Porcentaje');

    //* Format
    $sheet2->getStyle('A1:D1')->getFont()->setBold(true);

    $rowIndex = 2;
    foreach ($programCount['programs'] as $program) {

        $sheet2->setCellValue("A{$rowIndex}", $program);

        $partial = $programCount['filtered'][$program] ?? 0;
        $total = $programCount['total'][$program] ?? 0;
        $percentage = ($total > 0) ? ($partial / $total) * 100 : 0;

        $sheet2->setCellValue("B{$rowIndex}", $partial);
        $sheet2->setCellValue("C{$rowIndex}", $total);
        $sheet2->setCellValue("D{$rowIndex}", round($percentage, 2) . '%');

        $rowIndex++;
    }

    //* Format
    for ($i = 1; $i <= 4; $i++) {
        $sheet2->getColumnDimensionByColumn($i)->setAutoSize(true);
    }

    //* Add Graphs
    $sheet3 = $newSpreadsheet->createSheet();
    $sheet3->setTitle('Gráficas');
    $sheet2->setCellValue('A1', 'Gráficas de ');

    //* Save File
    if (!file_exists(XLSX_DIR)) {
        mkdir(XLSX_DIR, 0777, true);
    }

    $timestamp = date('Y-m-d_H-i-s');
    $outputFile = XLSX_DIR . "/filtered_students_{$timestamp}.xlsx";
    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($newSpreadsheet);
    $writer->save($outputFile);

    return $outputFile;
}

function getGraphData($filteredStudents)
{
    $data = [
        'maestria' => [],
        'especialidad' => []
    ];

    foreach ($filteredStudents as $student) {
        $carrer = strtolower($student->getProgram());

        if (str_starts_with($carrer, 'maestría')) {
            $data['maestria'][$carrer] = ($data['maestria'][$carrer] ?? 0) + 1;
        } elseif (str_starts_with($carrer, 'especialidad')) {
            $data['especialidad'][$carrer] = ($data['especialidad'][$carrer] ?? 0) + 1;
        }
    }

    return $data;
}

function _updateInDB($studentsConfirm, $studentsNotConfirm)
{
    foreach ($studentsNotConfirm as $student) {
        updateStudentFieldBoolean($student->getUlsaId(), 'afi', false);
    }

    foreach ($studentsConfirm as $student) {
        updateStudentFieldBoolean($student->getUlsaId(), 'afi', true);
    }
}
