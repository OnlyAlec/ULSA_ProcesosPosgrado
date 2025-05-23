<?php

require_once VENDOR_DIR . '/autoload.php';
require_once INCLUDES_DIR . '/utilities/util.php';
require_once INCLUDES_DIR . '/utilities/handleErrors.php';
require_once INCLUDES_DIR . '/utilities/database.php';

function restartDatabaseFromExcel(
    $filePath,
    $ulsaIdColumn,
    $nameColumn,
    $lastnameColumn,
    $emailColumn,
) {
    ErrorList::clear();

    $ulsaIdColumn = strtoupper($ulsaIdColumn);
    $nameColumn = strtoupper($nameColumn);
    $lastnameColumn = strtoupper($lastnameColumn);
    $emailColumn = strtoupper($emailColumn);

    try {
        $data = loadExcelData($filePath, $ulsaIdColumn, $nameColumn, $lastnameColumn, $emailColumn);
        if (empty($data['ulsa_ids'])) {
            throw new RuntimeException('El archivo Excel no contiene datos validos.');
        }
        deleteAllProfessors();
        insertDataIntoDatabase($data);

        return [
            'success' => true,
            'errors' => ErrorList::getAll(),
        ];
    } catch (RuntimeException $e) {
        throw new RuntimeException(message: $e->getMessage());
    }
}

function insertOneProfessor($ulsaId, $name, $lastname, $email)
{
    ErrorList::clear();

    $ulsaId = trim($ulsaId);
    $name = trim($name);
    $lastname = trim($lastname);
    $email = trim($email);

    try {
        $db = getDatabaseConnection();

        $stmt = $db->prepare(
            'INSERT INTO public.user (first_name, last_name, ulsa_id, email) VALUES (:first_name, :last_name, :ulsa_id, :email) RETURNING id',
        );
        $stmt->execute([
            ':first_name' => $name,
            ':last_name' => $lastname,
            ':ulsa_id' => $ulsaId,
            ':email' => $email,
        ]);
        $userId = $stmt->fetchColumn();

        $stmt = $db->prepare('INSERT INTO professor (user_id) VALUES (:user_id)');
        $stmt->execute([
            ':user_id' => $userId,
        ]);

        return [
            'success' => true,
            'errors' => ErrorList::getAll(),
        ];
    } catch (RuntimeException $e) {
        throw new RuntimeException(message: $e->getMessage());
    }
}

function loadExcelData($filePath, $ulsaIdColumn, $nameColumn, $lastnameColumn, $emailColumn)
{
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    $data = [
        'ulsa_ids' => [],
        'first_names' => [],
        'last_names' => [],
        'emails' => [],
    ];

    foreach ($sheet->getRowIterator(2) as $row) {
        // Desde la fila 2 para omitir encabezados
        $rowIndex = $row->getRowIndex();

        $ulsaId = trim($sheet->getCell("{$ulsaIdColumn}{$rowIndex}")->getValue());
        $firstName = trim($sheet->getCell("{$nameColumn}{$rowIndex}")->getValue());
        $lastName = trim($sheet->getCell("{$lastnameColumn}{$rowIndex}")->getValue());
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
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ErrorList::add("Fila {$rowIndex}: Correo electrOnico invalido.\n");
            continue;
        }

        $data['ulsa_ids'][] = intval($ulsaId);
        $data['first_names'][] = $firstName;
        $data['last_names'][] = $lastName;
        $data['emails'][] = $email;
    }

    return $data;
}

function insertDataIntoDatabase($data)
{
    try {
        $db = getDatabaseConnection();
        $db->beginTransaction();

        // Insertar nombres y apellidos
        $userIds = [];
        for ($i = 0; $i < count($data['first_names']); $i++) {
            $stmt = $db->prepare(
                'INSERT INTO public.user (first_name, last_name, ulsa_id, email) VALUES (:first_name, :last_name, :ulsa_id, :email) RETURNING id',
            );
            $stmt->execute([
                ':first_name' => $data['first_names'][$i],
                ':last_name' => $data['last_names'][$i],
                ':ulsa_id' => $data['ulsa_ids'][$i],
                ':email' => $data['emails'][$i],
            ]);
            $userIds[] = $db->lastInsertId();
        }

        // Insertar estudiantes
        for ($i = 0; $i < count($data['ulsa_ids']); $i++) {
            $stmt = $db->prepare('INSERT INTO professor (user_id) VALUES (:user_id)');
            $stmt->execute([
                ':user_id' => $userIds[$i],
            ]);
        }
        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        ErrorList::add($e->getMessage());
    }
}
