<?php

require_once VENDOR_DIR . "/autoload.php";
require_once INCLUDES_DIR . "/utilities/util.php";
require_once INCLUDES_DIR . "/utilities/handleErrors.php";


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
            throw new RuntimeException('El archivo Excel no contiene datos válidos.');
        }
        clearDatabaseTables();
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

        $stmt = $db->prepare("INSERT INTO name (first_name, last_name) VALUES (:first_name, :last_name) RETURNING id");
        $stmt->execute([
            ':first_name' => $name,
            ':last_name' => $lastname
        ]);
        $nameId = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO student (ulsa_id, name_id, program_id, email) VALUES (:ulsa_id, :name_id, :program_id, :email)");
        $stmt->execute([
            ':ulsa_id' => $ulsaId,
            ':name_id' => $nameId,
            ':program_id' => $careerId,
            ':email' => $email
        ]);

        return [
            'success' => true,
            'errors' => ErrorList::getAll()
        ];

    } catch (RuntimeException $e) {
        throw new RuntimeException(message: $e->getMessage());
    }
}

function deleteOneStudent($ulsaId)
{
    ErrorList::clear();
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("DELETE FROM student WHERE ulsa_id = (:ulsaId)");
        $stmt->execute([':ulsaId' => $ulsaId]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("No se encontro ningun estudiante con el ID proporcionado.");
        }

        return [
            'success' => true,
            'errors' => ErrorList::getAll()
        ];
    } catch (RuntimeException $e) {
        throw new RuntimeException(message: $e->getMessage());
    }
}

function deleteAllStudents()
{
    ErrorList::clear();
    try {
        clearDatabaseTables();
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
            ErrorList::add("Fila {$rowIndex}: Clave ULSA invalida.");
            continue;
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $firstName)) {
            ErrorList::add("Fila {$rowIndex}: Nombre invalido.");
            continue;
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $lastName)) {
            ErrorList::add("Fila {$rowIndex}: Apellidos invalidos.");
            continue;
        }
        if (empty($career)) {
            ErrorList::add("Fila {$rowIndex}: Carrera vacia.");
            continue;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ErrorList::add("Fila {$rowIndex}: Correo electrónico invalido.");
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

function clearDatabaseTables()
{
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();
        $db->exec("DELETE FROM student");
        $db->exec("DELETE FROM name");
        $db->exec("DELETE FROM program");
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        ErrorList::add($e->getMessage());
    }
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
            $stmt = $db->prepare("INSERT INTO name (first_name, last_name) VALUES (:first_name, :last_name) RETURNING id");
            $stmt->execute([
                ':first_name' => $data['first_names'][$i],
                ':last_name' => $data['last_names'][$i]
            ]);
            $nameIds[] = $db->lastInsertId();
        }

        // Insertar estudiantes
        for ($i = 0; $i < count($data['ulsa_ids']); $i++) {
            $stmt = $db->prepare("INSERT INTO student (ulsa_id, name_id, program_id, email) VALUES (:ulsa_id, :name_id, :program_id, :email)");
            $stmt->execute([
                ':ulsa_id' => $data['ulsa_ids'][$i],
                ':name_id' => $nameIds[$i],
                ':program_id' => $careerIds[$data['careers'][$i]],
                ':email' => $data['emails'][$i]
            ]);
        }
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        ErrorList::add($e->getMessage());
    }
}
