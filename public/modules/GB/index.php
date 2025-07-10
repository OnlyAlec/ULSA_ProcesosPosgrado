<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . '/utilities/database.php';
require_once INCLUDES_DIR . '/utilities/responseHTTP.php';
require_once INCLUDES_DIR . '/models/quitted.php';
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['action'] === 'registerQuit') {
            // Validaciones
            if (empty($_POST['studentID']) || !is_numeric($_POST['studentID'])) {
                throw new RuntimeException('ID de estudiante no válido.');
            }
            if (empty($_POST['quitDescriptionID']) || !is_numeric($_POST['quitDescriptionID'])) {
                throw new RuntimeException('Tipo de baja no válido.');
            }
            if (empty($_POST['quitStatusID']) || !is_numeric($_POST['quitStatusID'])) {
                throw new RuntimeException('Estado de baja no válido.');
            }

            $res = insertQuitted(
                (int) $_POST['studentID'],
                (int) $_POST['quitDescriptionID'],
                $_POST['requestedAt'] ?: null,
                $_POST['officialApplyingAt'] ?: null,
                $_POST['nofficialApplyingAt'] ?: null,
                $_POST['returningAt'] ?: null,
                null, // status field no longer used
                $_POST['quitReasonID'] ? (int) $_POST['quitReasonID'] : null,
                (int) $_POST['quitStatusID'],
            );
        } elseif ($_POST['action'] === 'getActiveStudents') {
            $res = getActiveStudents();
        } elseif ($_POST['action'] === 'getQuittedStudents') {
            $res = array_map(fn($quitted) => $quitted->getJSON(), getQuittedStudents());
        } elseif ($_POST['action'] === 'getQuittedDetails') {
            if (empty($_POST['quittedID'])) {
                throw new RuntimeException('ID de baja no proporcionado.');
            }

            $quitted = getQuittedByID((int) $_POST['quittedID']);
            if ($quitted) {
                $res = $quitted->getJSON();
            } else {
                throw new RuntimeException('Baja no encontrada.');
            }
        } elseif ($_POST['action'] === 'getQuitDescriptions') {
            $res = getQuitDescriptions();
        } elseif ($_POST['action'] === 'getQuitReasons') {
            $res = getQuitReasons();
        } elseif ($_POST['action'] === 'getQuitStatuses') {
            $res = getQuitStatuses();
        } elseif ($_POST['action'] === 'updateQuittedField') {
            if (empty($_POST['quittedID']) || empty($_POST['field']) || !isset($_POST['value'])) {
                throw new RuntimeException('Datos incompletos para actualización.');
            }

            $res = updateQuittedField((int) $_POST['quittedID'], $_POST['field'], $_POST['value']);
        } elseif ($_POST['action'] === 'uploadQuittedEvidence') {
            if (!isset($_POST['quittedID']) || !isset($_FILES['file'])) {
                throw new RuntimeException('Faltan datos para subir la evidencia.');
            }

            // Crear directorio si no existe
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/GB/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generar nombre único para el archivo
            $fileInfo = pathinfo($_FILES['file']['name']);
            $fileName =
                uniqid() .
                '_' .
                preg_replace('/[^a-zA-Z0-9_.-]/', '_', $fileInfo['filename']) .
                '.' .
                $fileInfo['extension'];
            $filePath = '/uploads/GB/' . $fileName;

            if (
                move_uploaded_file(
                    $_FILES['file']['tmp_name'],
                    $_SERVER['DOCUMENT_ROOT'] . $filePath,
                )
            ) {
                $res = insertQuittedEvidence($_POST['quittedID'], $filePath);
            } else {
                throw new RuntimeException('Error al mover el archivo.');
            }
        } elseif ($_POST['action'] === 'addQuittedComment') {
            if (empty($_POST['quittedID']) || empty($_POST['comment']) || empty($_POST['author'])) {
                throw new RuntimeException('Faltan datos para agregar el comentario.');
            }

            $res = insertQuittedComment($_POST['quittedID'], $_POST['comment'], $_POST['author']);
        } elseif ($_POST['action'] === 'getQuittedCommentsAndEvidence') {
            if (!isset($_POST['quittedID'])) {
                throw new RuntimeException(
                    'Falta el ID de la baja para obtener comentarios y evidencias.',
                );
            }
            $res = getQuittedCommentsAndEvidence($_POST['quittedID']);
        }

        echo responseOK($res);
        exit();
    }
} catch (RuntimeException $e) {
    echo responseInternalError($e->getMessage());
    exit();
}
ob_end_flush();
?>
<!DOCTYPE html>

<?php
require_once INCLUDES_DIR . '/templates/head.php';
get_head('GB');
?>

<body style="display: block;">
    <?php
    require_once INCLUDES_DIR . '/templates/header.php';
    get_header('Gestión de Bajas');
    ?>

    <main class="container content marco">
        <!-- Botones Nav -->
        <div class="sectionsGB row mb-3">
            <button id="btn-registrar" class="col btn btn-outline-primary mr-3 p-4">
                <span>Registrar Baja</span>
            </button>
            <button id="btn-consultar" class="col btn btn-outline-primary p-4">
                <span>Consultar/Modificar Bajas</span>
            </button>
        </div>

        <div>
            <!-- Sección de Registro de Baja -->
            <div id="registrar" class="my-5 sectionGB" style="display: none;">
                <h3>Registro de Baja</h3>
                
                <!-- Tabla de estudiantes activos -->
                <div class="mt-4">
                    <h5><i class="fas fa-user-graduate"></i> Seleccionar Estudiante Activo</h5>
                    <table id="tableActiveStudents" class="table table-white table-nostriped">
                        <thead class="thead-dark">
                            <tr>
                                <th scope="col">Clave ULSA</th>
                                <th scope="col">Nombre Completo</th>
                                <th scope="col">Programa</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sección de Consulta -->
            <div id="consultar" class="my-5 sectionGB" style="display: none;">
                <h3>Consulta de Bajas</h3>
                <br>
                <table id="tableQuitted" class="table table-white table-nostriped">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">Clave ULSA</th>
                            <th scope="col">Nombre</th>
                            <th scope="col">Programa</th>
                            <th scope="col">Status</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php include INCLUDES_DIR . '/templates/footer.php'; ?>

    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/util.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/GB/script.js"></script>
</body>

</html>
