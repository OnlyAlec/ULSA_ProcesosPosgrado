<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once 'update_from_excel.php';

        $allowedExtensions = ['xls', 'xlsx'];
        $regex = '/^[A-Za-z]$/';
        $uploadDir = __DIR__ . '/uploads/';

        if (isset($_FILES['sedExcelFile'])) {
            if ($_FILES['sedExcelFile']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error uploading file.');
            }

            $fileTmpPath = $_FILES['sedExcelFile']['tmp_name'];
            $fileName = str_replace(' ', '_', htmlspecialchars($_FILES['sedExcelFile']['name'], ENT_QUOTES, 'UTF-8'));
            $ext = strtolower(pathinfo($_FILES['sedExcelFile']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions)) {
                throw new RuntimeException('Invalid file type.');
            }

            if (!preg_match($regex, $_POST["claveUlsa"]) || !preg_match($regex, $_POST["nombre"]) || !preg_match($regex, $_POST["estatus"])) {
                throw new RuntimeException('Invalid column index.');
            }

            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new RuntimeException('Error creating directory for XLSX files.');
                }
            }

            if (!move_uploaded_file($fileTmpPath, "$uploadDir$fileName")) {
                throw new RuntimeException('Error uploading file.');
            }

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
                <h3 class="mb-2">Carga de concentrado de alumnos en Excel:</h3>
                <a href="index.php" class="btn btn-outline-primary">Regresar</a>
            </div>
            <br>
            <p class="d-flex justify-content-end">
                <b>Los datos se actualizarán en la base de datos.</b>
            </p>

            <form action="" method="post" enctype="multipart/form-data" class="mt-4 form-box">
                <!-- Archivo Excel -->
                <div class="form-group row mb-4">
                    <label for="sedExcelFile" class="col-md-3 col-form-label">Subir archivo Excel:</label>
                    <div class="col-md-8 custom-file ml-2">
                        <input type="file"
                            class="custom-file-input"
                            id="sedExcelFile"
                            name="sedExcelFile"
                            accept=".xls,.xlsx"
                            required>
                        <label class="custom-file-label"
                            for="sedExcelFile"
                            data-browse="Examinar">
                            Seleccionar archivo...
                        </label>
                    </div>
                </div>

                <!-- Encabezados -->
                <div class="d-flex align-items-center">
                    <h4>Encabezados</h4>
                    <div class="fs-6 text-muted ml-2 mb-1">(ej: A, B, C, ...)</div>
                </div>
                <br>

                <!-- Columnas -->
                <div class="form-group row mb-4">
                    <label for="claveUlsa" class="col-md-3 col-form-label">Clave Ulsa:</label>
                    <div class="col-md-8 ml-2">
                        <input type="text"
                            class="form-control w-auto"
                            id="claveUlsa"
                            name="claveUlsa"
                            placeholder="Columna"
                            maxlength="2">
                    </div>
                </div>
                <div class="form-group row mb-4">
                    <label for="nombre" class="col-md-3 col-form-label">Nombre:</label>
                    <div class="col-md-8 ml-2">
                        <input type="text"
                            class="form-control w-auto"
                            id="nombre"
                            name="nombre"
                            placeholder="Columna"
                            maxlength="2">
                    </div>
                </div>
                <div class="form-group row mb-4">
                    <label for="estatus" class="col-md-3 col-form-label">Estatus:</label>
                    <div class="col-md-8 ml-2">
                        <input type="text"
                            class="form-control w-auto"
                            id="estatus"
                            name="estatus"
                            placeholder="Columna"
                            maxlength="2">
                    </div>
                </div>

                <!-- Botón de envío -->
                <div class="text-center mt-4 d-flex justify-content-end">
                    <button type="submit"
                            class="btn btn-outline-primary"
                            style="width: 200px;">
                        <i class="fas fa-file-import mr-2"></i>
                        <span>Cargar Excel</span>
                    </button>
                </div>
            </form>

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