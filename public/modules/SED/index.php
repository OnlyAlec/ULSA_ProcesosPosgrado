<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . '/utilities/database.php';
require_once INCLUDES_DIR . '/utilities/responseHTTP.php';
require_once 'functionsSED.php';

ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $res = false;

        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'updateSingleSED':
                    $res = changeStatusSEDSingle($_POST['studentID'], $_POST['state']);
                    break;
                case 'updateSED':
                    $res = changeStatusSEDGroup($_POST['studentIDS']);
                    break;
                case 'sendEmail':
                    $student = getStudentByUlsaID($_POST['studentID']);
                    $res = $student
                        ? sendEmailRemainder($student)
                        : responseBadRequest('Student not found');
                    break;
                default:
                    $res = responseBadRequest('Invalid action');
            }
        } else {
            $res = responseBadRequest('No action specified');
        }

        if ($res === false || (isset($res['success']) && $res['success'] === false)) {
            echo responseBadRequest($res['message'] ?? 'Error processing the request.');
        } else {
            echo responseOK($res);
        }
        exit();
    }
} catch (RuntimeException $e) {
    echo responseInternalError($e->getMessage());
    exit();
}

$masterProgramsDataForPage = array_map(fn($program) => $program->getName(), getMastersPrograms());
$specialtyProgramsDataForPage = array_map(
    fn($program) => $program->getName(),
    getSpecialtyPrograms(),
);

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="es">
<?php
require_once INCLUDES_DIR . '/templates/head.php';
get_head('SED');
?>

