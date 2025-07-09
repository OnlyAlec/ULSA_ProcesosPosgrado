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
                (int) $_POST['quitStatusID']
            );
        } elseif ($_POST['action'] === 'getActiveStudents') {
            $res = getActiveStudents();
        } elseif ($_POST['action'] === 'getQuittedStudents') {
            $res = array_map(fn ($quitted) => $quitted->getJSON(), getQuittedStudents());
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
            $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $fileInfo['filename']) . '.' . $fileInfo['extension'];
            $filePath = '/uploads/GB/' . $fileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $filePath)) {
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
                throw new RuntimeException('Falta el ID de la baja para obtener comentarios y evidencias.');
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

<style>
    .quitted-details {
        background-color: #f8fafc;
        padding: 1.5rem;
        border-radius: 12px;
        margin-top: 1rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .field-group {
        margin-bottom: 1.25rem;
        padding: 0.75rem;
        background-color: #fff;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .field-group h6 {
        color: #4a5568;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }
    
    .editable-field {
        display: flex;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f1f3f5;
    }
    
    .editable-field:last-child {
        border-bottom: none;
    }
    
    .field-label {
        font-weight: 500;
        color: #6c757d;
        width: 200px;
        font-size: 0.9rem;
    }
    
    .field-value {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .field-value input,
    .field-value select,
    .field-value textarea {
        display: none;
        flex: 1;
    }
    
    .field-value.editing input,
    .field-value.editing select,
    .field-value.editing textarea {
        display: block;
    }
    
    .field-value.editing .field-text {
        display: none;
    }
    
    .field-text {
        flex: 1;
        color: #212529;
    }
    
    .btn-edit-field {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .status-badge.active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-badge.inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-badge.pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .comment-box {
        background-color: #f8f9fa;
        padding: 0.75rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        border-left: 3px solid #007bff;
    }
    
    .comment-author {
        font-weight: 500;
        color: #495057;
        font-size: 0.85rem;
    }
    
    .comment-text {
        color: #212529;
        margin-top: 0.25rem;
    }
    
    .evidence-section {
        padding: 0.5rem 0;
    }
    
    .evidence-list {
        max-height: 200px;
        overflow-y: auto;
        padding: 0.5rem;
        background-color: #f8f9fa;
        border-radius: 4px;
        border: 1px solid #e9ecef;
    }
    
    .evidence-item {
        padding: 0.25rem 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .evidence-item:last-child {
        border-bottom: none;
    }
    
    .evidence-item a {
        color: #007bff;
        text-decoration: none;
    }
    
    .evidence-item a:hover {
        text-decoration: underline;
    }
    
    .student-info {
        background-color: #e9ecef;
        padding: 0.75rem;
        border-radius: 6px;
        margin-top: 1rem;
    }
    
    .quit-form-card {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .alert {
        border-radius: 8px;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .alert i {
        margin-right: 0.5rem;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
    
    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border-left: 4px solid #17a2b8;
    }
    
    .btn-select-student {
        min-width: 40px;
        height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .btn-select-student:hover {
        transform: scale(1.05);
    }
    
    .btn-select-student i {
        font-size: 1.1rem;
    }
</style>

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
