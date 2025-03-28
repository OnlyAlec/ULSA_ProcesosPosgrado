<?php
require_once '../../../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/models/student.php";

$studentsDB = getStudents();

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
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SED - Seguimiento de Alumnos</title>
    <link rel="stylesheet" href="<?= ASSETS_PATH ?>/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

    <div class="container mt-4">
        <h2>Seguimiento de Evaluación Docente (SED)</h2>

        <!-- Filtro por Tipo de Programa -->
        <label for="programType">Seleccionar Tipo de Programa:</label>
        <select id="programType" class="form-control">
            <option value="">Todos</option>
            <option value="getMasters">Maestría</option>
            <option value="getSpecialty">Especialidad</option>
        </select>

        <!-- Filtro por Área del Programa -->
        <div id="filterArea" style="display:none;">
            <label for="programArea" class="mt-2">Seleccionar Área:</label>
            <select id="programArea" class="form-control">
                <option value="">Seleccione un tipo de programa primero</option>
            </select>
        </div>

        <!-- Tabla de Alumnos -->
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Clave ULSA</th>
                    <th>Nombre Completo</th>
                    <th>Correo</th>
                    <th>Estatus SED</th>
                </tr>
            </thead>
            <tbody id="studentsTable">
                <!-- Posible modificación aqui !!!! -->
                <?php foreach ($studentsDB as $student): ?>
                    <tr data-program="<?= strtolower($student['type_desc']) ?>"
                        data-area="<?= strtolower($student['area']) ?>">
                        <td><input type="checkbox" class="studentCheckbox"></td>
                        <td><?= htmlspecialchars($student['ulsa_id']) ?></td>
                        <td><?= htmlspecialchars($student['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($student['email']) ?></td>
                        <td>
                            <input type="checkbox" class="estatus-sed" data-student-id="<?= $student['id'] ?>"
                                <?= $student['sed'] ? 'checked' : '' ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Botones de Acción -->
        <button id="confirmChanges" class="btn btn-success" disabled>Confirmar Cambios</button>
        <button id="cancelChanges" class="btn btn-secondary" disabled>Cancelar</button>
    </div>

    <script src="<?= ASSETS_PATH ?>/js/SED/scripts.js"> </script>
    <script src="<?= ASSETS_PATH ?>/js/AFI/scripts.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/SED/table.js"></script>

</body>

</html>
