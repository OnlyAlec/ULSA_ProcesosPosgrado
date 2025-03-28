<?php
require_once '../../../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once './db_excel.php';
        require_once './all_excel.php';

        $allowedExtensions = ['xls', 'xlsx'];
        $uploadDir = __DIR__ . '/uploads/';

        if (isset($_FILES['excelFile'])) {
            if ($_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error uploading file.');
            }

            $fileTmpPath = $_FILES['excelFile']['tmp_name'];
            $fileName = str_replace(' ', '_', htmlspecialchars($_FILES['excelFile']['name'], ENT_QUOTES, 'UTF-8'));
            $ext = strtolower(pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions)) {
                throw new RuntimeException('Invalid file type.');
            }

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (!move_uploaded_file($fileTmpPath, "$uploadDir$fileName")) {
                throw new RuntimeException('Error uploading file.');
            }

            $res = init_process("$uploadDir$fileName");
        } elseif (isset($_FILES['excelForms']) && isset($_FILES['excelAlumni'])) {
            $fileTmpPath1 = $_FILES['excelForms']['tmp_name'];
            $fileTmpPath2 = $_FILES['excelAlumni']['tmp_name'];

            $fileName1 = str_replace(' ', '_', htmlspecialchars($_FILES['excelForms']['name'], ENT_QUOTES, 'UTF-8'));
            $fileName2 = str_replace(' ', '_', htmlspecialchars($_FILES['excelAlumni']['name'], ENT_QUOTES, 'UTF-8'));

            $ext1 = strtolower(pathinfo($_FILES['excelForms']['name'], PATHINFO_EXTENSION));
            $ext2 = strtolower(pathinfo($_FILES['excelAlumni']['name'], PATHINFO_EXTENSION));

            if (in_array($ext1, $allowedExtensions) && in_array($ext2, $allowedExtensions)) {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                if (!move_uploaded_file($fileTmpPath1, "$uploadDir$fileName1") || !move_uploaded_file($fileTmpPath2, "$uploadDir$fileName2")) {
                    throw new RuntimeException('Error uploading file.');
                }

                $res = process_multiple_excels($uploadDir, $fileName1, $fileName2);
            } else {
                throw new RuntimeException('Invalid file type.');
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'showMissingStudentsAFI') {
            require_once './gestor.php';
            $res = showMissingStudentsAFI();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'updateStatusAFI') {
            // TODO: Updated
        } elseif (isset($_POST['action']) && $_POST['action'] === 'sendEmailAFI') {
            // TODO: Implementation of Brevo
        }

        // FIXME: Pass to response header
        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
    }
} catch (RuntimeException $e) {
    // FIXME: Use method
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
        <!-- Botones Nav -->
        <div class="sectionsAFI row mb-3">
            <button id="btn-forms" class="col btn btn-outline-primary mr-3 p-4">
                <span>Importar desde Microsoft Forms</span>
            </button>
            <button id="btn-gestor" class="col btn btn-outline-primary mr-3 p-4">
                <span>Gestión manual</span>
            </button>
            <button id="btn-config" class="col btn btn-outline-primary p-4">
                <span>Configuración de fechas</span>
            </button>
        </div>
        <!-- Forms -->
        <div id="forms" class="sectionAFI" style="display: none;">
            <h3>Subir archivo de Excel:</h3>
            <p>El sistema ofrece 2 opciones para poder importar la confirmación de los alumnos a GPP.</p>
            <ul>
                <li>
                    Importar <b>únicamente</b> el archivo Excel generado por Microsoft Forms.
                </li>
                <li>
                    Importar <b>2 archivos de Excel</b>, la lista completa de alumnos y el archivo Excel generado por
                    Microsoft
                    Forms.
                </li>
            </ul>
            <p>Una vez realizada la importación, se le mostrara una tabla de los <b>alumnos sin confirmar</b> el aviso
                de fechas importantes.</p>
            <div class="row justify-content-around">
                <button id="btn-forms-msf" class="col-5 btn btn-danger p-5">
                    <i class="fab fa-wpforms fa-2x mb-2"></i>
                    <h4>Únicamente Microsoft Forms</h4>
                </button>
                <button id="btn-forms-lst" class="col-5  btn btn-danger p-5">
                    <i class="fas fa-copy fa-2x mb-2"></i>
                    <h4>Lista de Alumnos y Microsoft Forms</h4>
                </button>
            </div>
            <div id="forms-msf" class="subSectionAFI mb-5" style="display: none;">
                <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                    <div class="mb-3">
                        <input type="file" class="form-control form-control-lg pb-5" id="excelFile" name="excelFile"
                            accept=".xls,.xlsx" required>
                        <div id="emailHelp" class="form-text">Los alumnos confirmados se guardaran en la base de datos.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-import mr-2"></i>
                        <span>Importar confirmaciones</span>
                    </button>
                </form>
            </div>
            <div id="forms-lst" class="subSectionAFI mb-5" style="display: none;">
                <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                    <div class="mb-3">
                        <label for="excelForms" class="form-label">
                            <b>Excel de Microsoft Forms:</b>
                        </label>
                        <input type="file" class="form-control form-control-lg pb-5" id="excelForms" name="excelForms"
                            accept=".xls,.xlsx" required>
                    </div>
                    <div class="mb-3">
                        <label for="excelAlumni" class="form-label">
                            <b>Lista de alumnos:</b>
                        </label>
                        <input type="file" class="form-control form-control-lg pb-5" id="excelAlumni" name="excelAlumni"
                            accept=".xls,.xlsx" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-import mr-2"></i>
                        <span>Importar confirmaciones</span>
                    </button>
                </form>
            </div>
        </div>
        <div id="forms-result" class="sectionAFI" style="display:none">
            <hr class="my-4">
            <h1>Alumnos faltantes de confirmar AFI:</h1>
            <div class="row justify-content-around mb-4">
                <div class="col-md-5 bg-secondary text-white p-3 rounded mb-2 mb-md-0">
                    <div class="d-flex align-items-center h-100">
                        <div class="mr-3"><i class="fas fa-database fa-2x"></i></div>
                        <div>
                            <h5 class="mb-1">Total de alumnos en la base de datos:</h5>
                            <p id="totalDB" class="h3 mb-0 font-weight-bold"></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-5 bg-secondary text-white p-3 rounded mb-2 mb-md-0">
                    <div class="d-flex align-items-center h-100">
                        <div class="mr-3"><i class="fas fa-filter fa-2x"></i></div>
                        <div>
                            <h5 class="mb-1">Estudiantes filtrados:</h5>
                            <p id="totalFiltered" class="h3 mb-0 font-weight-bold"></p>
                        </div>
                    </div>
                </div>

                <a href="#" id="downloadExcel"
                    class="col-md-1 bg-success text-white p-3 rounded d-flex justify-content-center align-items-center"
                    style="display: none !important;">
                    <i class="fas fa-file-excel fa-2x"></i>
                </a>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label for="selectMaster">Filtrar por maestría:</label> <br>
                    <select name="filter" id="selectMaster" class="custom-select">
                        <option selected value="all"></option>
                        <?php
                        foreach (getMastersPrograms() as $master) {
                            $master = ucfirst(strtolower($master));
                            echo "<option value='$master'>$master</option>";
                        } ?>
                    </select>
                </div>
                <div class="col">
                    <label for="selectSpecialty">Filtrar por especialidad:</label>
                    <select name="filter" id="selectSpecialty" class="custom-select">
                        <option selected value="all"></option>
                        <?php
                        foreach (getSpecialtyPrograms() as $special) {
                            $special = ucfirst(strtolower($special));
                            echo "<option value='$special'>$special</option>";
                        } ?>
                    </select>
                </div>
            </div>
            <table id="tableStudents" class="table">
                <thead>
                    <tr>
                        <th scope="col">Clave ULSA</th>
                        <th scope="col">Nombre Completo</th>
                        <th scope="col">Programa</th>
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

        <!-- Gestión -->
        <div id="gestor" class="sectionAFI" style="display:none;">
            <h3>Gestión de confirmación de Alumnos:</h3>
            <p>A continuación se mostraran todos los <b>alumnos faltantes de confirmar el AFI</b>, donde se podrá
                indicar en el
                sistema que ya confirmaron pero por alguna cuestión <b>no se pudo confirmar</b> en el Forms o directo en
                el sistema.
            </p>
            <p>
                <b>Evita el marcado manual</b> de la confirmación del alumno, como alternativa puedes mandar un
                <b>recordatorio por correo electrónico</b>.
            </p>
            <div class="row my-3">
                <div class="col">
                    <label for="selectMaster">Filtrar por maestría:</label> <br>
                    <select name="filter" id="selectMasterConfirm" class="custom-select">
                        <option selected value="all"></option>
                        <?php
                        foreach (getMastersPrograms() as $master) {
                            $master = ucfirst(strtolower($master));
                            echo "<option value='$master'>$master</option>";
                        } ?>
                    </select>
                </div>
                <div class="col">
                    <label for="selectSpecialty">Filtrar por especialidad:</label>
                    <select name="filter" id="selectSpecialtyConfirm" class="custom-select">
                        <option selected value="all"></option>
                        <?php
                        foreach (getSpecialtyPrograms() as $special) {
                            $special = ucfirst(strtolower($special));
                            echo "<option value='$special'>$special</option>";
                        } ?>
                    </select>
                </div>
            </div>
            <table id="tableStudentsConfirm" class="table">
                <thead>
                    <tr>
                        <th scope="col">Clave ULSA</th>
                        <th scope="col">Nombre Completo</th>
                        <th scope="col">Programa</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody id="missingStudentsConfirm">
                </tbody>
            </table>
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