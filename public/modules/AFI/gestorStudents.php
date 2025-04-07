<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . '/utilities/mailer.php';

function showStudentsAFIByStatus($status)
{
    if (empty($studentsDB = getStudents())) {
        return [];
    }
    $filteredStudents = array_filter($studentsDB, fn ($student) => $status == 'missing' ? !$student->getAfi() : $student->getAfi());
    return array_values(array_map(fn ($student) => $student->getJSON(), $filteredStudents));
}

function changeStatusAFI($ulsaID)
{
    if (empty($student = getStudentByUlsaID($ulsaID))) {
        return [];
    }

    $newStatus = !$student->getAfi();
    if (!updateStudentFieldBoolean($student->getUlsaId(), "afi", $newStatus)) {
        return ["newStatus" => !$newStatus];
    }
    return ["newStatus" => $newStatus];
}

function sendEmailRemainder(Student $student)
{
    if (empty($student)) {
        return [];
    }

    $mailer = new Mailer($student, "¡No dejes pasar estas fechas importantes!", "remainderAFI");
    $mailer->setNeedToken(true);
    $mailer->setRedirection(MODULES_DIR . "/AFI/confirmation.php");
    $mailer->constructEmail();
    return $mailer->send();
}
