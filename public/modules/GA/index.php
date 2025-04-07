<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/utilities/responseHTTP.php";
require_once INCLUDES_DIR . "/models/student.php";
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once 'manage_users.php';

        $allowedExtensions = ['xls', 'xlsx'];
        $regex = '/^[A-Za-z]$/';
        $uploadDir = __DIR__ . '/uploads/';

        if ($_POST["action"] === "registerFromExcel" && isset($_FILES['gaExcelFile'])) {
            if ($_FILES['gaExcelFile']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error uploading file.');
            }

            $fileTmpPath = $_FILES['gaExcelFile']['tmp_name'];
            $fileName = str_replace(' ', '_', htmlspecialchars($_FILES['gaExcelFile']['name'], ENT_QUOTES, 'UTF-8'));
            $ext = strtolower(pathinfo($_FILES['gaExcelFile']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions)) {
                throw new RuntimeException('Invalid file type.');
            }

            if (!preg_match($regex, $_POST["claveUlsaCol"]) || !preg_match($regex, $_POST["nombreCol"]) || !preg_match($regex, $_POST["apellidosCol"]) || !preg_match($regex, $_POST["carreraCol"]) || !preg_match($regex, $_POST["emailCol"])) {
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

            $res = restartDatabaseFromExcel("$uploadDir$fileName", $_POST["claveUlsaCol"], $_POST["nombreCol"], $_POST["apellidosCol"], $_POST["carreraCol"], $_POST["emailCol"]);

        } elseif ($_POST["action"] === "registerOneStudent") {

            if (!preg_match('/^\d{6}$/', $_POST["claveUlsa"])) {
                throw new RuntimeException('Clave ULSA invalida. Debe ser un numero de 6 digitos.');
            }
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $_POST["nombre"])) {
                throw new RuntimeException('Nombre invalido. Solo se permiten letras y espacios.');
            }
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $_POST["apellidos"])) {
                throw new RuntimeException('Apellidos invalidos. Solo se permiten letras y espacios.');
            }
            if (empty($_POST["carrera"])) {
                throw new RuntimeException('Carrera no puede estar vacia.');
            }
            if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Correo electronico invalido.');
            }

            $res = insertOneStudent($_POST["claveUlsa"], $_POST["nombre"], $_POST["apellidos"], $_POST["carrera"], $_POST["email"]);

        } elseif ($_POST["action"] === "getTableStudents") {
            $res = array_values(array_map(fn ($student) => $student->getJSON(), getStudents()));
        } elseif ($_POST["action"] === "deleteOneStudent") {
            if (!preg_match('/^\d{6}$/', $_POST["claveUlsaDelete"])) {
                throw new RuntimeException('Clave ULSA invalida. Debe ser un numero de 6 digitos.');
            }

            $res = deleteOneStudent($_POST["claveUlsaDelete"]);

        } elseif ($_POST["action"] === "deleteAllStudents") {
            $res = deleteAllStudents();
        }

        echo responseOK($res);
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
get_head("GA");
?>

<body style="display: block;">
    <?php require_once INCLUDES_DIR . '/templates/header.php';
