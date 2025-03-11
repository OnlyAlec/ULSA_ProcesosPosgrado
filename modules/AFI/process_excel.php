<?php

function process_excel($fileTmpPath, $uploadDir, $fileName)
{
    $destPath = $uploadDir . $fileName;
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        echo '<div class="alert alert-success" role="alert">File uploaded successfully.</div>';
    } else {
        echo '<div class="alert alert-danger" role="alert">There was an error moving the uploaded file.</div>';
    }
}

function loadFromExcel($path)
{

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
    $sheet = $spreadsheet->getActiveSheet();
    $noticias = array();

    foreach ($sheet->getRowIterator() as $row) {
        if ($row->getRowIndex() === 1) {
            continue;
        }

        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);

        $articulo = ["Titulo" => "", "Autores" => "", "Universidad" => "", "Articulo" => "", "Imagenes" => []];
        $strIndex = ["Titulo", "Autores", "Universidad", "Articulo", "Imagenes"];
        $i = 0;

        foreach ($cellIterator as $cell) {
            if ($i >= 4) {
                $articulo["Imagenes"][] = $cell->getValue();
            } else {
                $articulo[$strIndex[$i]] = $cell->getValue();
                $i++;
            }
        }

        // echo $articulo["Titulo"] . "<br>";
        $noticias[] = $articulo;
    }

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    return $noticias;
}