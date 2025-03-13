<?php
use \PhpOffice\PhpSpreadsheet\Cell\Coordinate;

require_once __DIR__ . '/../../../includes/config/constants.php';
require_once VENDOR_DIR . "/autoload.php";
require_once INCLUDES_DIR . "/utilities/util.php";
require_once INCLUDES_DIR . "/utilities/handleErrors.php";

function process_multiple_excels($uploadDir, $filePath1, $filePath2)
{
   ErrorList::clear();

   $outputFile       = null;
   $studentsAll      = [];
   $studentsForms    = [];
   $urloutputFile    = null;
   $filteredStudents = [];

    try {
        $studentsForms    = processExcel("$uploadDir$filePath1");
        $studentsAll      = processAllStudentsExcel("$uploadDir$filePath2");
        $filteredStudents = compareStudents($studentsForms, $studentsAll);
        $programCount     = getProgramCount($studentsAll, $filteredStudents);
        $outputFile       = createExcel($filteredStudents, $programCount);

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
                'totalDB' => count($studentsAll),
                'totalFiltered' => count($studentsArray)
            ],
            'errors' => ErrorList::getAll()
        ];
    } catch (RuntimeException $e) {
        throw new RuntimeException($e->getMessage());
    }
}

function processAllStudentsExcel($filePath)
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
    $tipoColumn      = $headerMap["tipo de programa"];
    $areaColumn      = $headerMap["Área de programa"];
    $claveColumn     = $headerMap["clave ulsa"];
    $nombreColumn    = $headerMap["nombre"];
    $apellidoPColumn = $headerMap["apellido paterno"];
    $apellidoMColumn = $headerMap["apellido materno"];

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