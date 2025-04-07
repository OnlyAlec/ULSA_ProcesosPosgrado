<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/utilities/responseHTTP.php";
require_once INCLUDES_DIR . "/models/student.php";

ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $res = false;

        if (isset($_POST['action'])) {
            require_once 'gestorStudents.php';

            switch ($_POST['action']) {
                case 'getTableStudents':
                    $res = array_values(array_map(fn ($student) => $student->getJSON(), getStudents()));
                    break;
                case 'getMissing':
                    //? Not in use
                    $res = showStudentsAFIByStatus('missing');
                    break;
                case 'getConfirm':
                    //? Not in use
                    $res = showStudentsAFIByStatus('confirm');
                    break;
                case 'setStatus':
                    $res = changeStatusAFI($_POST['ulsaID']);
                    break;
                case 'sendEmail':
                    $student = getStudentByUlsaID($_POST['ulsaID']);
                    $res = sendEmailRemainder($student);
                    break;
                case 'setConfigDate':
                    $res = updateConfig($_POST['type'], $_POST['date']);
                    break;
            }
        } elseif (count($_FILES) > 0) {
            require_once 'formsDB.php';
            require_once 'formsMultiple.php';
            $allowedExtensions = ['xls', 'xlsx'];
            $uploadDir = __DIR__ . '/uploads/';

            if (isset($_FILES['excelFile'])) {
                if ($_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Error uploading file.');
                }

                $fileTmpPath = $_FILES['excelFile']['tmp_name'];
                $fileName = str_replace(' ', '_', htmlspecialchars($_FILES['excelFile']['name'], ENT_QUOTES, 'UTF-8'));
                $ext = strtolower(pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, $allowedExtensions)) {
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            throw new RuntimeException('Error creating upload directory.');
                        }
                    }
                    if (!move_uploaded_file($fileTmpPath, "$uploadDir$fileName")) {
                        throw new RuntimeException('Error uploading file.');
                    }

                    $res = init_process("$uploadDir$fileName");
                }
            } elseif (isset($_FILES['excelForms']) && isset($_FILES['excelAlumni'])) {
                $fileTmpPath1 = $_FILES['excelForms']['tmp_name'];
                $fileTmpPath2 = $_FILES['excelAlumni']['tmp_name'];

                $fileName1 = str_replace(' ', '_', htmlspecialchars($_FILES['excelForms']['name'], ENT_QUOTES, 'UTF-8'));
                $fileName2 = str_replace(' ', '_', htmlspecialchars($_FILES['excelAlumni']['name'], ENT_QUOTES, 'UTF-8'));

                $ext1 = strtolower(pathinfo($_FILES['excelForms']['name'], PATHINFO_EXTENSION));
                $ext2 = strtolower(pathinfo($_FILES['excelAlumni']['name'], PATHINFO_EXTENSION));

                if (in_array($ext1, $allowedExtensions) && in_array($ext2, $allowedExtensions)) {
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            throw new RuntimeException('Error creating directory for XLSX files.');
                        }
                    }

                    if (!move_uploaded_file($fileTmpPath1, "$uploadDir$fileName1") || !move_uploaded_file($fileTmpPath2, "$uploadDir$fileName2")) {
                        throw new RuntimeException('Error uploading file.');
                    }

                    $res = process_multiple_excels($uploadDir, $fileName1, $fileName2);
                }
            }
        }

        if ($res === false || $res === null || empty($res)) {
            echo responseBadRequest('Error processing the request.');
            exit;
        }
        echo responseOK($res);
        exit;
    }
} catch (RuntimeException $e) {
    echo responseInternalError($e->getMessage());
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
            <div id="forms-msf" class="my-5 subSectionAFI" style="display: none;">
                <h4>Únicamente Microsoft Forms</h4>
                <p class="d-flex justify-content-end">
                    <b>Los datos sobreescribiran la base de datos.</b>
                </p>
                <form action="" method="post" enctype="multipart/form-data" class="form-box custom-file formsForm">
                    <div class="form-group row">
                        <label for="excelFile" class="col-md-3 col-form-label">Excel</label>
                        <div class="col-md-8 custom-file ml-2">
                            <input type="file" id="excelFile" name="excelFile" accept=".xls,.xlsx"
                                class="custom-file-input" required>
                            <label class="custom-file-label" for="customFile" data-browse="Examinar">
                                Seleccionar archivo...
                            </label>
                        </div>
                        <button type="submit" class="btn btn-outline-primary mt-2 mx-auto" style="width: 200px;">
                            <i class="fas fa-file-import mr-2"></i>
                            <span>Importar datos</span>
                        </button>
                    </div>
                </form>
            </div>
            <div id="forms-lst" class="my-5 subSectionAFI" style="display: none;">
                <h4>Lista de alumnos y Microsoft Forms</h4>
                <p class="d-flex justify-content-end">
                    <b>Los datos no modificaran la base de datos.</b>
                </p>
                <form action="" method="post" enctype="multipart/form-data" class="form-box custom-file formsForm">
                    <div class="form-group row">
                        <label for="excelForms" class="col-md-3 col-form-label">Excel Microsoft Forms</label>
                        <div class="col-md-8 custom-file ml-2">
                            <input type="file" id="excelForms" name="excelForms" accept=".xls,.xlsx"
                                class="custom-file-input" required>
                            <label class="custom-file-label" for="customFile" data-browse="Examinar">
                                Seleccionar archivo...
                            </label>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="excelAlumni" class="col-md-3 col-form-label">Excel Lista de Alumnos</label>
                        <div class="col-md-8 custom-file ml-2">
                            <input type="file" id="excelAlumni" name="excelAlumni" accept=".xls,.xlsx"
                                class="custom-file-input" required>
                            <label class="custom-file-label" for="customFile" data-browse="Examinar">
                                Seleccionar archivo...
                            </label>
                        </div>
                        <button type="submit" class="btn btn-outline-primary mt-2 mx-auto" style="width: 200px;">
                            <i class="fas fa-file-import mr-2"></i>
                            <span>Importar datos</span>
                        </button>
                    </div>
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
            <div class="form-box">
                <div class="form-group row">
                    <label for="selectMaster" class="col-md-3 col-form-label">Por maestría</label>
                    <div class="col-md-8 ml-2 datalist">
                        <input type="text" id="selectMaster" class="datalist-input w-100" placeholder="Seleccionar"
                            readonly>
                        <i class="fas fa-search icono filter"></i>
                        <ul style="display: none;">
                            <?php
                    foreach (getMastersPrograms() as $master) {
                        $master = ucfirst(strtolower($master->getName()));
                        echo "<option value='$master'>$master</option>";
                    } ?>
                        </ul>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="selectSpecialty" class="col-md-3 col-form-label">Por especialidad:</label>
                    <div class="col-md-8 ml-2 datalist">
                        <input type="text" id="selectSpecialty" class="datalist-input w-100" placeholder="Seleccionar"
                            readonly>
                        <i class="fas fa-search icono filter"></i>
                        <ul style="display: none;">
                            <?php
                    foreach (getSpecialtyPrograms() as $special) {
                        $special = ucfirst(strtolower($special->getName()));
                        echo "<option value='$special'>$special</option>";
                    } ?>
                        </ul>
                    </div>
                </div>
            </div>
            <table id="tableStudents" class="table table-white table-nostriped">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">Clave</th>
                        <th scope="col">Nombre Completo</th>
                        <th scope="col">Programa</th>
                        <th scope="col">Correo</th>
                    </tr>
                </thead>
                <tbody>
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
            <div class="form-box">
                <div class="form-group row">
                    <label for="selectMasterConfirm" class="col-md-3 col-form-label">Por maestría</label>
                    <div class="col-md-8 ml-2 datalist">
                        <input type="text" id="selectMasterConfirm" class="datalist-input w-100"
                            placeholder="Seleccionar" readonly>
                        <i class="fas fa-search icono filter"></i>
                        <ul style="display: none;">
                            <?php
                    foreach (getMastersPrograms() as $master) {
                        $master = ucfirst(strtolower($master->getName()));
                        echo "<option value='$master'>$master</option>";
                    } ?>
                        </ul>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="selectSpecialtyConfirm" class="col-md-3 col-form-label">Por especialidad:</label>
                    <div class="col-md-8 ml-2 datalist">
                        <input type="text" class="datalist-input w-100" id="selectSpecialtyConfirm"
                            placeholder="Seleccionar" readonly>
                        <i class="fas fa-search icono filter"></i>
                        <ul style="display: none;">
                            <?php
                    foreach (getSpecialtyPrograms() as $special) {
                        $special = ucfirst(strtolower($special->getName()));
                        echo "<option value='$special'>$special</option>";
                    } ?>
                        </ul>
                    </div>
                </div>
                <div class="form-group row justify-content-center mt-3">
                    <button id="removeFilter" class="btn btn-outline-success mr-2" style="width: 230px;">
                        <i class="fas fa-users"></i> Todos
                    </button>
                    <button id="onlyConfirm" class="btn btn-outline-primary mr-2" style="width: 230px;">
                        <i class="fas fa-check-double"></i> Solamente confirmados
                    </button>
                    <button id="onlyMissing" class="btn btn-outline-danger" style="width: 230px;">
                        <i class="fas fa-times-circle"></i> Solamente faltantes
                    </button>
                </div>
            </div>
            <table id="tableStudentsConfirm" class="table table-white table-nostriped">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">Clave</th>
                        <th scope="col">Nombre Completo</th>
                        <th scope="col">Programa</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <!-- Config -->
        <div id="config" class="sectionAFI" style="display:none;">
            <h3>Configuración de fechas:</h3>
            <p>
                A continuación se podrá configurar las fechas de cada cuatrimestre para el inicio del proceso, cuando
                llegue el dia indicado se
                <b>reiniciaran los indicadores de los alumnos</b>.
            </p>
            <div class="col-md-12 bg-info rounded p-4 mb-3">
                <form action="" method="post" class="row justify-content-around dateForm" data-type="dateFirstAFI">
                    <div class="col-md-10">
                        <h3>Primer cuatrimestre:</h3>
                        <input class="form-control date" type="text" placeholder="Selecciona una fecha"
                            data-set="<?= getConfig("dateFirstAFI") ?? '' ?>">
                    </div>
                    <button type="submit" class="col-md-1 btn btn-success text-white">
                        <i class="fas fa-save fa-2x"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-12 bg-info rounded p-4 mb-3">
                <form action="" method="post" class="row justify-content-around dateForm" data-type="dateSecondAFI">
                    <div class="col-md-10">
                        <h3>Segundo cuatrimestre:</h3>
                        <input class="form-control date" type="text" placeholder="Selecciona una fecha"
                            data-set="<?= getConfig("dateSecondAFI") ?? '' ?>">
                    </div>
                    <button type="submit" class="col-md-1 btn btn-success text-white">
                        <i class="fas fa-save fa-2x"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-12 bg-info rounded p-4 mb-3">
                <form action="" method="post" class="row justify-content-around dateForm" data-type="dateThirdAFI">
                    <div class="col-md-10">
                        <h3>Tercer cuatrimestre:</h3>
                        <input class="form-control date" type="text" placeholder="Selecciona una fecha"
                            data-set="<?= getConfig("dateThirdAFI") ?? '' ?>">
                    </div>
                    <button type="submit" class="col-md-1 btn btn-success text-white">
                        <i class="fas fa-save fa-2x"></i>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <?php include INCLUDES_DIR . '/templates/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/jquery-ui.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/datepicker-es.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/AFI/scripts.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/AFI/forms.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/AFI/gestor.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/AFI/settings.js"></script>
</body>

</html>