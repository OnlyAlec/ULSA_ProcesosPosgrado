<?php
require_once INCLUDES_DIR . "/utilities/responseHTTP.php";

function showMissingStudentsAFI()
{
    $studentsDB = getStudents();
    $filteredStudents = array_filter($studentsDB, fn($student) => !$student->getAfi());
    $data = array_map(fn($student) => $student->getJSON(), $filteredStudents);

    return responseOK($data);
}