get_header("Gestión de Alumnos");
?>

    <main class="container content marco">
        <!-- Botones Nav -->
        <div class="sectionsGA row mb-3">
            <button id="btn-crear" class="col btn btn-outline-primary mr-3 p-4">
                <span>Registrar Alumnos</span>
            </button>
            <button id="btn-consultar" class="col btn btn-outline-primary mr-3 p-4">
                <span>Consultar Alumnos</span>
            </button>
            <button id="btn-eliminar" class="col btn btn-outline-primary p-4">
                <span>Eliminar Alumnos</span>
            </button>
        </div>

        <div>         
            <div id="crear" class="my-5 sectionGA" style="display: none;">
                <h3>Registro de Alumnos desde Excel</h3>
                <p class="d-flex justify-content-end">
                    <b>Se sobreescribirá la base de datos.</b>
                </p>

                <form action="" method="post" enctype="multipart/form-data" class="form-box">
                    <input type="hidden" name="action" value="registerFromExcel">

                    <!-- Archivo Excel -->
                    <div class="form-group row mb-4">
                        <label for="gaExcelFile" class="col-md-3 col-form-label">Archivo Excel</label>
                        <div class="col-md-8 custom-file ml-2">
                            <input type="file" class="custom-file-input" id="gaExcelFile" name="gaExcelFile" accept=".xls,.xlsx" required>
                            <label class="custom-file-label" for="gaExcelFile" data-browse="Examinar">
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
                        <label for="claveUlsaCol" class="col-md-3 col-form-label">Clave Ulsa:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="claveUlsaCol" name="claveUlsaCol" placeholder="Columna" maxlength="1">
                        </div>
                    </div>
                    <div class="form-group row mb-4">
                        <label for="nombreCol" class="col-md-3 col-form-label">Nombre(s):</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="nombreCol" name="nombreCol" placeholder="Columna" maxlength="1">
                        </div>
                    </div>
                    <div class="form-group row mb-4">
                        <label for="apellidosCol" class="col-md-3 col-form-label">Apellidos:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="apellidosCol" name="apellidosCol" placeholder="Columna" maxlength="1">
                        </div>
                    </div>
                    <div class="form-group row mb-4">
                        <label for="carreraCol" class="col-md-3 col-form-label">Carrera:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="carreraCol" name="carreraCol" placeholder="Columna" maxlength="1">
                        </div>
                    </div>
                    <div class="form-group row mb-4">
                        <label for="emailCol" class="col-md-3 col-form-label">Email:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="emailCol" name="emailCol" placeholder="Columna" maxlength="1">
                        </div>
                    </div>

                    <!-- Botón de envío -->
                    <div class="text-center mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-primary" style="width: 200px;">
                            <i class="fas fa-file-import mr-2"></i>
                            <span>Cargar Excel</span>
                        </button>
                    </div>
                </form>

                <hr>

                <h3>Registro de alumno único</h3>
                <form action="" method="post" enctype="multipart/form-data" class="form-box">
                    <input type="hidden" name="action" value="registerOneStudent">

                    <div class="d-flex align-items-center">
                        <h4>Datos del alumno</h4>
                        <div class="fs-6 text-muted ml-2 mb-1">(no utilizar "al" en la Clave Ulsa )</div>
                    </div>
                    <br>

                    <div class="form-group row mb-4">
                        <label for="claveUlsa" class="col-md-3 col-form-label">Clave Ulsa:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="claveUlsa" name="claveUlsa" placeholder="Clave Ulsa">
                        </div>
                    </div>

                    <div class="form-group row mb-4">
                        <label for="nombre" class="col-md-3 col-form-label">Nombre(s):</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="nombre" name="nombre" placeholder="Nombre(s)">
                        </div>
                    </div>

                    <div class="form-group row mb-4">
                        <label for="apellidos" class="col-md-3 col-form-label">Apellidos:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="apellidos" name="apellidos" placeholder="Apellidos">
                        </div>
                    </div>

                    <div class="form-group row mb-4">
                        <label for="carrera" class="col-md-3 col-form-label">Carrera:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="carrera" name="carrera" placeholder="Carrera">
                        </div>
                    </div>

                    <div class="form-group row mb-4">
                        <label for="email" class="col-md-3 col-form-label">Email:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="email" name="email" placeholder="Correo electrónico">
                        </div>
                    </div>


                    <br>
                    <div class="text-center mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-primary" style="width: 200px;">
                            <i class="fas fa-user-plus mr-2"></i>
                            <span>Registrar alumno</span>
                        </button>
                    </div>
                </form>
            </div>

            <div id="consultar" class="my-5 sectionGA" style="display: none;">
                <h3>Consulta de Alumnos</h3>
                <br>
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
            </div>

            <div id="eliminar" class="my-5 sectionGA" style="display: none;">

                <div class="d-flex align-items-center">
                    <h3>Borrado de alumno único</h3>
                    <div class="fs-6 text-muted ml-2 mb-1">(no utilizar "al")</div>
                </div>

                <form action="" method="post" enctype="multipart/form-data" class="mt-4 form-box">
                    <input type="hidden" name="action" value="deleteOneStudent">
                    <div class="form-group row mb-4">
                        <label for="claveUlsaDelete" class="col-md-3 col-form-label">Clave Ulsa:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="claveUlsaDelete" name="claveUlsaDelete" placeholder="Clave Ulsa" maxlength="6">
                        </div>
                    </div>
                    <div class="text-center mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-primary" style="width: 200px;">
                            <i class="fas fa-user-minus mr-2"></i>
                            <span>Eliminar alumno</span>
                        </button>
                    </div>
                </form>

                <br>
                <hr>

                <h3>Borrado de todos los alumnos</h3>
                <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                    <input type="hidden" name="action" value="deleteAllStudents">
                    <br>
                    <div class="text-center mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-danger" style="width: 200px;">
                            <i class="fas fa-trash mr-2"></i>
                            <span>Eliminar alumnos</span>
                        </button>
                    </div>
                </form>

            </div>
            
        </div>
    </main>

    <?php include INCLUDES_DIR . '/templates/footer.php'; ?>

    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/util.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/GA/scripts.js"></script>
</body>

</html>
