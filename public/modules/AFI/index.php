<?php
require_once '../../../includes/config/constants.php';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once './db_excel.php';
        require_once './all_excel.php';

        $allowedExtensions = ['xls', 'xlsx'];
        $uploadDir = __DIR__ . '/uploads/';

        if (isset($_FILES['excelFile'])) {
            if ($_FILES['excelFile']['error'] !== UPLOAD_ERR_OK)
                throw new RuntimeException('Error uploading file.');

            $fileTmpPath = $_FILES['excelFile']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions))
                throw new RuntimeException('Invalid file type.');

            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);

            if (!move_uploaded_file($fileTmpPath, $uploadDir))
                throw new RuntimeException('Error uploading file.');

            $res = process_excel_db($uploadDir, $fileTmpPath);

        } else if (isset($_FILES['excelForms']) && isset($_FILES['excelAlumni'])) {
            $fileTmpPath1 = $_FILES['excelForms']['tmp_name'];
            $fileTmpPath2 = $_FILES['excelAlumni']['tmp_name'];
            $ext1 = strtolower(pathinfo($_FILES['excelForms']['name'], PATHINFO_EXTENSION));
            $ext2 = strtolower(pathinfo($_FILES['excelAlumni']['name'], PATHINFO_EXTENSION));

            if (in_array($ext1, $allowedExtensions) && in_array($ext2, $allowedExtensions)) {
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0777, true);

                if (!move_uploaded_file($fileTmpPath1, $uploadDir) && !move_uploaded_file($fileTmpPath2, $uploadDir))
                    throw new RuntimeException('Error uploading file.');

                $res = process_multiple_excels($uploadDir, $fileTmpPath1, $fileTmpPath2);
            } else
                throw new RuntimeException('Invalid file type.');
        }

        return json_encode($res);
    }
} catch (\RuntimeException $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
    ];
    return json_encode($response);
}
?>
<!DOCTYPE html>

<?php
require_once INCLUDES_DIR . '/templates/head.php';
get_head("AFI");
?>

<body style="display: block;">
    <?php require_once INCLUDES_DIR . '/templates/header.php';
    get_header("Avisos de Fechas Importantes");
    ?>

    <main class="container content marco">
        <h1>Excel y base de datos:</h1>
        <p>Al usar este método, solamente sera requerido el excel que obtiene de <b>Microsoft Forms</b>.</p>
        <form action="" method="post" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label for="excelFile" class="form-label">Subir archivo Excel de Microsoft Forms:</label>
                <input type="file" class="form-control form-control-lg" id="excelFile" name="excelFile"
                    accept=".xls,.xlsx" required>
                <div id="emailHelp" class="form-text">Los datos se guardaran en la base de datos.</div>
            </div>
            <button type="submit" class="btn btn-primary">Obtener alumnos faltantes</button>
        </form>
        <br>
        <hr>
        <br>
        <h1>Unicamente excel's:</h1>
        <p>Al usar este método, son requeridos <b>2 archivos excel</b>: el que obtiene de <b>Microsoft Forms</b> y el de
            la <b>lista de alumnos completa</b>.</p>
        <form action="" method="post" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label for="excelForms" class="form-label">Subir archivo Excel de Microsoft Forms:</label>
                <input type="file" class="form-control form-control-lg" id="excelForms" name="excelForms"
                    accept=".xls,.xlsx" required>
            </div>
            <div class="mb-3">
                <label for="excelAlumni" class="form-label">Subir archivo Excel de alumonos:</label>
                <input type="file" class="form-control form-control-lg" id="excelAlumni" name="excelAlumni"
                    accept=".xls,.xlsx" required>
            </div>
            <button type="submit" class="btn btn-primary">Obtener alumnos faltantes</button>
        </form>
    </main>

    <?php require_once INCLUDES_DIR . '/templates/footer.php'; ?>

    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/popper.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/util.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/AFI/scripts.js"></script>
</body>

</html>