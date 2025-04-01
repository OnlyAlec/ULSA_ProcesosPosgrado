<?php

require_once '../../../includes/config/constants.php';
require_once INCLUDES_DIR . '/utilities/mailer.php';

function showStudentsAFIByStatus($status)
{
    $studentsDB = getStudents();
    $filteredStudents = array_filter($studentsDB, fn ($student) => $status == 'missing' ? !$student->getAfi() : $student->getAfi());
    return array_values(array_map(fn ($student) => $student->getJSON(), $filteredStudents));
}

function changeStatusAFI($ulsaID)
{
    $student = getStudentByUlsaID($ulsaID);
    $newStatus = !$student->getAfi();

    if (updateStudentFieldBoolean($student->getUlsaId(), "afi", $newStatus) == 0) {
        throw new RuntimeException("Error updating AFI status");
    }

    return ["newStatus" => $newStatus];
}

function sendEmailRemainder(Student $student)
{
    $mailer = new Mailer($student, "¡No dejes pasar estas fechas importantes!", "remainderAFI");
    $mailer->constructEmail();
    return  $mailer->send();
}
