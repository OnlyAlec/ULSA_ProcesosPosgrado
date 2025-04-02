<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";

function updateSingleSED($studentID, $newState)
{
    $db = getDatabaseConnection();
    try {
        if (!$studentID || $newState === null) {
            return ["success" => false, "message" => "Datos inválidos."];
        }

        if (!$db) {
            return ["success" => false, "message" => "Error de conexión a la base de datos."];
        }

        $query = "UPDATE student SET SED = ? WHERE ulsa_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$newState, $studentID]);

        return ["success" => true, "message" => "Estado SED actualizado correctamente."];
    } catch (Exception $e) {
        return ["success" => false, "message" => "Error: " . $e->getMessage()];
    }
}

function updateSelectedSED($studentIDs)
{
    $db = getDatabaseConnection();

    try {
        if (!is_array($studentIDs) || empty($studentIDs)) {
            return ["success" => false, "message" => "No se enviaron alumnos."];
        }

        if (!$db) {
            return ["success" => false, "message" => "Error de conexión a la base de datos."];
        }

        $query = "UPDATE student SET SED = TRUE WHERE ulsa_id = ?";
        $stmt = $db->prepare($query);

        foreach ($studentIDs as $id) {
            $stmt->execute([$id]);
        }

        return ["success" => true, "message" => "Estado SED actualizado correctamente."];
    } catch (Exception $e) {
        return ["success" => false, "message" => "Error: " . $e->getMessage()];
    }
}

function getPrograms($action)
{
    try {
        $masters = getMastersPrograms();
        $specialty = getSpecialtyPrograms();

        if ($action === 'getMasters') {
            return $masters;
        } elseif ($action === 'getSpecialty') {
            return $specialty;
        } else {
            return array_unique(array_merge($masters, $specialty), SORT_REGULAR);
        }
    } catch (Exception $e) {
        return ["error" => "Error: " . $e->getMessage()];
    }
}
