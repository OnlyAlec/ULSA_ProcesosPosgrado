<?php

require_once VENDOR_DIR . '/autoload.php';
require_once INCLUDES_DIR . '/utilities/util.php';
require_once INCLUDES_DIR . '/utilities/handleErrors.php';

function restartDatabaseFromExcel(
    $filePath,
    $ulsaIdColumn,
    $nameColumn,
    $lastnameColumn,
    $careerColumn,
    $emailColumn,
) {
    ErrorList::clear();

    $ulsaIdColumn = strtoupper($ulsaIdColumn);
    $nameColumn = strtoupper($nameColumn);
    $lastnameColumn = strtoupper($lastnameColumn);
    $careerColumn = strtoupper($careerColumn);
    $emailColumn = strtoupper($emailColumn);

    try {
        $data = loadExcelData(
            $filePath,
            $ulsaIdColumn,
            $nameColumn,
            $lastnameColumn,
            $careerColumn,
            $emailColumn,
        );
        if (empty($data['ulsa_ids'])) {
            throw new RuntimeException('El archivo Excel no contiene datos validos.');
        }
        deleteAllStudents();
        insertDataIntoDatabase($data);

        return 'Reinicio completado!';
    } catch (RuntimeException $e) {
        throw new RuntimeException(message: $e->getMessage());
    }
}

function insertOneStudent($ulsaId, $name, $lastname, $career, $email)
{
    ErrorList::clear();

    $ulsaId = trim($ulsaId);
    $name = trim($name);
    $lastname = trim($lastname);
    $career = trim($career);
    $email = trim($email);

    try {
        $user = getStudentByUlsaID($ulsaId);
        if ($user) {
            throw new RuntimeException(message: 'Estudiante ya existente');
        }

        $career = getProgramByName($career);
        $careerID = !$career ? insertProgram($career, true) : $career->getId();
        $userID = insertUser($ulsaId, $name, $lastname, $email, true);

        if (!insertStudent($userID, $careerID)) {
            throw new RuntimeException(message: 'Error en el registro');
        }
        return 'Registro correcto!';
    } catch (RuntimeException $e) {
        throw new RuntimeException(message: $e->getMessage());
    }
}

function deleteOneStudent($ulsaId)
{
    ErrorList::clear();
    $ulsaId = trim($ulsaId);

    if (!deleteStudent($ulsaId)) {
        throw new RuntimeException('No se encontro ningun estudiante con el ID proporcionado.');
    }

    return 'Estudiante eliminado!';
}

function loadExcelData(
    $filePath,
    $ulsaIdColumn,
    $nameColumn,
    $lastnameColumn,
    $careerColumn,
    $emailColumn,
) {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    $data = [
        'ulsa_ids' => [],
        'first_names' => [],
        'last_names' => [],
        'careers' => [],
        'emails' => [],
    ];

    foreach ($sheet->getRowIterator(2) as $row) {
        // Desde la fila 2 para omitir encabezados
        $rowIndex = $row->getRowIndex();

        $ulsaId = trim($sheet->getCell("{$ulsaIdColumn}{$rowIndex}")->getValue());
        $firstName = trim($sheet->getCell("{$nameColumn}{$rowIndex}")->getValue());
        $lastName = trim($sheet->getCell("{$lastnameColumn}{$rowIndex}")->getValue());
        $career = trim($sheet->getCell("{$careerColumn}{$rowIndex}")->getValue());
        $email = trim($sheet->getCell("{$emailColumn}{$rowIndex}")->getValue());

        if (!preg_match('/^\d{6}$/', $ulsaId)) {
            ErrorList::add("Fila {$rowIndex}: Clave ULSA invalida.\n");
            continue;
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $firstName)) {
            ErrorList::add("Fila {$rowIndex}: Nombre invalido.\n");
            continue;
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $lastName)) {
            ErrorList::add("Fila {$rowIndex}: Apellidos invalidos.\n");
            continue;
        }
        if (empty($career)) {
            ErrorList::add("Fila {$rowIndex}: Carrera vacia.\n");
            continue;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ErrorList::add("Fila {$rowIndex}: Correo electrOnico invalido.\n");
            continue;
        }

        $data['ulsa_ids'][] = intval($ulsaId);
        $data['first_names'][] = $firstName;
        $data['last_names'][] = $lastName;
        $data['careers'][] = $career;
        $data['emails'][] = $email;
    }

    return $data;
}

function insertDataIntoDatabase($data)
{
    try {
        $careers = array_unique($data['careers']);
        $careerIds = [];
        foreach ($careers as $career) {
            $careerIds[$career] = insertProgram($career, true);
        }

        $usersIds = [];
        for ($i = 0; $i < count($data['ulsa_ids']); $i++) {
            $usersIds[] = insertUser(
                $data['ulsa_ids'][$i],
                $data['first_names'][$i],
                $data['last_names'][$i],
                $data['emails'][$i],
                true,
            );
        }

        for ($i = 0; $i < count($usersIds); $i++) {
            insertStudent($usersIds[$i], $careerIds[$data['careers'][$i]]);
        }
    } catch (PDOException $e) {
        throw new RuntimeException('No se pueden agregar nuevos datos');
    }
}
