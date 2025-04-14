<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once VENDOR_DIR . "/autoload.php";
require_once INCLUDES_DIR . "/utilities/util.php";
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/utilities/handleErrors.php";
require_once INCLUDES_DIR . "/models/student.php";

function processExcel($filePath, $ulsaIdColumn, $nameColumn, $sedColumn)
{
    ErrorList::clear();

    $ulsaIdColumn = strtoupper($ulsaIdColumn);
    $nameColumn   = strtoupper($nameColumn);
    $sedColumn    = strtoupper($sedColumn);

    try {
        $excelStudents = loadExcelStudents($filePath, $ulsaIdColumn, $nameColumn, $sedColumn);
        updateStudentSedInDatabase($excelStudents);
        return [
            'success' => true,
            'errors' => ErrorList::getAll()
        ];
    } catch (RuntimeException $e) {
        throw new RuntimeException($e->getMessage());
    }
}


function updateStudentSedInDatabase($students)
{
    try {
        $db = getDatabaseConnection();
        $query = "UPDATE student SET SED = :sed WHERE ulsa_id = :ulsa_id";
        $stmt = $db->prepare($query);

        foreach ($students as $student) {
            $stmt->execute([
                ':sed' => (int) $student->getSed(),
                ':ulsa_id' => $student->getUlsaId()
            ]);
        }
    } catch (InvalidArgumentException $e) {
        ErrorList::add($e->getMessage());
    }
}

function loadExcelStudents($filePath, $ulsaIdColumn, $nameColumn, $sedColumn)
{
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    $students = [];

    foreach ($sheet->getRowIterator(2) as $row) { // Suponiendo que la fila 1 contiene encabezados
        $cellUlsaId = $sheet->getCell("{$ulsaIdColumn}{$row->getRowIndex()}")->getValue();
        $cellName = $sheet->getCell("{$nameColumn}{$row->getRowIndex()}")->getValue();
        $cellSed = $sheet->getCell("{$sedColumn}{$row->getRowIndex()}")->getValue();

        // Limpiar y convertir el ULSA ID a entero
        if ($cellUlsaId !== null) {
            $cellUlsaId = trim($cellUlsaId);
            $cellUlsaId = intval($cellUlsaId);
        } else {
            $cellUlsaId = 0; // O manejarlo de otra manera según tu lógica
        }

        // Validar que el ULSA ID sea un número de 6 cifras
        if (preg_match('/^\d{6}$/', $cellUlsaId)) {
            $nameParts = explode(" ", trim($cellName), 3);
            $paternalSurname = $nameParts[0] ?? '';
            $maternalSurname = $nameParts[1] ?? '';
            $firstName = $nameParts[2] ?? '';

            $falseValues = ["", "0", "no", "false", "n"];
            $sedBool = !in_array(strtolower(trim($cellSed)), $falseValues, true);

            try {
                $student = new Student($firstName, $maternalSurname." ".$paternalSurname, $cellUlsaId, carrer: " ", email:"");
                $student->setSed($sedBool);
                $students[] = $student;
            } catch (InvalidArgumentException $e) {
                ErrorList::add($e->getMessage());
                continue;
            }
        }
    }
    return $students;
}
