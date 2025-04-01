<?php
require_once '../../../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/models/student.php";

ob_start();
$studentsDB = getStudents();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) { 
    require_once './update_functions.php';
    header('Content-Type: application/json');

    if ($_POST['action'] === 'updateSingleSED') {
        $studentID = $_POST['studentID'] ?? null;
        $newState = $_POST['state'] ?? null;

        $response = updateSingleSED($studentID, $newState);
        echo json_encode($response);
        exit;
    }

    if ($_POST['action'] === 'updateSED') {
        $studentIDs = $_POST['studentIDS'] ?? [];
        $response = updateSelectedSED($studentIDs);
        echo json_encode($response);
        exit;
    }

    if ($_POST['action'] === 'getMasters' || $_POST['action'] === 'getSpecialty' || $_POST['action'] === '') {
        $response = getPrograms($_POST['action']);
        echo json_encode($response);
        exit;
    }
}
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
        <h1 class="text-center">Lista de alumnos</h1>
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

<main class="container content marco">
        <div>
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-2">Carga del concentrado SED en Excel:</h1>
                <a href="../../index.php" class="btn btn-outline-primary">Regresar</a>
            </div>
            <br>
            <form action="" method="post" enctype="multipart/form-data" class="mt-4">
                <div class="mb-3">
                    <h4 for="sedExcelFile" class="form-label">Subir archivo Excel:</h4>
                    <input type="file" class="form-control form-control-lg w-100 pb-5 pl-2" id="sedExcelFile" name="sedExcelFile" accept=".xls,.xlsx" required>
                    <div id="emailHelp" class="form-text d-flex justify-content-end">Los valores de Estatus se actualizarán en la base de datos.</div>
                </div>
                
                <div class="d-flex align-items-center">
                    <h4>Encabezados</h4>
                    <div class="fs-6 text-muted ml-2 mb-1">(ej: A, B, C, ...)</div>
                </div>

                <div class="mb-3">
                    <div class="row align-items-center mb-2">
                        <label class="col-2" for="claveUlsa" class="form-label me-2">Clave Ulsa:</label>
                        <input type="text" class="col-2 form-control w-auto" id="claveUlsa" name="claveUlsa" placeholder="Columna" maxlength="1">
                    </div>
                    <div class="row align-items-center mb-2">
                        <label class="col-2" for="nombre" class="form-label me-2">Nombre:</label>
                        <input type="text" class="col-2 form-control w-auto" id="nombre" name="nombre" placeholder="Columna" maxlength="1">
                    </div>
                    <div class="row align-items-center">
                        <label class="col-2" for="estatus" class="form-label me-2">Estatus:</label>
                        <input type="text" class="col-2 form-control w-auto" id="estatus" name="estatus" placeholder="Columna" maxlength="1">
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-danger">Cargar Excel</button>
                </div>
            </form>
            <br>
            <hr>
            <br>
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
                    <tr data-carrer="<?= $student->getCarrer() ?>">
                        <td><input type="checkbox" class="studentCheckbox" style="width: 20px; height: 20px;"></td>
                        <td><?= htmlspecialchars($student->getUlsaId()) ?></td>
                        <td><?= htmlspecialchars($student->getName()) . " " . htmlspecialchars($student->getLastName()) ?></td>
                        <td><?= htmlspecialchars($student->getEmail()) ?></td>
                        <td>
                            <button class="changeSED border-0" data-student-id="<?= $student->getUlsaId() ?>">
                                <?= $student->getSed() ? '<i class="fas fa-check-square fa-2x" style="color:#36b18c"></i>' : '<i class="fas fa-minus-square fa-2x" style="color:rgb(206, 85, 85)"></i>' ?>
                            </button>
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