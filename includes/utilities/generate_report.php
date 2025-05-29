<?php

use Fpdf\fpdf;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once VENDOR_DIR . '/autoload.php';

function addNewPage($pdf, $title)
{
    $pdf->AddPage();
    $pdf->Image(PUBLIC_DIR . ASSETS_PATH . '/img/logo_lasalle.png', 10, 10, 45, 15.3, 'png');
    $pdf->SetFont('IndivisaSans', '', 15);
    
    $pdf->Cell(0, 40, $title, 0, 1, 'R');
}

function addTable($pdf, $title, $titleTable, $students)
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
            addNewPage($pdf, $title); // Añadir una página si se acerca al final
        }

        $pdf->SetFont('IndivisaTextSans', '', 10);
        $pdf->Cell(
            130,
            10,
            mb_convert_encoding($student->fullName, 'ISO-8859-1', 'UTF-8'),
            1,
            0,
            'L',
        );
        $pdf->Cell(60, 10, $student->id, 1, 1, 'C');
    }
    $pdf->Ln(5);
}

function separateStudents($students, $statusField)
{
    $studentsEvaluation = [
        'EVALUATED' => [],
        'NOT_EVALUATED' => [],
    ];

    foreach ($students as $student) {
        $programName = strtoupper($student->carrer);
        $evaluationKey = $student->$statusField ? 'EVALUATED' : 'NOT_EVALUATED';
        $studentsEvaluation[$evaluationKey][$programName][] = $student;
    }

    foreach(['EVALUATED', 'NOT_EVALUATED'] as $evaluationStatus) {
        ksort($studentsEvaluation[$evaluationStatus]);
    }

    return $studentsEvaluation;
}

function addResume($pdf, $students, $statusField) {

    $programsTotal = [];

    foreach ($students as $student) {
        $programName = strtoupper($student->carrer);

        if (!isset($programsTotal[$programName])){
            $programsTotal[$programName]=['EVALUATED' => 0, 'TOTAL' => 0];
        }

        if($student->$statusField){
            $programsTotal[$programName]['EVALUATED'] += 1;
        }

        $programsTotal[$programName]['TOTAL'] += 1;
    }

    ksort($programsTotal);

    $pdf->SetFont('IndivisaSans', '', 15);
    $pdf->Cell(0, 10, mb_convert_encoding('Resumen:', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
    $pdf->Ln(5);

    $pdf->SetFont('IndivisaTextSans', '', 12);
    $pdf->SetFillColor(255, 204, 153);

    $pdf->Cell(100, 10, 'PROGRAMAS', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'YA RESPONDIERON', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'PORCENTAJE', 1, 1, 'C', true);

    $total = 0;

    foreach ($programsTotal as $programName => $program) {
        $pdf->SetFont('IndivisaTextSans', '', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(200, 220, 255);
    
        $x = $pdf->GetX();
        $y = $pdf->GetY();
    
        $pdf->MultiCell(100, 10, mb_convert_encoding($programName, 'ISO-8859-1', 'UTF-8'), 1, 'C', true);
    
        $yAfter = $pdf->GetY();
        $height = $yAfter - $y;
    
        $pdf->SetXY($x + 100, $y);
    
        $pdf->SetFillColor(255, 255, 153);
        $pdf->Cell(40, $height, $program['EVALUATED'], 1, 0, 'C', true);
        $pdf->Cell(20, $height, $program['TOTAL'], 1, 0, 'C', true);
    
        $pdf->SetFont('IndivisaSans', '', 12);
        $pdf->SetFillColor(255, 255, 0);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(30, $height, round(($program['EVALUATED'] / $program['TOTAL']) * 100, 2) . '%', 1, 1, 'C', true);
    
        $total += $program['EVALUATED'];
    }    

    $pdf->SetFont('IndivisaSans', '', 14);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->Cell(100, 10, 'TOTAL', 1, 0, 'R');

    $pdf->SetFillColor(255, 255, 0);

    $pdf->Cell(40, 10, $total, 1, 0, 'C', true);

    $pdf->Cell(20, 10, count($students), 1, 0, 'C', true);

    $pdf->SetTextColor(255, 0, 0);
    $pdf->Cell(30, 10, round($total / count($students) * 100, 2) . '%', 1, 0, 'C', true);

}

function generateReport($studentsJson, $statusField, $filename)
{
    $studentsData = json_decode($studentsJson, true);

    $students = [];
    foreach ($studentsData as $studentData) {
        $student = new stdClass();
        $student->id = $studentData['id'];
        $student->fullName = $studentData['fullName'];
        $student->email = $studentData['email'];
        $student->$statusField = $studentData[$statusField];
        $student->carrer = $studentData['carrer'];

        $students[] = $student;
    }

    $pdf = new Fpdf();

    $pdf->AddFont('IndivisaSans', '', 'IndivisaDisplaySans-Regular.php');
    $pdf->AddFont('IndivisaSerif', '', 'IndivisaDisplaySerif-RegularItalic.php');
    $pdf->AddFont('IndivisaTextSans', '', 'IndivisaTextSans-Regular.php');

    $studentsByEvaluation = separateStudents($students, $statusField);

    if ($statusField == 'afiStatus') {
        $title = 'Reporte del Formulario de Avisos Importantes';
        $subtitle = 'el Formulario';
    } else {
        $title = 'Reporte de Evaluaciones Docentes';
        $subtitle = 'la Evaluación Docente';
    }

    if (!empty($studentsByEvaluation['EVALUATED'])) {
        addNewPage($pdf, $title);
        $pdf->SetFont('IndivisaSans', '', 14);
        $pdf->Cell(
            0,
            10,
            mb_convert_encoding(
                'Alumnos que realizaron ' . $subtitle,
                'ISO-8859-1',
                'UTF-8',
            ),
            0,
            1,
            'L',
        );
        $pdf->Ln(5);

        foreach ($studentsByEvaluation['EVALUATED'] as $programName => $programStudents) {
            addTable($pdf, $title, $programName, $programStudents);
        }
    }

    if (!empty($studentsByEvaluation['NOT_EVALUATED'])) {
        addNewPage($pdf, $title);
        $pdf->SetFont('IndivisaSans', '', 14);
        $pdf->Cell(
            0,
            10,
            mb_convert_encoding(
                'Alumnos que no han realizado ' . $subtitle,
                'ISO-8859-1',
                'UTF-8',
            ),
            0,
            1,
            'L',
        );
        $pdf->Ln(5);

        foreach ($studentsByEvaluation['NOT_EVALUATED'] as $programName => $programStudents) {
            addTable($pdf, $title, $programName, $programStudents);
        }
    }

    addNewPage($pdf, $title);
    addResume($pdf, $students, $statusField);

    $reportsDir = PDF_DIR;
    if (!is_dir($reportsDir)) {
        if (!mkdir($reportsDir, 02775, true)) {
            throw new RuntimeException('Error creating reports directory.');
        }
    }
    $outputPath = PDF_DIR . '/' . $filename . '.pdf';
    $pdf->Output('F', $outputPath);

    return true;
}
