<?php
require_once __DIR__ . '/../../../includes/config/constants.php';
require_once VENDOR_DIR . "/autoload.php";
require_once INCLUDES_DIR . "/utilities/database.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

function process_excel_db($uploadDir, $fileTmpPath)
{
    // $db = getDatabaseConnection();
    return [
        'success' => true,
        'message' => 'Archivo procesado correctamente.',
    ];
}

// Parte 1: Obtener los datos de la BD
// $query = "
//     SELECT 
//         paternal_surname, 
//         maternal_surname, 
//         first_name, 
//         ulsa_id, 
//         type_desc, 
//         area, 
//         period 
//     FROM student
//     JOIN contact ON student.contact_id = contact.id
//     JOIN name ON student.name_id = name.id
//     JOIN program ON student.program_id = program.id;
// ";
// $stmt = $db->query($query);
// $studentsDB = $stmt->fetchAll(PDO::FETCH_ASSOC);

// // Parte 2: Cargar y procesar el archivo de Excel
// $filePath = "archivo.xlsx";
// try {
//     $spreadsheet = IOFactory::load($filePath);
// } catch (Exception $e) {
//     die("Error al cargar el archivo de Excel: " . $e->getMessage());
// }

// $sheet = $spreadsheet->getActiveSheet();
// $headerRow = 1;
// $dataStartRow = 2;
// $highestRow = $sheet->getHighestRow();
// $highestColumn = $sheet->getHighestColumn();

// // Buscar la columna "Clave ULSA (sin al, sólo las 6 cifras)"
// $headerMap = [];
// foreach ($sheet->getRowIterator($headerRow, $headerRow) as $row) {
//     $cellIterator = $row->getCellIterator();
//     foreach ($cellIterator as $cell) {
//         $headerMap[strtolower(trim($cell->getValue()))] = $cell->getColumn();
//     }
// }

// if (!isset($headerMap["clave ulsa (sin al, sólo las 6 cifras)"]) || !isset($headerMap["apellido paterno"]) || !isset($headerMap["apellido materno"]) || !isset($headerMap["nombre(s)"])) {
//     die("No se encontraron todas las columnas necesarias en el archivo de Excel.");
// }

// $claveColumn = $headerMap["clave ulsa (sin al, sólo las 6 cifras)"];
// $apellidoPColumn = $headerMap["apellido paterno"];
// $apellidoMColumn = $headerMap["apellido materno"];
// $nombreColumn = $headerMap["nombre(s)"];

// $studentsExcel = [];

// for ($row = $dataStartRow; $row <= $highestRow; $row++) {
//     $clave = $sheet->getCell($claveColumn . $row)->getValue();
//     $apellidoP = strtolower(trim($sheet->getCell($apellidoPColumn . $row)->getValue()));
//     $apellidoM = strtolower(trim($sheet->getCell($apellidoMColumn . $row)->getValue()));
//     $nombre = strtolower(trim($sheet->getCell($nombreColumn . $row)->getValue()));

//     if (preg_match('/^al(\d{6})$/i', $clave, $matches)) {
//         $clave = $matches[1];
//     }

//     $studentsExcel[] = [
//         'ulsa_id' => is_numeric($clave) && strlen($clave) == 6 ? $clave : null,
//         'paternal_surname' => $apellidoP,
//         'maternal_surname' => $apellidoM,
//         'first_name' => $nombre
//     ];
// }

// // Filtrar los estudiantes de la BD que no están en el Excel
// $filteredStudents = [];
// foreach ($studentsDB as $student) {
//     $found = false;
//     foreach ($studentsExcel as $excelStudent) {
//         if ($excelStudent['ulsa_id'] !== null && $excelStudent['ulsa_id'] == $student['ulsa_id']) {
//             $found = true;
//             break;
//         } elseif (
//             $excelStudent['ulsa_id'] === null &&
//             $excelStudent['paternal_surname'] == strtolower($student['paternal_surname']) &&
//             $excelStudent['maternal_surname'] == strtolower($student['maternal_surname']) &&
//             $excelStudent['first_name'] == strtolower($student['first_name'])
//         ) {
//             $found = true;
//             break;
//         }
//     }
//     if (!$found) {
//         $filteredStudents[] = $student;
//     }
// }

// // Imprimir los estudiantes que no están en el Excel
// foreach ($filteredStudents as $student) {
//     echo "{$student['paternal_surname']} {$student['maternal_surname']} {$student['first_name']} {$student['type_desc']} {$student['area']} ({$student['ulsa_id']})\n";
// }
