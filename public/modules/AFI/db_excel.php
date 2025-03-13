<?php
use \PhpOffice\PhpSpreadsheet\Cell\Coordinate;

require_once __DIR__ . '/../../../includes/config/constants.php';
require_once VENDOR_DIR . "/autoload.php";
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/utilities/util.php";
require_once INCLUDES_DIR . "/utilities/handleErrors.php";

class Student
{
    private string $firstName;
    private string $maternalSurname;
    private string $paternalSurname;
    private int $ulsaID;
    private string $typeDesc;
    private string $area;
    private string $email;

    public function __construct($firstName, $maternalSurname, $paternalSurname, $ulsaID, $typeDesc, $area)
    {
        $this->firstName = $firstName;
        $this->maternalSurname = $maternalSurname;
        $this->paternalSurname = $paternalSurname;
        $this->typeDesc = $typeDesc;
        $this->area = $area;
        $validatedId = $this->validateUlsaId($ulsaID);
        if ($validatedId === -1) {
            throw new InvalidArgumentException("Invalid ULSA ID ($ulsaID) - $firstName $maternalSurname");
        }
        $this->ulsaID = $validatedId;
    }

    private function normalizeUlsaId($ulsaId)
    {
        return preg_replace('/^al(\d{6})$/i', '$1', $ulsaId);
    }

    private function validateUlsaId($ulsa_id)
    {
        $ulsa_id = $this->normalizeUlsaId($ulsa_id);

        if (strlen($ulsa_id) == 6)
            return (int) $ulsa_id;
        return -1;
    }

    public function getName()
    {
        return $this->firstName;
    }

    public function getApm()
    {
        return $this->maternalSurname;
    }

    public function getApp()
    {
        return $this->paternalSurname;
    }

    public function getUlsaId()
    {
        return $this->ulsaID;
    }

    public function getTypeDesc()
    {
        return $this->typeDesc;
    }

    public function setTypeDesc($typeDesc)
    {
        $this->typeDesc = $typeDesc;
    }

    public function getArea()
    {
        return $this->area;
    }

    public function setArea($area)
    {
        $this->area = $area;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }
}

