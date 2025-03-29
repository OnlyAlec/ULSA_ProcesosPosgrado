<?php
require_once '../../../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/models/student.php";

ob_start();

/* AJAX Para actualizar SED 1 Alumno cuando se quiere desmarcar o marcar */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateSingleSED') {
    header('Content-Type: application/json');
    try {
        $studentID = $_POST['studentID'] ?? null;
        $newState = $_POST['state'] ?? null;
        $db = getDatabaseConnection();

        if (!$studentID || $newState === null) {
            echo json_encode(["success" => false, "message" => "Datos inválidos."]);
            exit;
        }

        if (!$db) {
            echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos."]);
            exit;
        }

        $query = "UPDATE student SET SED = ? WHERE ulsa_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$newState, $studentID]);

        echo json_encode(["success" => true, "message" => "Estado SED actualizado correctamente."]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
        exit;
    }
}

/* AJAX Para actualizar SED N Alumnos al confirmar los cambios */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateSED') {
    header('Content-Type: application/json');
    try {
            $students = $_POST['studentIDS'] ?? [];
            $db = getDatabaseConnection(); 

            if (!is_array($students) || empty($students)) {
                echo json_encode(["success" => false, "message" => "No se enviaron alumnos."]);
                exit;
            }
            if (!$db) {
                echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos."]);
                exit;
            }

            $query = "UPDATE student SET SED = TRUE WHERE ulsa_id = ?";
            $stmt = $db->prepare($query);

            foreach ($students as $id) {
                $stmt->execute([$id]);
            }

            echo json_encode(["success" => true, "message" => "Estado SED actualizado correctamente."]);
            exit;
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
        exit;
    }
}

/* AJAX Para traer los programas */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {   
            $masters = getMastersPrograms();
            $specialty = getSpecialtyPrograms();
            
            $res = ($_POST['action'] === '') ? array_unique(array_merge($masters, $specialty), SORT_REGULAR) : (($_POST['action'] === 'getMasters') ? $masters : $specialty);
            
            echo json_encode($res);
            exit;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
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