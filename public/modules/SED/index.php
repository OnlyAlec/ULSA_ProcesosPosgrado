<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/utilities/responseHTTP.php";
require_once INCLUDES_DIR . "/models/student.php";

ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        if (isset($_POST['action'])) {
            require_once 'functionsSED.php';

            switch ($_POST['action']) {
                case 'updateSingleSED':
                    $res = changeStatusSEDSingle($_POST['studentID'], $_POST['state']);
                    break;
                case 'updateSED':
                    $res = changeStatusSEDGroup($_POST['studentIDS']);
                    break;
                case 'getMasters':
                    $res = array_map(fn ($program) => $program->getName(), getMastersPrograms());
                    break;
                case 'getSpecialty':
                    $res = array_map(fn ($program) => $program->getName(), getSpecialtyPrograms());
                    break;
                case 'sendEmail':
                    $student = getStudentByUlsaID($_POST['studentID']);
                    if ($student) {
                        $res = sendEmailRemainder($student);
                    } else {
                        throw new RuntimeException('Student not found');
                    }
                    break;
                case '':
                    $res = array_map(fn ($program) => $program->getName(), getProgramsFiltered($_POST['action']));
                    break;
                default:
                    throw new RuntimeException('Not valid action!');
            }
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
get_head("SED");
?>

<body style="display: block;">
    <?php require_once INCLUDES_DIR . '/templates/header.php';
get_header("Seguimiento de Evaluación Docente");
?>
    <main class="container content marco">
        
        <!-- PÁRRAFO INFORMATIVO -->
        <div class="sectionsSED">
            <h3>Lista de alumnos</h3>
            <p>
                El sistema permite gestionar la selección y actualización del <strong>Estado SED</strong> de los alumnos mediante una tabla 
                interactiva con filtros y opciones de selección múltiple.
            </p>
            <ul>
                <li><strong>Checkboxes (cuadros de selección):</strong> Puede seleccionar varios alumnos mediante los checkboxes para cambiar su estado SED a "realizado" confirmando los cambios.</li>
                <li><strong>Iconos de Estado:</strong> Puede actualizar el estado sed a "realizado" o "no realizado" de los alumnos de manera invidiual dando un clic en el icono de "Estatus SED".</li>
            </ul>
        </div>
        <br>

        <!-- FILTROS POR TIPO DE PROGRAMA Y ÁREA ESPECÍFICA + BOTÓN CARGA EXCEL -->
        <div class="row align-items-center">
            <div class="col-12 row">
                <div class="form-box col-10" style="margin-bottom: 0;">
                    <div class="form-group row">
                        <label for="programType" class="col-md-4 col-form-label">Seleccionar Tipo de Programa:</label>
                        <div class="col-md-7 ml-2 datalist">
                            <input type="text" id="programType" class="datalist-input w-100" placeholder="Seleccionar" readonly>
                            <i class="fas fa-search icono filter"></i>
                            <ul style="display: none;">
                                <li data-value="">Todos</li >
                                <li data-value="getMasters">Maestría</li>
                                <li data-value="getSpecialty">Especialidad</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-2">
                    <a href="load_excel.php">
                        <button type="button" class="btn btn-outline-primary w-100">Cargar Excel</button>
                    </a>
                </div>
            </div>

            <div id="filterArea" class="col-12 row mt-1" style="display:none;">
                <div class="form-box col-10" style="margin-bottom: 0;">
                    <div class="form-group row">
                        <label for="programArea" class="col-md-4 col-form-label">Seleccionar Área: </label>
                        <div class="col-md-7 ml-2 datalist">
                            <input type="text" id="programArea" class="datalist-input w-100" placeholder="Seleccione un área" readonly>
                            <i class="fas fa-search icono filter"></i>
                            <ul style="display: none;">
                                <li data-value="">Seleccione un área</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-2"></div>
            </div>
        </div>

        <!-- FILTROS PARA ALUMNOS POR SU ESTADO SED -->
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
        <br>

        <!-- TABLA DE ALUMNOS -->
        <table class="table table-white table-nostriped" id="studentsTable">
            <thead class="thead-dark">
                <tr>
                    <th><input type="checkbox" id="selectAll" style="width: 20px; height: 20px;"></th>
                    <th>Clave ULSA</th>
                    <th>Nombre Completo</th>
                    <th>Correo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="studentsTable">
                <?php
                    if (empty($studentsDB = getStudents())) {
                        echo '<tr><td colspan="5" class="text-center">No hay alumnos registrados.</td></tr>';
                    } 
                    else {
                        foreach ($studentsDB as $student): ?>
                            <tr data-carrer="<?= $student->getProgram() ?>">
                                <td><input type="checkbox" class="studentCheckbox" style="width: 20px; height: 20px;"></td> 
                                <td><?= $student->getUlsaId() ?></td>
                                <td><?= ucwords($student->getName()). " " . ucwords($student->getLastName()) ?></td>
                                <td><?= $student->getEmail() ?></td>
                                <td>
                                    <div class="d-flex" style="gap: 8px;">
                                        <?php $btnClass = $student->getSed() ? 'btn-danger' : 'btn-success'; ?>
                                        <button class="btn <?= $btnClass ?> btn-sm text-white changeSED border-0 flex-fill" data-student-id="<?= $student->getUlsaId() ?>">
                                            <?= $student->getSed() ? '<i class="fas fa-minus-square fa-2x"></i>' : '<i class="fas fa-check-square fa-2x"></i>' ?>
                                        </button>
                                        <button class="btn btn-info btn-sm text-white sendEmail border-0 flex-fill" data-student-id="<?= $student->getUlsaId() ?>">
                                            <i class="fas fa-paper-plane fa-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach;
                    } 
                ?>
            </tbody>
        </table>
        <br>

        <!-- BOTONES INFERIORES -->
        <div class="d-flex justify-content-between">
            <button id="confirmChanges" class="btn btn-outline-success w-50" style="width: 200px;" disabled>
                <span>Confirmar Cambios</span>
            </button>
            <button id="generateReport" class="btn btn-outline-primary" style="width: 200px;" data-filename="reporte_evaluaciones">
                <span>Generar Reporte</span>
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

    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/popper.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/util.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/SED/scripts.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/SED/table.js"></script>
</body>

</html>