<?php
require_once '../../../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/utilities/responseHTTP.php";
require_once INCLUDES_DIR . "/models/student.php";

ob_start();
$studentsDB = getStudents();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
try {
        header('Content-Type: application/json');
        require_once './gestorStudents.php';

        switch ($_POST['action']) {
            case 'updateSingleSED':
                $studentID = $_POST['studentID'] ?? null;
                $newState = $_POST['state'] ?? null;
                $res = updateStudentFieldBoolean($studentID, 'sed', $newState);
                break;

            case 'updateSED':
                $error = false;
                $studentIDs = $_POST['studentIDS'] ?? [];
                foreach ($studentIDs as $id) {
                    if(updateStudentFieldBoolean($id, 'sed', true) == 0 ){
                        ErrorList::add("Not update student $id");
                        $error = true;
                    }
                }

                if($error) {
                    throw new RuntimeException("");
                }
                
                $res = "";
                break;

            case 'getMasters':
                $programs = getMastersPrograms();
                $res = array_map(fn($program) => $program->getName(), $programs);
                break;

            case 'getSpecialty':
                $programs = getSpecialtyPrograms();
                $res = array_map(fn($program) => $program->getName(), $programs);
                break;

            case '':
                $allPrograms = getPrograms($_POST['action']);
                $res = array_map(fn($program) => $program->getName(), $allPrograms);
                break;

            case 'sendEmail':
                $student = getStudentFromUlsaID($_POST['studentID']) ?? null;
                $res = sendEmailRemainder($student);
                break;

            default:
                throw new RuntimeException('Not valid action!');
        }
        
        echo responseOK($res);
        exit;
    } catch (RuntimeException $e) {
        echo responseInternalError($e->getMessage());
        exit;
    }   
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
        <div class="sectionsSED">
            <h1 class="text-center">Lista de alumnos</h1>
            <p>
                El sistema permite gestionar la selección y actualización del <strong>Estado SED</strong> de los alumnos mediante una tabla 
                interactiva con filtros y opciones de selección múltiple.
            </p>
            <ul>
                <li><strong>Checkboxes (cuadros de selección):</strong> Puede seleccionar varios alumnos mediante los checkboxes para cambiar su estado SED a "realizado" confirmando los cambios.</li>
                <li><strong>Iconos de Estado:</strong> Puede actualizar el estado sed a "realizado" o "no realizado" de los alumnos de manera invidiual dando un clic en el icono de "Estatus SED".</li>
            </ul>
        </div>
        <div class="row justify-content-end">
            <div class="col-10">
                <label for="programType">Seleccionar Tipo de Programa:</label>
                <select id="programType" class="form-control">
                    <option value="">Todos</option>
                    <option value="getMasters">Maestría</option>
                    <option value="getSpecialty">Especialidad</option>
                </select>
            </div>

            <div class="col-2">
                <a href="load_excel.php">
                    <button type="button" class="btn btn-primary h-100 w-100">Cargar Excel</button>
                </a>
            </div>

            <div id="filterArea" class="col-12" style="display:none;">
                <label for="programArea" class="mt-2">Seleccionar Área:</label>
                <select id="programArea" class="form-control">
                    <option value="">Seleccione un tipo de programa primero</option>
                </select>
            </div>
        </div>

        <table class="table table-bordered mt-4" id="studentsTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" style="width: 20px; height: 20px;"></th>
                    <th>Clave ULSA</th>
                    <th>Nombre Completo</th>
                    <th>Correo</th>
                    <th>Estatus SED</th>
                </tr>
            </thead>
            <tbody id="studentsTable">
                <?php
                $studentsDB = getStudents();
                foreach ($studentsDB as $student): ?>
                    <tr data-carrer="<?= $student->getProgram() ?>">
                        <td><input type="checkbox" class="studentCheckbox" style="width: 20px; height: 20px;"></td>
                        <td><?= htmlspecialchars($student->getUlsaId()) ?></td>
                        <td><?= htmlspecialchars($student->getName()) . " " . htmlspecialchars($student->getLastName()) ?></td>
                        <td><?= htmlspecialchars($student->getEmail()) ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <?php
                                    $btnClass = $student->getSed() ? 'btn-success' : 'btn-danger';
                                ?>
                                <button class="btn <?= $btnClass ?> btn-sm text-white changeSED border-0 flex-fill" data-student-id="<?= $student->getUlsaId() ?>">
                                    <?= $student->getSed()
                                        ? '<i class="fas fa-check-square fa-2x"></i>'
                                        : '<i class="fas fa-minus-square fa-2x"></i>' 
                                    ?>
                                </button>

                                <button class="btn btn-info btn-sm text-white sendEmail border-0 flex-fill" data-student-id="<?= $student->getUlsaId() ?>">
                                    <i class="fas fa-paper-plane fa-2x"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="d-flex justify-content-between">
            <button id="confirmChanges" class="btn btn-success w-50" disabled>Confirmar Cambios</button>
            <button id="generateReport" class="btn btn-danger" data-filename="reporte_evaluaciones">Generar Reporte</button>
        </div>

        <div id="selectedCountContainer">
            <p style="margin-top:15px; font-size: 20px; font-weight: bold;">Alumnos seleccionados: <span id="selectedCount">0</span></p>
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