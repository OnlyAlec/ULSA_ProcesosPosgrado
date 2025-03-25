<?php
require_once '../../../includes/config/constants.php';
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once './update_from_excel.php';

        $allowedExtensions = ['xls', 'xlsx'];
        $regex = '/^[A-Za-z]$/';
        $uploadDir = __DIR__ . '/uploads/';

        if (isset($_FILES['sedExcelFile'])) {
            if ($_FILES['sedExcelFile']['error'] !== UPLOAD_ERR_OK)
                throw new RuntimeException('Error uploading file.');

            $fileTmpPath = $_FILES['sedExcelFile']['tmp_name'];
            $fileName = str_replace(' ', '_', htmlspecialchars($_FILES['sedExcelFile']['name'], ENT_QUOTES, 'UTF-8'));
            $ext = strtolower(pathinfo($_FILES['sedExcelFile']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions))
                throw new RuntimeException('Invalid file type.');

            if(!preg_match($regex, $_POST["claveUlsa"]) || !preg_match($regex, $_POST["nombre"]) || !preg_match($regex, $_POST["estatus"])){
                throw new RuntimeException('Invalid column index.');
            }

            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);

            if (!move_uploaded_file($fileTmpPath, "$uploadDir$fileName"))
                throw new RuntimeException('Error uploading file.');

            $res = processExcel("$uploadDir$fileName", $_POST["claveUlsa"], $_POST["nombre"], $_POST["estatus"]);
        } 

        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
    }
} catch (RuntimeException $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
    ];
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
ob_end_flush();
?>
<!DOCTYPE html>

<?php
require_once INCLUDES_DIR . '/templates/head.php';
get_head("SED");
?>

<body style="display: block;">
    <?php require_once INCLUDES_DIR . '/templates/header.php';
    get_header("Seguimiento de Evaluación Docente");
    ?>

<main class="container content marco">
        <div>
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-2">Carga de concentrado de alumnos en Excel:</h1>
                <a href="../../index.php" class="btn btn-outline-primary">Regresar</a>
            </div>
            <br>
            <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                <div class="mb-3">
                    <h4 for="sedExcelFile" class="form-label">Subir archivo Excel:</h4>
                    <input type="file" class="form-control form-control-lg w-100 pb-5 pl-2" id="sedExcelFile" name="sedExcelFile" accept=".xls,.xlsx" required>
                    <div id="emailHelp" class="form-text d-flex justify-content-end">Los datos se actualizarán en la base de datos.</div>
                </div>
                
                <div class="d-flex align-items-center">
                    <h4>Encabezados</h4>
                    <div class="fs-6 text-muted ml-2 mb-1">(ej: A, B, C, ...)</div>
                </div>

                <div class="mb-3">
                    <div class="row align-items-center mb-2">
                        <label class="col-2" for="claveUlsa" class="form-label me-2">Clave Ulsa:</label>
                        <input type="text" class="col-2 form-control w-auto" id="claveUlsa" name="claveUlsa" placeholder="Columna" maxlength="2">
                    </div>
                    <div class="row align-items-center mb-2">
                        <label class="col-2" for="nombre" class="form-label me-2">Nombre:</label>
                        <input type="text" class="col-2 form-control w-auto" id="nombre" name="nombre" placeholder="Columna" maxlength="2">
                    </div>
                    <div class="row align-items-center">
                        <label class="col-2" for="estatus" class="form-label me-2">Estatus:</label>
                        <input type="text" class="col-2 form-control w-auto" id="estatus" name="estatus" placeholder="Columna" maxlength="2">
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-danger">Cargar Excel</button>
                </div>
            </form>
            <br>
            <hr>
            <br>
    </main>

    <?php include INCLUDES_DIR . '/templates/footer.php'; ?>

    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/popper.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/util.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/SED/scripts.js"></script>
</body>

</html>