<body style="display: block;">
    <?php
    require_once INCLUDES_DIR . '/templates/header.php';
    get_header('Seguimiento de Evaluación Docente');
    ?>
    <main class="container content marco">

        <!-- PÁRRAFO INFORMATIVO -->
        <div class="sectionsSED">
            <h3>Lista de alumnos</h3>
            <p>
                El sistema permite gestionar la selección y actualización del <strong>Estado SED</strong> de los alumnos
                mediante una tabla
                interactiva con filtros y opciones de selección múltiple.
            </p>
            <ul>
                <li><strong>Checkboxes (cuadros de selección):</strong> Puede seleccionar varios alumnos mediante los
                    checkboxes para cambiar su estado SED a "realizado" confirmando los cambios.</li>
                <li><strong>Iconos de Estado:</strong> Puede actualizar el estado sed a "realizado" o "no realizado" de
                    los alumnos de manera invidiual dando un clic en el icono de "Estatus SED".</li>
            </ul>
        </div>
        <br>

        <!-- FILTROS POR TIPO DE PROGRAMA Y ÁREA ESPECÍFICA + BOTÓN CARGA EXCEL -->
        <div class="row mb-2">
            <div class="col-md-9 mt-1">
                <div class="form-box">
                    <div class="form-group row">
                        <label for="programType" class="col-md-4 col-form-label">Seleccionar Programa:</label>
                        <div class="col-md-7 ml-2 datalist">
                            <input type="text" id="programType" class="datalist-input w-100"
                                placeholder="Seleccionar Tipo:" readonly>
                            <i class="fas fa-search icono filter"></i>
                            <ul style="display: none;">
                                <li data-value="">Todos</li>
                                <li data-value="masters">Maestría</li>
                                <li data-value="specialties">Especialidad</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div id="filterArea" class="mt-1" style="display:none;">
                    <div class="form-box">
                        <div class="form-group row">
                            <label for="programArea" class="col-md-4 col-form-label">Seleccionar Área: </label>
                            <div class="col-md-7 ml-2 datalist">
                                <input type="text" id="programArea" class="datalist-input w-100"
                                    placeholder="Seleccione un área" readonly>
                                <i class="fas fa-search icono filter"></i>
                                <ul style="display: none;"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <a href="load_excel.php">
                    <button type="button"
                        class="bg-primary text-white p-3 rounded d-flex flex-column justify-content-center align-items-center"
                        style="height: 115px;">
                        <i class="fas fa-file-upload fa-2x pb-2"></i>
                        <b>Cargar Excel</b>
                    </button>
                </a>
            </div>
            <div class="col-md-1">
                <button id="generateReport" type="button"
                    class="bg-danger text-white p-3 rounded d-flex flex-column justify-content-center align-items-center"
                    style="height: 115px;" data-filename="reporte_evaluaciones">
                    <i class="fas fa-file-pdf fa-2x pb-2"></i>
                    <b>Reporte</b>
                </button>
            </div>
        </div>
        <hr>
        <!-- FILTROS PARA ALUMNOS POR SU ESTADO SED -->
        <div class="form-group row justify-content-center mt-4">
            <button id="removeFilter" class="btn btn-outline-success mr-2" style="width: 230px;">
                <i class="fas fa-users"></i> Quitar sub-filtro
            </button>
            <button id="onlyConfirm" class="btn btn-outline-primary mr-2" style="width: 230px;">
                <i class="fas fa-check-double"></i> Solamente confirmados
            </button>
            <button id="onlyMissing" class="btn btn-outline-danger" style="width: 230px;">
                <i class="fas fa-times-circle"></i> Solamente faltantes
            </button>
        </div>

        <!-- TABLA DE ALUMNOS -->
        <table class="table table-white table-nostriped" id="studentsTable">
            <thead class="thead-dark">
                <tr>
                    <th><input type="checkbox" id="selectAll" style="width: 20px; height: 20px;"></th>
                    <th>Clave ULSA</th>
                    <th>Nombre Completo</th>
                    <th>Programa</th>
                    <th>Correo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="studentsTable">
                <?php if (empty(($studentsDB = getStudents()))) {
                    echo '<tr><td colspan="5" class="text-center">No hay alumnos registrados.</td></tr>';
                } else {
                    foreach ($studentsDB as $student): ?>
                        <tr data-carrer="<?= $student->getProgram() ?>">
                            <td class="text-center"><input type="checkbox" class="studentCheckbox"
                                    style="width: 20px; height: 20px;"></td>
                            <td><?= $student->getUlsaId() ?></td>
                            <td><?= ucwords($student->getName()) .
                                ' ' .
                                ucwords($student->getLastName()) ?></td>
                            <td>
                                <?php
                                $program = $student->getProgram();
                                if ($program) {
                                    echo ucwords($program);
                                } else {
                                    echo 'No disponible';
                                }
                                ?>
                            <td><?= $student->getEmail() ?></td>
                            <td>
                                <div class="d-flex" style="gap: 8px;">
                                    <?php $btnClass = $student->getSed()
                                        ? 'btn-danger'
                                        : 'btn-success'; ?>
                                    <button class="btn <?= $btnClass ?> btn-sm text-white changeSED border-0 flex-fill"
                                        data-student-id="<?= $student->getUlsaId() ?>">
                                        <?= $student->getSed()
                                            ? '<i class="fas fa-minus-square fa-2x"></i>'
                                            : '<i class="fas fa-check-square fa-2x"></i>' ?>
                                    </button>
                                    <button class="btn btn-info btn-sm text-white sendEmail border-0 flex-fill"
                                        data-student-id="<?= $student->getUlsaId() ?>">
                                        <i class="fas fa-paper-plane fa-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
                } ?>
            </tbody>
        </table>
        <br>

        <!-- BOTONES INFERIORES -->
        <div class="d-flex justify-content-between">
            <button id="confirmChanges" class="btn btn-outline-success w-100" disabled>
                <span>Confirmar Cambios</span>
            </button>
        </div>

        <!-- NUMERO DE ALUMNOS SELECCIONADOS EN CHECKBOXES -->
        <div id="selectedCountContainer">
            <p style="margin-top:15px; font-size: 20px; font-weight: bold;">Alumnos seleccionados:
                <span id="selectedCount">0</span>
            </p>
        </div>
    </main>

    <?php include INCLUDES_DIR . '/templates/footer.php'; ?>

    <script>
        window.sedPreloadedData = {
            masters: <?= json_encode($masterProgramsDataForPage) ?>,
            specialties: <?= json_encode($specialtyProgramsDataForPage) ?>
        };
    </script>
    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/popper.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/util.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/SED/scripts.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/SED/table.js"></script>
</body>

</html>