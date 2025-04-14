<?php

use Fpdf\fpdf;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once VENDOR_DIR . "/autoload.php";

if (isset($_POST['students']) && isset($_POST['filename'])) {
    $studentsData = json_decode($_POST['students'], true);
    $filename = $_POST['filename'];

    $students = [];
    foreach ($studentsData as $studentData) {
        $student = new stdClass();
        $student->id = $studentData['id'];
        $student->fullName = $studentData['fullName'];
        $student->email = $studentData['email'];
        $student->sedStatus = $studentData['sedStatus'];
        $student->carrer = $studentData['carrer'];

        $students[] = $student;
    }

    generateReport($students, $filename);
}

function addNewPage($pdf)
{
    $pdf->AddPage();
    $pdf->Image(PUBLIC_DIR . ASSETS_PATH . '/img/logo_lasalle.png', 10, 10, 45, 15.3, 'png');
    $pdf->SetFont('IndivisaSans', '', 15);
    $pdf->Cell(0, 40, 'Reporte de Evaluaciones Docentes', 0, 1, 'R');
}

function addTable($pdf, $titleTable, $students)
{
    $pdf->Ln(5);

    $pdf->SetFont('IndivisaSans', '', 12);

    // Encabezado de la tabla
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(190, 10, mb_convert_encoding($titleTable, 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

    // Columnas de la tabla
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(130, 10, 'Nombre del Estudiante', 1, 0, 'C', true);
    $pdf->Cell(60, 10, 'Clave ULSA', 1, 1, 'C', true);

    // Contenido de la tabla
    foreach ($students as $student) {
        if ($pdf->GetY() + 10 > 260) {
            addNewPage($pdf); // Añadir una página si se acerca al final
        }

        $pdf->SetFont('IndivisaTextSans', '', 10);
        $pdf->Cell(130, 10, mb_convert_encoding($student->fullName, 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
        $pdf->Cell(60, 10, $student->id, 1, 1, 'C');
    }
    $pdf->Ln(5);
}

function separateStudents($students)
{
    $studentsEvaluation = [
        'EVALUATED' => [],
        'NOT_EVALUATED' => []
    ];

    foreach ($students as $student) {
        $programName = strtoupper($student->carrer);
        $evaluationKey = $student->sedStatus ? 'EVALUATED' : 'NOT_EVALUATED';
        $studentsEvaluation[$evaluationKey][$programName][] = $student;
    }

    return $studentsEvaluation;
}

function generateReport($students, $filename)
{
    $pdf = new Fpdf();
    $reportsDir = __DIR__ . '/reports/';
    if (!mkdir($reportsDir, 0755, true)) {
        throw new RuntimeException('Error creating reports directory.');
    }

    $pdf->AddFont('IndivisaSans', '', 'IndivisaDisplaySans-Regular.php');
    $pdf->AddFont('IndivisaSerif', '', 'IndivisaDisplaySerif-RegularItalic.php');
    $pdf->AddFont('IndivisaTextSans', '', 'IndivisaTextSans-Regular.php');

    $studentsByEvaluation = separateStudents($students);

    if (!empty($studentsByEvaluation['EVALUATED'])) {
        addNewPage($pdf);
        $countEvaluated = 0;
        $pdf->SetFont('IndivisaSans', '', 14);
        $pdf->Cell(0, 10, mb_convert_encoding('Alumnos que realizaron la Evaluación Docente', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        $pdf->Ln(5);

        foreach ($studentsByEvaluation['EVALUATED'] as $programName => $programStudents) {
            addTable($pdf, $programName, $programStudents);
            $countEvaluated = $countEvaluated + count($programStudents);
        }
    }

    if (!empty($studentsByEvaluation['NOT_EVALUATED'])) {
        addNewPage($pdf);
        $pdf->SetFont('IndivisaSans', '', 14);
        $pdf->Cell(0, 10, mb_convert_encoding('Alumnos que no han realizado la Evaluación Docente', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        $pdf->Ln(5);

        foreach ($studentsByEvaluation['NOT_EVALUATED'] as $programName => $programStudents) {
            addTable($pdf, $programName, $programStudents);
        }
    }

    $percentage = round(($countEvaluated / count($students)) * 100, 2);
    addNewPage($pdf);
    $pdf->SetFont('IndivisaSans', '', 14);
    $pdf->Cell(0, 10, mb_convert_encoding('Números de Alumnos que realizaron la evaluación: ' . $countEvaluated, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->Cell(0, 10, mb_convert_encoding('Números de Alumnos que no han realizado la evaluación: ' . (count($students) - $countEvaluated), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->Cell(0, 10, mb_convert_encoding('Total de Alumnos: ' . count($students), 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->Cell(0, 10, mb_convert_encoding('Porcentaje de Cumplimiento: ' . $percentage . '%', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

    $outputPath = $reportsDir . $filename . '.pdf';
    $pdf->Output('F', $outputPath);
    echo json_encode(['url' => "/modules/SED/reports/$filename.pdf"]);

}
