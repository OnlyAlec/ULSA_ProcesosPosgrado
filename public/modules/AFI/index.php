<?php
require_once '../../../includes/config/constants.php';
ob_start();

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
            $fileName = str_replace(' ', '_', htmlspecialchars($_FILES['excelFile']['name'], ENT_QUOTES, 'UTF-8'));
            $ext = strtolower(pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions))
                throw new RuntimeException('Invalid file type.');

            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);

            if (!move_uploaded_file($fileTmpPath, "$uploadDir$fileName"))
                throw new RuntimeException('Error uploading file.');

            $res = init_process("$uploadDir$fileName");
        } else if (isset($_FILES['excelForms']) && isset($_FILES['excelAlumni'])) {
            $fileTmpPath1 = $_FILES['excelForms']['tmp_name'];
            $fileTmpPath2 = $_FILES['excelAlumni']['tmp_name'];

            $fileName1 = str_replace(' ', '_', htmlspecialchars($_FILES['excelForms']['name'], ENT_QUOTES, 'UTF-8'));
            $fileName2 = str_replace(' ', '_', htmlspecialchars($_FILES['excelAlumni']['name'], ENT_QUOTES, 'UTF-8'));

            $ext1 = strtolower(pathinfo($_FILES['excelForms']['name'], PATHINFO_EXTENSION));
            $ext2 = strtolower(pathinfo($_FILES['excelAlumni']['name'], PATHINFO_EXTENSION));

            if (in_array($ext1, $allowedExtensions) && in_array($ext2, $allowedExtensions)) {
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0777, true);

                if (!move_uploaded_file($fileTmpPath1, "$uploadDir$fileName1") || !move_uploaded_file($fileTmpPath2, "$uploadDir$fileName2"))
                    throw new RuntimeException('Error uploading file.');

                $res = process_multiple_excels($uploadDir, $fileName1, $fileName2);
            } else
                throw new RuntimeException('Invalid file type.');
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
get_head("AFI");
?>

<body style="display: block;">
    <?php require_once INCLUDES_DIR . '/templates/header.php';
    get_header("Avisos de Fechas Importantes");
    ?>

    <main class="container content marco">
        <div>
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
            <h1>Únicamente excel's:</h1>
            <p>Al usar este método, son requeridos <b>2 archivos excel</b>: el que obtiene de <b>Microsoft Forms</b> y
                el de
                la <b>lista de alumnos completa</b>.</p>
            <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                <div class="mb-3">
                    <label for="excelForms" class="form-label">Subir archivo Excel de Microsoft Forms:</label>
                    <input type="file" class="form-control form-control-lg" id="excelForms" name="excelForms"
                        accept=".xls,.xlsx" required>
                </div>
                <div class="mb-3">
                    <label for="excelAlumni" class="form-label">Subir archivo Excel de alumnos:</label>
                    <input type="file" class="form-control form-control-lg" id="excelAlumni" name="excelAlumni"
                        accept=".xls,.xlsx" required>
                </div>
                <button type="submit" class="btn btn-primary">Obtener alumnos faltantes</button>
            </form>
        </div>
        <div style="display:none">
            <br>
            <hr>
            <br>
            <h1>Alumnos faltantes</h1>
            <div>
                <h3>
                    Total de registros en BD:
                    <p id="totalDB"></p>
                </h3>
                <h3>
                    Listado de estudiantes filtrados:
                    <p id="totalFiltered"></p>
                </h3>
            </div>
            <table id="tableStudents" class="table">
                <thead>
                    <tr>
                        <th scope="col">Nombre Completo</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Área</th>
                        <th scope="col">Correo</th>
                    </tr>
                </thead>
                <tbody id="missingStudents">
                </tbody>
            </table>

            <div id="graphContainer">
                <h1 id="maestriaTitle">Gráfica para Maestrías</h1>
                <div id="maestriaGraph"></div>
                <h1 id="especialidadTitle">Gráfica para Especialidades</h1>
                <div id="especialidadGraph"></div>
            </div>
            
        </div>
    </main>

    <?php include INCLUDES_DIR . '/templates/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/popper.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/util.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/AFI/scripts.js"></script>
</body>

</html>