function init_process($filePath)
{
    ErrorList::clear();

    $studentsExcel = [];
    $studentsDB = [];
    $filteredStudents = [];
    $outputFile = null;
    $urloutputFile = null;

    try {
        $studentsExcel = processExcel($filePath);
        $studentsDB = getStudents();
        $filteredStudents = compareStudents($studentsExcel, $studentsDB);
        $programCount = getProgramCount($studentsDB, $filteredStudents);
        $outputFile = createExcel($filteredStudents, $programCount);

        $urloutputFile = filePathToUrl($outputFile);
        $studentsArray = array_map(fn($student) => [
            'firstName' => $student->getName(),
            'maternalSurname' => $student->getApm(),
            'paternalSurname' => $student->getApp(),
            'ulsaID' => $student->getUlsaId(),
            'typeDesc' => $student->getTypeDesc(),
            'area' => $student->getArea(),
            'email' => $student->getEmail()
        ], $filteredStudents);
        return [
            'success' => true,
            'data' => [
                'students' => $studentsArray,
                'excel' => $urloutputFile,
                'totalDB' => count($studentsDB),
                'totalFiltered' => count($studentsArray)
            ],
            'errors' => ErrorList::getAll()
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

    if (!_validateExcel($headerMap))
        throw new RuntimeException('Archivo con estructura invalida.');

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
                strtolower(trim($row[$apellidoMColumn])),
                strtolower(trim($row[$apellidoPColumn])),
                $row[$claveColumn],
                $row[$tipoColumn],
                $row[$areaColumn]
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

function getStudents()
{
    $studentsDB = [];
    $db = getDatabaseConnection();
    $query = "SELECT LOWER(paternal_surname) AS paternal_surname, 
                LOWER(maternal_surname) AS maternal_surname, 
                LOWER(first_name) AS first_name, 
                ulsa_id, 
                LOWER(TRIM(type_desc)) AS type_desc, 
                LOWER(TRIM(area)) AS area, 
                ulsa_email 
              FROM student 
              JOIN contact ON student.contact_id = contact.id 
              JOIN name ON student.name_id = name.id 
              JOIN program ON student.program_id = program.id";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        try {
            $student = new Student(
                $row['first_name'],
                $row['maternal_surname'],
                $row['paternal_surname'],
                $row['ulsa_id'],
                $row['type_desc'],
                $row['area'],
            );
            $student->setEmail($row['ulsa_email']);
            $studentsDB[] = $student;
        } catch (InvalidArgumentException $e) {
            ErrorList::add($e->getMessage());
            continue;
        }
    }
    return $studentsDB;
}

function getProgramCount($db, $filtered)
{
    $totalCounts = [];
    $filteredCounts = [];

    foreach ($db as $student) {
        $program = $student->getArea();
        $totalCounts[$program] = ($totalCounts[$program] ?? 0) + 1;
    }

    foreach ($filtered as $student) {
        $program = $student->getArea();
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

function compareStudents($studentsExcel, $studentsDB)
{
    $excelById = [];
    $excelByName = [];
    foreach ($studentsExcel as $excelStudent) {
        if ($excelStudent->getUlsaId() !== null) {
            $excelById[$excelStudent->getUlsaId()] = true;
        } else {
            $nameKey = implode('|', [
                $excelStudent->getApp(),
                $excelStudent->getApm(),
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
                $student->getApp(),
                $student->getApm(),
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
        "Apellido Paterno",
        "Apellido Materno",
        "Nombre(s)",
        "Clave Ulsa",
        "Tipo de Programa",
        "Área",
        "Correo institucional"
    ];

    //* Format
    $sheet1->setTitle('Estudiantes sin confirmar AFI');
    foreach (range('A', 'G') as $index => $col) {
        $cell = "{$col}1";
        $sheet1->setCellValue($cell, $headers[$index]);
        $sheet1->getStyle($cell)->getFont()->setBold(true);
    }

    $sheet1->fromArray(
        [$headers],
        NULL,
        'A1'
    );
    $dataRows = [];
    foreach ($students as $student) {
        $dataRows[] = [
            $student->getApp(),
            $student->getApm(),
            $student->getName(),
            $student->getUlsaId(),
            $student->getTypeDesc(),
            $student->getArea(),
            $student->getEmail()
        ];
    }
    $sheet1->fromArray($dataRows, NULL, 'A2');

    //* Format
    foreach (range('A', 'G') as $col) {
        $sheet1->getColumnDimension($col)->setAutoSize(true);
    }


    $sheet2 = $newSpreadsheet->createSheet();
    $sheet2->setTitle('Conteos por Programa');
    $sheet2->setCellValue('A1', 'Programa');
    $sheet2->setCellValue('A2', 'Conteo Parcial');
    $sheet2->setCellValue('A3', 'Conteo Total');
    $sheet2->setCellValue('A4', 'Porcentaje');

    //* Format
    $sheet2->getStyle('A1:A4')->getFont()->setBold(true);

    $colIndex = 2;
    foreach ($programCount['programs'] as $program) {
        $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
        $sheet2->setCellValue("{$columnLetter}1", $program);

        $partial = $programCount['filtered'][$program] ?? 0;
        $total = $programCount['total'][$program] ?? 0;
        $percentage = ($total > 0) ? ($partial / $total) * 100 : 0;

        $sheet2->setCellValue("{$columnLetter}2", $partial);
        $sheet2->setCellValue("{$columnLetter}3", $total);
        $sheet2->setCellValue("{$columnLetter}4", round($percentage, 2) . '%');

        $colIndex++;
    }

    //* Format
    for ($i = 1; $i <= $colIndex; $i++) {
        $sheet2->getColumnDimensionByColumn($i)->setAutoSize(true);
    }

    //* Save File
    $timestamp = date('Y-m-d_H-i-s');
    $outputFile = XLSX_DIR . "/filtered_students_{$timestamp}.xlsx";
    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($newSpreadsheet);
    $writer->save($outputFile);

    return $outputFile;
}