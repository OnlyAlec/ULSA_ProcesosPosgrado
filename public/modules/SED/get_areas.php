<?php
require_once '../../../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'getMasters') {
            $res = getMastersPrograms();
        } elseif ($action === 'getSpecialty') {
            $res = getSpecialtyPrograms();
        } else {
            $res = array_unique(array_merge(getMastersPrograms(), getSpecialtyPrograms()), SORT_REGULAR);
        }

        echo json_encode(["success" => true, "areas" => $res]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    exit;
}
?>
