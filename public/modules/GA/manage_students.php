<?php

require_once VENDOR_DIR . "/autoload.php";
require_once INCLUDES_DIR . "/utilities/util.php";
require_once INCLUDES_DIR . "/utilities/handleErrors.php";
require_once INCLUDES_DIR . "/utilities/database.php";


function restartDatabaseFromExcel($filePath, $ulsaIdColumn, $nameColumn, $lastnameColumn, $careerColumn, $emailColumn)
{
    ErrorList::clear();

    $ulsaIdColumn   = strtoupper($ulsaIdColumn);
    $nameColumn     = strtoupper($nameColumn);
    $lastnameColumn = strtoupper($lastnameColumn);
    $careerColumn   = strtoupper($careerColumn);
    $emailColumn    = strtoupper($emailColumn);

    try {

        $data = loadExcelData($filePath, $ulsaIdColumn, $nameColumn, $lastnameColumn, $careerColumn, $emailColumn);
        if (empty($data['ulsa_ids'])) {
            throw new RuntimeException('El archivo Excel no contiene datos validos.');
        }
        deleteAllStudents();
        insertDataIntoDatabase($data);

        return [
            'success' => true,
            'errors' => ErrorList::getAll()
        ];
    } catch (RuntimeException $e) {
        throw new RuntimeException(message: $e->getMessage());
    }
}

function insertOneStudent($ulsaId, $name, $lastname, $career, $email)
{
    ErrorList::clear();

    $ulsaId   = trim($ulsaId);
    $name     = trim($name);
    $lastname = trim($lastname);
    $career   = trim($career);
    $email    = trim($email);

    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT id FROM program WHERE career = :career");
        $stmt->execute([':career' => $career]);
        $careerId = $stmt->fetchColumn();

        if (!$careerId) {
            $stmt = $db->prepare("INSERT INTO program (career) VALUES (:career) RETURNING id");
            $stmt->execute([':career' => $career]);
            $careerId = $db->lastInsertId();
        }

        $stmt = $db->prepare("INSERT INTO public.user (first_name, last_name, ulsa_id, email) VALUES (:first_name, :last_name, :ulsa_id, :email) RETURNING id");
        $stmt->execute([
            ':first_name' => $name,
            ':last_name' => $lastname,
            ':ulsa_id' => $ulsaId,
            ':email' => $email
        ]);
        $userId = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO student (program_id, user_id) VALUES (:program_id, :user_id)");
        $stmt->execute([
            ':program_id' => $careerId,
            ':user_id' => $userId,
        ]);

        return [
            'success' => true,
            'errors' => ErrorList::getAll()
        ];

    } catch (RuntimeException $e) {
        throw new RuntimeException(message: $e->getMessage());
    }
}

function loadExcelData($filePath, $ulsaIdColumn, $nameColumn, $lastnameColumn, $careerColumn, $emailColumn)
{
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    $data = [
        'ulsa_ids'    => [],
        'first_names' => [],
        'last_names'  => [],
        'careers'     => [],
        'emails'      => []
    ];

    foreach ($sheet->getRowIterator(2) as $row) { // Desde la fila 2 para omitir encabezados
        $rowIndex = $row->getRowIndex();

        $ulsaId    = trim($sheet->getCell("{$ulsaIdColumn}{$rowIndex}")->getValue());
        $firstName = trim($sheet->getCell("{$nameColumn}{$rowIndex}")->getValue());
        $lastName  = trim($sheet->getCell("{$lastnameColumn}{$rowIndex}")->getValue());
        $career    = trim($sheet->getCell("{$careerColumn}{$rowIndex}")->getValue());
        $email     = trim($sheet->getCell("{$emailColumn}{$rowIndex}")->getValue());

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

        $data['ulsa_ids'][]    = intval($ulsaId);
        $data['first_names'][] = $firstName;
        $data['last_names'][]  = $lastName;
        $data['careers'][]     = $career;
        $data['emails'][]      = $email;
    }

    return $data;
}


function insertDataIntoDatabase($data)
{
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();

        // Insertar carreras únicas
        $careers = array_unique($data['careers']);
        $careerIds = [];
        foreach ($careers as $career) {
            $stmt = $db->prepare("INSERT INTO program (career) VALUES (:career) RETURNING id");
            $stmt->execute([':career' => $career]);
            $careerIds[$career] = $db->lastInsertId();
        }

        // Insertar nombres y apellidos
        $nameIds = [];
        for ($i = 0; $i < count($data['first_names']); $i++) {
            $stmt = $db->prepare("INSERT INTO public.user (first_name, last_name, email, ulsa_id) VALUES (:first_name, :last_name, :email, :ulsa_id) RETURNING id");
            $stmt->execute([
                ':first_name' => $data['first_names'][$i],
                ':last_name' => $data['last_names'][$i],
                ':email' => $data['emails'][$i],
                ':ulsa_id' => $data['ulsa_ids'][$i],
            ]);
            $userIds[] = $db->lastInsertId();
        }

        // Insertar estudiantes
        for ($i = 0; $i < count($data['ulsa_ids']); $i++) {
            $stmt = $db->prepare("INSERT INTO student (user_id, program_id) VALUES (:user_id, :program_id)");
            $stmt->execute([
                ':user_id' => $userIds[$i],
                ':program_id' => $careerIds[$data['careers'][$i]],
            ]);
        }
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        ErrorList::add($e->getMessage());
    }
}
