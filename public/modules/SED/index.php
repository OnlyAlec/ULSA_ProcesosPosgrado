<?php
require_once '../../../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/models/student.php";

ob_start();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $masters = getMastersPrograms();
        $specialty = getSpecialtyPrograms();
        $res = ($_POST['action'] === '') ? array_unique(array_merge($masters, $specialty), SORT_REGULAR) : (($_POST['action'] === 'getMasters') ? $masters : $specialty);
        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
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
        <h1 class="text-center">Lista de alumnos:</h1>
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

        <!-- Tabla de Alumnos -->
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
                        <td><?= htmlspecialchars($student->getName()) . " " . htmlspecialchars($student->getLastName()) ?>
                        </td>
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

        <!-- Botones de Acción -->
        <button id="confirmChanges" class="btn btn-success w-100" disabled>Confirmar Cambios</button>
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