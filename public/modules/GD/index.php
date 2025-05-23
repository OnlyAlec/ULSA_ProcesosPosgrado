<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/utilities/responseHTTP.php";
require_once INCLUDES_DIR . "/models/professor.php";
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once 'manage_professors.php';

        $allowedExtensions = ['xls', 'xlsx'];
        $regex = '/^[A-Za-z]$/';
        $uploadDir = __DIR__ . '/uploads/';

        if ($_POST["action"] === "registerFromExcel" && isset($_FILES['gdExcelFile'])) {
            if ($_FILES['gdExcelFile']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error uploading file.');
            }

            $fileTmpPath = $_FILES['gdExcelFile']['tmp_name'];
            $fileName = str_replace(' ', '_', htmlspecialchars($_FILES['gdExcelFile']['name'], ENT_QUOTES, 'UTF-8'));
            $ext = strtolower(pathinfo($_FILES['gdExcelFile']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions)) {
                throw new RuntimeException('Invalid file type.');
            }
            if (!preg_match($regex, $_POST["claveUlsaCol"]) || !preg_match($regex, $_POST["nombreCol"]) || !preg_match($regex, $_POST["apellidosCol"]) || !preg_match($regex, $_POST["emailCol"])) {
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

        } elseif ($_POST["action"] === "registerOneProfessor") {
            if (!preg_match('/^\d{6}$/', $_POST["claveUlsa"])) {
                throw new RuntimeException('Clave ULSA invalida. Debe ser un numero de 6 digitos.');
            }
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $_POST["nombre"])) {
                throw new RuntimeException('Nombre invalido. Solo se permiten letras y espacios.');
            }
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $_POST["apellidos"])) {
                throw new RuntimeException('Apellidos invalidos. Solo se permiten letras y espacios.');
            }
            if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Correo electronico invalido.');
            }
            $res = insertOneProfessor($_POST["claveUlsa"], $_POST["nombre"], $_POST["apellidos"], $_POST["email"]);

        } elseif ($_POST["action"] === "getTableProfessor") {
            $res = array_values(array_map(fn ($professor) => $professor->getJSON(), getProfessors()));
        
        } elseif ($_POST["action"] === "deleteOneProfessor") {
            if (!preg_match('/^\d{6}$/', $_POST["claveUlsaDelete"])) {
                throw new RuntimeException('Clave ULSA invalida. Debe ser un numero de 6 digitos.');
            }

            $res = deleteProfessorByUlsaId($_POST["claveUlsaDelete"]);

        } elseif ($_POST["action"] === "deleteAllProfessors") {
            $res = deleteAllProfessors();

        }
        elseif ($_POST["action"] === "getProfessorDetails") {
            if (!preg_match('/^\d{6}$/', $_POST["ulsaID"])) {
                throw new RuntimeException('Clave ULSA invalida. Debe ser un numero de 6 digitos.');
            }
            $res = getProfessorSubjectsAndProgramsByUlsaID($_POST["ulsaID"]);

        } elseif ($_POST["action"] === "deleteProgramSubject") {
            if (!isset($_POST['professorId']) || !isset($_POST['subjectId']) || !isset($_POST['programId'])) {
                throw new RuntimeException('Faltan datos para eliminar la materia del programa.');
            }
            $res = deleteProgramSubject ($_POST["professorId"], $_POST["subjectId"], $_POST["programId"]);
        
        } elseif ($_POST["action"] === "deleteProgramSubject") {
            if (!isset($_POST['professorId']) || !isset($_POST['subjectId']) || !isset($_POST['programId'])) {
                throw new RuntimeException('Faltan datos para eliminar la materia del programa.');
            }
            $res = deleteProgramSubject ($_POST["professorId"], $_POST["subjectId"], $_POST["programId"]);
        
        } elseif ($_POST["action"] === "addProgramSubject") {
            if (!isset($_POST['professorId']) || !isset($_POST['subjectId']) || !isset($_POST['programId'])) {
                throw new RuntimeException('Faltan datos para asignar la materia al programa.');
            }
            $inserted = addProgramSubject(
                (int) $_POST["professorId"],
                (int) $_POST["subjectId"],
                (int) $_POST["programId"]
            );

            if (!$inserted) {
                throw new RuntimeException('No se pudo asignar la materia al programa.');
            }

            $subject = getSubjectByID((int) $_POST["subjectId"]);
            $program = getProgramByID((int) $_POST["programId"]);

            $res = [
                'subject' => $subject->toArray(),
                'program' => $program->toArray(),
            ];

        } elseif ($_POST["action"] === "getSubjects") {
            $res = getSubjects();
            $res = array_map(fn ($subject) => $subject->toArray(), $res);

        } elseif ($_POST["action"] === "getPrograms") {
            $res = getPrograms();
            $res = array_map(fn ($program) => $program->toArray(), $res);
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
get_head("GD");
?>

<style>
    .new-assignment {
        display: flex;
        flex-direction: column;
        gap: 15px;
        background-color: #f8fafc;
        padding: 1.25rem;
        border-radius: 12px;
        margin: 0.75rem 0;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .new-assignment .select-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .new-assignment select.form-select {
        padding: 0.625rem 1rem;
        font-size: 0.925rem;
        border-radius: 8px;
        border: 1px solid #ced4da;
        transition: all 0.2s ease;
        background-color: #fff;
    }

    .new-assignment select.form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13,110,253,.15);
    }

    .new-assignment .button-container {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;      /* <- espacio entre botones */
        margin-top: 5px;
    }

    .new-assignment .btn-ok,
    .new-assignment .btn-cancel {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .new-assignment .btn-ok:hover {
        transform: translateY(-1px);
    }

    .new-assignment .form-label {
        font-size: 0.9rem;
        color: #4a5568;
    }
</style>

<body style="display: block;">
    
    <?php require_once INCLUDES_DIR . '/templates/header.php';
get_header("Gestión de Profesores");
?>

    <main class="container content marco">
        <!-- Botones Nav -->
        <div class="sectionsGD row mb-3">
            <button id="btn-crear" class="col btn btn-outline-primary mr-3 p-4">
                <span>Registrar Profesores</span>
            </button>
            <button id="btn-consultar" class="col btn btn-outline-primary mr-3 p-4">
                <span>Consultar Profesores</span>
            </button>
            <button id="btn-eliminar" class="col btn btn-outline-primary p-4">
                <span>Eliminar Profesores</span>
            </button>
        </div>

        <div>         
            <div id="crear" class="my-5 sectionGD" style="display: none;">
                <h3>Registro de Profesores desde Excel</h3>
                <p class="d-flex justify-content-end">
                    <b>Se sobreescribirá los profesores.</b>
                </p>

                <form action="" method="post" enctype="multipart/form-data" class="form-box">
                    <input type="hidden" name="action" value="registerFromExcel">

                    <!-- Archivo Excel -->
                    <div class="form-group row mb-4">
                        <label for="gdExcelFile" class="col-md-3 col-form-label">Archivo Excel</label>
                        <div class="col-md-8 custom-file ml-2">
                            <input type="file" class="custom-file-input" id="gdExcelFile" name="gdExcelFile" accept=".xls,.xlsx" required>
                            <label class="custom-file-label" for="gdExcelFile" data-browse="Examinar">
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

                <h3>Registro de Profesor Único</h3>
                <form action="" method="post" enctype="multipart/form-data" class="form-box">
                    <input type="hidden" name="action" value="registerOneProfessor">

                    <div class="d-flex align-items-center">
                        <h4>Datos del profesor</h4>
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
                        <label for="email" class="col-md-3 col-form-label">Email:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="email" name="email" placeholder="Correo electrónico">
                        </div>
                    </div>


                    <br>
                    <div class="text-center mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-primary" style="width: 200px;">
                            <i class="fas fa-user-plus mr-2"></i>
                            <span>Registrar profesor</span>
                        </button>
                    </div>
                </form>
            </div>

            <div id="consultar" class="my-5 sectionGD" style="display: none;">
                <h3>Consulta de Profesores</h3>
                <br>
                <table id="tableProfessors" class="table table-white table-nostriped">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">Clave</th>
                            <th scope="col">Nombre Completo</th>
                            <th scope="col">Correo</th>
                            <th scope="col">Materias</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>

            <div id="eliminar" class="my-5 sectionGD" style="display: none;">

                <div class="d-flex align-items-center">
                    <h3>Borrado de Profesor Único</h3>
                    <div class="fs-6 text-muted ml-2 mb-1">(no utilizar "al")</div>
                </div>

                <form action="" method="post" enctype="multipart/form-data" class="mt-4 form-box">
                    <input type="hidden" name="action" value="deleteOneProfessor">
                    <div class="form-group row mb-4">
                        <label for="claveUlsaDelete" class="col-md-3 col-form-label">Clave Ulsa:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="claveUlsaDelete" name="claveUlsaDelete" placeholder="Clave Ulsa" maxlength="6">
                        </div>
                    </div>
                    <div class="text-center mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-primary" style="width: 200px;">
                            <i class="fas fa-user-minus mr-2"></i>
                            <span>Eliminar profesor</span>
                        </button>
                    </div>
                </form>

                <br>
                <hr>

                <h3>Borrado de Todos los Profesores</h3>
                <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                    <input type="hidden" name="action" value="deleteAllProfessors">
                    <br>
                    <div class="text-center mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-danger" style="width: 200px;">
                            <i class="fas fa-trash mr-2"></i>
                            <span>Eliminar profesores</span>
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
    <script src="<?= ASSETS_PATH ?>/js/GD/scripts.js"></script>
</body>

</html>
