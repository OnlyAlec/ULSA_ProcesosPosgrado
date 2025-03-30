<?php

require_once '../../../includes/config/constants.php';

function showStudentsAFIByStatus($status)
{
    $studentsDB = getStudents();
    $filteredStudents = array_filter($studentsDB, fn ($student) => $status == 'missing' ? !$student->getAfi() : $student->getAfi());
    return array_values(array_map(fn ($student) => $student->getJSON(), $filteredStudents));
}

function changeStatusAFI($ulsaID)
{
    $student = getStudentFromUlsaID($ulsaID);
    $newStatus = !$student->getAfi();

    if (updateStudentField($student, "afi", $newStatus) == 0) {
        throw new RuntimeException("Error updating AFI status");
    }

    return ["newStatus" => $newStatus];
}
