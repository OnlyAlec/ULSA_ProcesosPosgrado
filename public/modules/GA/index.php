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
                mkdir($uploadDir, 0777, true);
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

<style>
    .btn-group .btn {
        flex-grow: 1;
    }
</style>

<body style="display: block;">
    <?php require_once INCLUDES_DIR . '/templates/header.php';
get_header("Gestión de Alumnos");
?>

    <main class="container content marco">
        <div>
            <div class="d-flex justify-content-end align-items-center">
                <a href="<?=$_SERVER['DOCUMENT_ROOT']?>" class="btn btn-outline-primary">Regresar</a>
            </div>
            <br>
            <br>
            <div class="btn-group d-flex mb-4" role="group" id="button-group">
                <button class="btn btn-dark flex-grow-1"    id="btn-crear" onclick="showSection('crear')">Registrar Alumnos</button>
                <button class="btn btn-primary flex-grow-1" id="btn-consultar" onclick="showSection('consultar')">Consultar Alumnos</button>
                <button class="btn btn-primary flex-grow-1" id="btn-eliminar" onclick="showSection('eliminar')" type="submit">Eliminar Alumnos</button>
            </div>
            
            <div id="section-crear" class="section">
                <h2>Carga de concentrado de alumnos en Excel:</h2>
                <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                    <input type="hidden" name="action" value="registerFromExcel">
                    <div class="mb-3">
                        <h4 for="gaExcelFile" class="form-label">Subir archivo Excel:</h4>
                        <input type="file" class="form-control form-control-lg w-100 pb-5 pl-2" id="gaExcelFile" name="gaExcelFile" accept=".xls,.xlsx" required>
                        <div id="emailHelp" class="form-text d-flex justify-content-end">Se sobreescribirá la base de datos.</div>
                    </div>
                    <div class="d-flex align-items-center">
                        <h4>Encabezados</h4>
                        <div class="fs-6 text-muted ml-2 mb-1">(ej: A, B, C, ...)</div>
                    </div>
                    <br>

                    <div class="mb-3">
                        <div class="row align-items-center mb-2">
                            <div class="col-6 d-flex">
                                <label class="col-4" for="claveUlsaCol">Clave Ulsa:</label>
                                <input type="text" class="col-6 form-control w-auto" id="claveUlsaCol" name="claveUlsaCol" placeholder="Columna" maxlength="1">
                            </div>
                            <div class="col-6 d-flex">
                                <label class="col-4" for="nombreCol">Nombre(s):</label>
                                <input type="text" class="col-6 form-control w-auto" id="nombreCol" name="nombreCol" placeholder="Columna" maxlength="1">
                            </div>
                        </div>
                        <div class="row align-items-center mb-2">
                            <div class="col-6 d-flex">
                                <label class="col-4" for="apellidosCol">Apellidos:</label>
                                <input type="text" class="col-6 form-control w-auto" id="apellidosCol" name="apellidosCol" placeholder="Columna" maxlength="1">
                            </div>
                            <div class="col-6 d-flex">
                                <label class="col-4" for="carreraCol">Carrera:</label>
                                <input type="text" class="col-6 form-control w-auto" id="carreraCol" name="carreraCol" placeholder="Columna" maxlength="1">
                            </div>
                        </div>
                        <div class="row align-items-center mb-2">
                            <div class="col-6 d-flex">
                                <label class="col-4" for="emailCol">Email:</label>
                                <input type="text" class="col-6 form-control w-auto" id="emailCol" name="emailCol" placeholder="Columna" maxlength="1">
                            </div>
                        </div>
                    </div>


                    <br>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Cargar Excel</button>
                    </div>
                </form>
                
                <br>
                <hr>

                <h2>Registro único de alumno:</h2>
                <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                    <input type="hidden" name="action" value="registerOneStudent">

                    <div class="d-flex align-items-center">
                        <h4>Datos del alumno</h4>
                        <div class="fs-6 text-muted ml-2 mb-1">(no utilizar "al" en la Clave Ulsa )</div>
                    </div>
                    <br>

                    <div class="mb-3">
                        <div class="row align-items-center mb-2">
                            <div class="col-6 d-flex">
                                <label class="col-4" for="claveUlsa">Clave Ulsa:</label>
                                <input type="text" class="col-6 form-control w-auto" id="claveUlsa" name="claveUlsa" placeholder="Clave Ulsa">
                            </div>
                            <div class="col-6 d-flex">
                                <label class="col-4" for="nombre">Nombre(s):</label>
                                <input type="text" class="col-6 form-control w-auto" id="nombre" name="nombre" placeholder="Nombre(s)">
                            </div>
                        </div>
                        <div class="row align-items-center mb-2">
                            <div class="col-6 d-flex">
                                <label class="col-4" for="apellidos">Apellidos:</label>
                                <input type="text" class="col-6 form-control w-auto" id="apellidos" name="apellidos" placeholder="Apellidos">
                            </div>
                            <div class="col-6 d-flex">
                                <label class="col-4" for="carrera">Carrera:</label>
                                <input type="text" class="col-6 form-control w-auto" id="carrera" name="carrera" placeholder="Carrera">
                            </div>
                        </div>
                        <div class="row align-items-center mb-2">
                            <div class="col-6 d-flex">
                                <label class="col-4" for="email">Email:</label>
                                <input type="text" class="col-6 form-control w-auto" id="email" name="email" placeholder="Correo electrónico">
                            </div>
                        </div>
                    </div>


                    <br>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Registrar alumno</button>
                    </div>
                </form>
            </div>

            <div id="section-consultar" class="section d-none">
                <h2>Consultar Alumnos</h2>
                <table id="tableStudents" class="table">
                <thead>
                        <tr>
                            <th scope="col">Clave ULSA</th>
                            <th scope="col">Nombre Completo</th>
                            <th scope="col">Programa</th>
                            <th scope="col">Correo</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>

            <div id="section-eliminar" class="section d-none">

                <h2>Borrado único de alumno:</h2>
                <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                    <input type="hidden" name="action" value="deleteOneStudent">
                    <div class="d-flex align-items-center">
                        <h4>Clave Ulsa del Alumno</h4>
                        <div class="fs-6 text-muted ml-2 mb-1">(no utilizar "al" en la Clave Ulsa )</div>
                    </div>
                    <br>

                    <div class="mb-3">
                        <div class="row align-items-center mb-2">
                            <div class="col-6 d-flex">
                                <label class="col-4" for="claveUlsaDelete">Clave Ulsa:</label>
                                <input type="text" class="col-6 form-control w-auto" id="claveUlsaDelete" name="claveUlsaDelete" placeholder="Clave Ulsa" maxlength="6">
                            </div>
                    </div>

                    <br>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Eliminar alumno</button>
                    </div>
                </form>

                <br>
                <hr>

                <h2>Borrado de todos los alumnos</h2>
                <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                    <input type="hidden" name="action" value="deleteAllStudents">
                    <br>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-danger">Eliminar alumnos</button>
                    </div>
                </form>

            </div>
            
        </div>
    </main>

    <?php include INCLUDES_DIR . '/templates/footer.php'; ?>

    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/popper.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/util.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/GA/scripts.js"></script>
</body>

</html>
