<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . '/utilities/database.php';
require_once INCLUDES_DIR . '/utilities/responseHTTP.php';
require_once INCLUDES_DIR . '/models/candidate.php';
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once 'manage_candidates.php';

        if ($_POST['action'] === 'registerOneCandidate') {
            // Validaciones
            if (!preg_match('/^[A-Za-z0-9\-]+$/', $_POST['folioAdmision'])) {
                throw new RuntimeException('Folio de Admisión inválido.');
            }
            if (!preg_match('/^[1-5]$/', $_POST['numeroBloque'])) {
                throw new RuntimeException('Número de Bloque inválido. Debe ser entre 1 y 5.');
            }
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $_POST['nombre'])) {
                throw new RuntimeException('Nombre inválido. Solo se permiten letras y espacios.');
            }
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $_POST['apellidos'])) {
                throw new RuntimeException('Apellidos inválidos. Solo se permiten letras y espacios.');
            }
            if (!filter_var($_POST['correo1'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Correo electrónico 1 inválido.');
            }
            if (!empty($_POST['correo2']) && !filter_var($_POST['correo2'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Correo electrónico 2 inválido.');
            }
            if (!preg_match('/^\d{10}$/', $_POST['celular'])) {
                throw new RuntimeException('Número de celular inválido. Debe tener 10 dígitos.');
            }
            if (empty($_POST['programaAcademico']) || !is_numeric($_POST['programaAcademico'])) {
                throw new RuntimeException('Programa Académico no válido.');
            }
            if (empty($_POST['fechaSolicitudEntrevista'])) {
                throw new RuntimeException('Fecha de Solicitud de Entrevista no puede estar vacía.');
            }
            if (empty($_POST['fechaHoraEntrevista'])) {
                throw new RuntimeException('Fecha y Hora de Entrevista no pueden estar vacías.');
            }

            // Validar clave ULSA si se proporciona
            $claveUlsa = null;
            if (isset($_POST['tieneClaveUlsa']) && $_POST['tieneClaveUlsa'] === 'true') {
                if (!preg_match('/^\d{6}$/', $_POST['claveUlsa'])) {
                    throw new RuntimeException('Clave ULSA inválida. Debe ser un número de 6 dígitos.');
                }
                $claveUlsa = (int)$_POST['claveUlsa'];
            }

            $res = insertOneCandidate(
                $_POST['folioAdmision'],
                (int)$_POST['numeroBloque'],
                $_POST['nombre'],
                $_POST['apellidos'],
                $_POST['correo1'],
                $_POST['correo2'] ?: null,
                $_POST['celular'],
                (int)$_POST['programaAcademico'],
                $_POST['fechaSolicitudEntrevista'],
                $_POST['fechaHoraEntrevista'],
                $claveUlsa
            );
        } elseif ($_POST['action'] === 'getPrograms') {
            $programs = getPrograms();
            $res = array_map(fn ($program) => $program->toArray(), $programs);
        } elseif ($_POST['action'] === 'getTableCandidates') {
            $res = array_values(
                array_map(fn ($candidate) => $candidate->getJSON(), getCandidates())
            );
        } elseif ($_POST['action'] === 'getCandidateDetails') {
            if (empty($_POST['candidateID'])) {
                throw new RuntimeException('ID del candidato no proporcionado.');
            }

            $candidates = getCandidates();
            $candidate = null;
            foreach ($candidates as $c) {
                if ($c->getID() == $_POST['candidateID']) {
                    $candidate = $c;
                    break;
                }
            }

            if ($candidate) {
                $res = $candidate->getJSON();
            } else {
                throw new RuntimeException('Candidato no encontrado.');
            }
        } elseif ($_POST['action'] === 'updateCandidateField') {
            if (empty($_POST['candidateID']) || empty($_POST['field']) || !isset($_POST['value'])) {
                throw new RuntimeException('Datos incompletos para actualización.');
            }

            $candidateID = (int)$_POST['candidateID'];
            $field = $_POST['field'];
            $value = $_POST['value'];

            // Campos que pertenecen a la tabla user
            $userFields = ['first_name', 'last_name', 'email'];

            if (in_array($field, $userFields)) {
                $res = updateCandidateUserField($candidateID, $field, $value);
            } else {
                $res = updateCandidateField($candidateID, $field, $value);
            }
        } elseif ($_POST['action'] === 'deleteOneCandidate') {
            if (!preg_match('/^[A-Za-z0-9\-]+$/', $_POST['folioAdmisionDelete'])) {
                throw new RuntimeException('Folio de Admisión inválido.');
            }
            $res = deleteCandidateByAdmissionFolio($_POST['folioAdmisionDelete']);
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
get_head('GC');
?>

<style>
    .candidate-details {
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
        background-color: #fff3cd;
        color: #856404;
    }
    
    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .form-check-input {
        cursor: pointer;
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.5rem;
    }
    
    .form-check-input:focus {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }
    
    .editable-field .checkbox-wrapper {
        min-height: 2rem;
        align-items: center;
    }
    
    .field-value input[type="checkbox"] {
        display: block !important;
        position: relative;
        width: 1.25rem;
        height: 1.25rem;
        margin: 0;
        flex-shrink: 0;
    }
    
    .field-value input[type="checkbox"]:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .checkbox-wrapper[title] {
        cursor: help;
    }
    
    .checkbox-wrapper[title] .field-text {
        color: #6c757d;
        font-style: italic;
    }
    
    /* Estilos para grupos de pendientes */
    .pending-group {
        margin-bottom: 1rem;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        background-color: #f8f9fa;
        overflow: hidden;
    }
    
    .pending-group-header {
        background-color: #e9ecef;
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    .pending-group-title {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 600;
        color: #495057;
    }
    
    .pending-group-title i {
        margin-right: 0.5rem;
        color: #6c757d;
    }
    
    .pending-group-content {
        padding: 0.75rem;
        background-color: #fff;
    }
    
    .pending-group-content .editable-field {
        margin-bottom: 0.75rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f1f3f5;
    }
    
    .pending-group-content .editable-field:last-child {
        margin-bottom: 0;
        border-bottom: none;
    }
    
    .pending-flag .field-label {
        width: 80px;
    }
    
    .pending-description .field-label {
        width: 100px;
    }
    
    /* Mejorar el espaciado y centrado del checkbox de Clave ULSA */
    .form-check {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        padding: 0.75rem 0;
    }
    
    .form-check-input {
        margin: 0;
        flex-shrink: 0;
        width: 1.25rem;
        height: 1.25rem;
    }
    
    .form-check-label {
        line-height: 1.4;
        margin: 0;
    }
    
    #claveUlsaGroup .form-group {
        margin-top: 1rem;
    }
    
    /* Corrección específica para el checkbox de Clave ULSA */
    .form-group .form-check {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0;
        padding: 0;
    }
    
    .form-group .form-check .form-check-input {
        margin: 0 !important;
        margin-right: 0.75rem !important;
        flex-shrink: 0;
        position: static !important;
        transform: none !important;
    }
    
    .form-group .form-check .form-check-label {
        margin: 0;
        line-height: 1.5;
        cursor: pointer;
        font-weight: normal;
    }
    
    /* Asegurar que el checkbox esté perfectamente alineado */
    #tieneClaveUlsa {
        vertical-align: middle;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }
    
    /* Contenedor del checkbox con mejor espaciado */
    .form-group.row.mb-4:has(.form-check) {
        align-items: center;
        min-height: 3rem;
    }
</style>

<body style="display: block;">
    <?php require_once INCLUDES_DIR . '/templates/header.php';
get_header('Gestión de Candidatos');
?>

    <main class="container content marco">
        <!-- Botones Nav -->
        <div class="sectionsGC row mb-3">
            <button id="btn-crear" class="col btn btn-outline-primary mr-3 p-4">
                <span>Registrar Candidatos</span>
            </button>
            <button id="btn-consultar" class="col btn btn-outline-primary mr-3 p-4">
                <span>Consultar Candidatos</span>
            </button>
            <button id="btn-eliminar" class="col btn btn-outline-primary p-4">
                <span>Eliminar Candidatos</span>
            </button>
        </div>

        <div>
            <!-- Sección de Registro -->
            <div id="crear" class="my-5 sectionGC" style="display: none;">
                <h3>Registro de candidato</h3>
                <form action="" method="post" enctype="multipart/form-data" class="form-box">
                    <input type="hidden" name="action" value="registerOneCandidate">

                    <br>

                    <!-- Checkbox para clave ULSA -->
                    <div class="form-group row mb-4">
                        <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="tieneClaveUlsa" name="tieneClaveUlsa" value="true">
                            <label class="form-check-label" for="tieneClaveUlsa">
                                ¿Cuenta con Clave ULSA?
                            </label>
                        </div>
                        </div>
                    </div>

                    <!-- Clave ULSA (oculto por defecto) -->
                    <div class="form-group row mb-4" id="claveUlsaGroup" style="display: none;">
                        <label for="claveUlsa" class="col-md-3 col-form-label">Clave ULSA:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="claveUlsa" name="claveUlsa" placeholder="Clave ULSA (6 dígitos)" maxlength="6">
                        </div>
                    </div>

                    <!-- Folio de Admisión -->
                    <div class="form-group row mb-4">
                        <label for="folioAdmision" class="col-md-3 col-form-label">Folio de Admisión:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="folioAdmision" name="folioAdmision" placeholder="Folio de Admisión" required>
                        </div>
                    </div>

                    <!-- Número de Bloque de Admisión -->
                    <div class="form-group row mb-4">
                        <label for="numeroBloque" class="col-md-3 col-form-label">Número de Bloque:</label>
                        <div class="col-md-8 ml-2">
                            <select class="form-control w-auto" id="numeroBloque" name="numeroBloque" required>
                                <option value="">Seleccionar bloque</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                    </div>

                    <!-- Nombre -->
                    <div class="form-group row mb-4">
                        <label for="nombre" class="col-md-3 col-form-label">Nombre(s):</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="nombre" name="nombre" placeholder="Nombre(s)" required>
                        </div>
                    </div>

                    <!-- Apellidos -->
                    <div class="form-group row mb-4">
                        <label for="apellidos" class="col-md-3 col-form-label">Apellidos:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="apellidos" name="apellidos" placeholder="Apellidos" required>
                        </div>
                    </div>

                    <!-- Correo1 -->
                    <div class="form-group row mb-4">
                        <label for="correo1" class="col-md-3 col-form-label">Correo 1:</label>
                        <div class="col-md-8 ml-2">
                            <input type="email" class="form-control w-auto" id="correo1" name="correo1" placeholder="Correo electrónico 1" required>
                        </div>
                    </div>

                    <!-- Correo2 -->
                    <div class="form-group row mb-4">
                        <label for="correo2" class="col-md-3 col-form-label">Correo 2 (Opcional):</label>
                        <div class="col-md-8 ml-2">
                            <input type="email" class="form-control w-auto" id="correo2" name="correo2" placeholder="Correo electrónico 2">
                        </div>
                    </div>

                    <!-- Celular -->
                    <div class="form-group row mb-4">
                        <label for="celular" class="col-md-3 col-form-label">Celular:</label>
                        <div class="col-md-8 ml-2">
                            <input type="tel" class="form-control w-auto" id="celular" name="celular" placeholder="Número de celular (10 dígitos)" maxlength="10" required>
                        </div>
                    </div>

                    <!-- Programa Académico -->
                    <div class="form-group row mb-4">
                        <label for="programaAcademico" class="col-md-3 col-form-label">Programa Académico:</label>
                        <div class="col-md-8 ml-2">
                            <select class="form-control w-auto" id="programaAcademico" name="programaAcademico" required>
                                <option value="">Seleccionar programa</option>
                            </select>
                        </div>
                    </div>

                    <!-- Fecha de Solicitud de Entrevista -->
                    <div class="form-group row mb-4">
                        <label for="fechaSolicitudEntrevista" class="col-md-3 col-form-label">Fecha de Solicitud:</label>
                        <div class="col-md-8 ml-2">
                            <input type="date" class="form-control w-auto" id="fechaSolicitudEntrevista" name="fechaSolicitudEntrevista" required>
                        </div>
                    </div>

                    <!-- Fecha y Hora de Entrevista -->
                    <div class="form-group row mb-4">
                        <label for="fechaHoraEntrevista" class="col-md-3 col-form-label">Fecha y Hora Entrevista:</label>
                        <div class="col-md-8 ml-2">
                            <input type="datetime-local" class="form-control w-auto" id="fechaHoraEntrevista" name="fechaHoraEntrevista" required>
                        </div>
                    </div>

                    <br>
                    <div class="text-center mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-primary" style="width: 200px;">
                            <i class="fas fa-user-plus mr-2"></i>
                            <span>Registrar candidato</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sección de Consulta -->
            <div id="consultar" class="my-5 sectionGC" style="display: none;">
                <h3>Consulta de Candidatos</h3>
                <br>
                <table id="tableCandidates" class="table table-white table-nostriped">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">Folio Admisión</th>
                            <th scope="col">Nombre</th>
                            <th scope="col">Programa Académico</th>
                            <th scope="col">Status</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>

            <!-- Sección de Eliminación -->
            <div id="eliminar" class="my-5 sectionGC" style="display: none;">
                <div class="d-flex align-items-center">
                    <h3>Eliminación de Candidato</h3>
                </div>

                <form action="" method="post" enctype="multipart/form-data" class="mt-4 form-box">
                    <input type="hidden" name="action" value="deleteOneCandidate">
                    <div class="form-group row mb-4">
                        <label for="folioAdmisionDelete" class="col-md-3 col-form-label">Folio de Admisión:</label>
                        <div class="col-md-8 ml-2">
                            <input type="text" class="form-control w-auto" id="folioAdmisionDelete" name="folioAdmisionDelete" placeholder="Folio de Admisión" required>
                        </div>
                    </div>
                    <div class="text-center mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-danger" style="width: 200px;">
                            <i class="fas fa-user-minus mr-2"></i>
                            <span>Eliminar candidato</span>
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
    <script src="<?= ASSETS_PATH ?>/js/GC/scripts.js"></script>
</body>

</html>
