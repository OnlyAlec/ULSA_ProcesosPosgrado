<?php

//! FIXME: Use new structure

require_once VENDOR_DIR . "/autoload.php";
require_once INCLUDES_DIR . "/utilities/util.php";
require_once INCLUDES_DIR . "/utilities/handleErrors.php";
require_once INCLUDES_DIR . "/models/student.php";

function process_multiple_excels($uploadDir, $filePath1, $filePath2)
{
    ErrorList::clear();

    $outputFile = null;
    $studentsAll = [];
    $studentsForms = [];
    $urloutputFile = null;
    $filteredStudents = [];

    try {
        $studentsForms = processExcel("$uploadDir$filePath1");
        $studentsAll = processAlumniExcel("$uploadDir$filePath2");
        $filteredStudents = compareStudents($studentsForms, $studentsAll);
        $programCount = getProgramCount($studentsAll, $filteredStudents);
        $outputFile = createExcel($filteredStudents, $programCount);

        $urloutputFile = filePathToUrl($outputFile);
        $studentsArray = array_map(fn ($student) => [
            'firstName' => $student->getName(),
            'maternalSurname' => $student->getApm(),
            'paternalSurname' => $student->getApp(),
            'ulsaID' => $student->getUlsaId(),
            'typeDesc' => $student->getTypeDesc(),
            'area' => $student->getArea(),
            'email' => $student->getEmail()
        ], $filteredStudents);
        return [
            'students' => $studentsArray,
            'excel' => $urloutputFile,
            'totalDB' => count($studentsAll),
            'totalFiltered' => count($studentsArray)
        ];
    } catch (RuntimeException $e) {
        throw new RuntimeException($e->getMessage());
    }
}

function processAlumniExcel($filePath)
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

    if (!_validateAlumniExcel($headerMap)) {
        throw new RuntimeException('Archivo con estructura invalida.');
    }

    //^ Obtener datos y pasar a clase
    $tipoColumn = $headerMap["tipo de programa"];
    $areaColumn = $headerMap["Área de programa"];
    $claveColumn = $headerMap["clave ulsa"];
    $nombreColumn = $headerMap["nombre"];
    $apellidoPColumn = $headerMap["apellido paterno"];
    $apellidoMColumn = $headerMap["apellido materno"];
    $emailColumn = $headerMap["correo"];

    $students = [];
    $dataRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
    array_shift($dataRows);
    foreach ($dataRows as $row) {
        try {
            $student = new Student(
                strtolower(trim($row[$nombreColumn])),
                strtolower(trim($row[$apellidoMColumn])) . ' ' . strtolower(trim($row[$apellidoPColumn])),
                $row[$claveColumn],
                $row[$tipoColumn] . ' ' . $row[$areaColumn],
                "",
            );
            $student->setEmail($row[$emailColumn]);
            $students[] = $student;
        } catch (InvalidArgumentException $e) {
            ErrorList::add($e->getMessage());
            continue;
        }
    }

    return $students;
}

function _validateAlumniExcel($headerMap)
{
    $requiredHeaders = [
        "clave ulsa",
        "apellido paterno",
        "apellido materno",
        "nombre",
        "tipo de programa",
        "Área de programa"
    ];

    foreach ($requiredHeaders as $header) {
        if (!isset($headerMap[$header])) {
            throw new RuntimeException("Columna requerida no encontrada: $header");
        }
    }
    return true;
}
