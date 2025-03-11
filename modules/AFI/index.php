<!-- FIXME: Calculate the path of the files or use full path in a constant -->
<?php
/*error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);*/
//require_once './include/bd_pdo.php';
require_once '../../include/util.php';
require_once './process_excel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['excelFile']) && $_FILES['excelFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['excelFile']['tmp_name'];
        $fileName = $_FILES['excelFile']['name'];
        $fileSize = $_FILES['excelFile']['size'];
        $allowedExtensions = ['xls', 'xlsx'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $destPath = "$uploadDir$fileName";
            process_excel($fileTmpPath, $uploadDir, $fileName);
        } else {
            echo '<div class="alert alert-danger" role="alert">Invalid file type. Only Excel files are allowed.</div>';
        }
    } else {
        echo '<div class="alert alert-danger" role="alert">No file uploaded or an error occurred.</div>';
    }
}
?>
<!DOCTYPE html>

<head>
    <title>Proyectos | Facultad de ingeniería</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="../../css/bootstrap-ulsa.min.css" type="text/css">
    <link rel="stylesheet" href="../../css/jquery-ui.css" type="text/css">
    <link rel="stylesheet" href="../../css/indivisa.css" type="text/css">
    <link rel="stylesheet" href="../../css/style.css" type="text/css">
    <link rel="stylesheet" href="../../css/fa_all.css" type="text/css">

    <script src="../../js/util.js"></script>
</head>

<body style="display: block;">
    <?php
    include "../../include/header.php";
    ?>

    <main class="container content marco">
        <form action="" method="post" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label for="excelFile" class="form-label">Upload SGU Excel:</label>
                <input type="file" class="form-control form-control-lg" id="excelFile" name="excelFile"
                    accept=".xls,.xlsx" required>
                <div id="emailHelp" class="form-text">All the process data will be secure.</div>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
    </main>

    <?php
    include "../../include/footer.php";
    ?>

    <script src="../../js/jquery.min.js"></script>
    <script src="../../js/bootstrap/popper.min.js"></script>
    <script src="../../js/bootstrap/bootstrap.min.js"></script>
    <script src="../../js/util.js"></script>
    <script src="../../js/sidebarmenu.js"></script>

</body>

</html>