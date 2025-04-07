<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . '/utilities/database.php';
require_once INCLUDES_DIR . '/utilities/mailer.php';

function changeStatusSEDSingle($studentID, $newState)
{
    if (updateStudentFieldBoolean($studentID, 'sed', $newState) == 0) {
        throw new RuntimeException("Error updating SED status");
    }

    return "";
}

function changeStatusSEDGroup($studentIDs)
{
    $error = false;

    foreach ($studentIDs as $id) {
        if (updateStudentFieldBoolean($id, 'sed', true) == 0) {
            ErrorList::add("Error updating SED status of student $id");
            $error = true;
        }
    }

    if ($error) {
        throw new RuntimeException("");
    }

    return "";
}

function sendEmailRemainder(Student $student)
{
    $mailer = new Mailer($student, "¡No olvides contestar la Evaluación Docente!", "remainderSED");
    $mailer->constructEmail();
    return  $mailer->send();
}

function getProgramsFiltered($action)
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