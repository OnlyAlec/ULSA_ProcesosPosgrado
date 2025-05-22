<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . '/utilities/database.php';
require_once INCLUDES_DIR . '/utilities/responseHTTP.php';
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if ($_POST['action'] === 'getProgramSubjects') {
            $res = getProgramSubjects();
        } elseif ($_POST['action'] === 'toggleSigned') {
            if (!isset($_POST['id']) || !isset($_POST['state'])) {
                throw new RuntimeException('Faltan datos para actualizar el estado de firma.');
            }
            $res = updateHasSigned($_POST['id'], $_POST['state']);
        } elseif ($_POST['action'] === 'toggleAbsent') {
            if (!isset($_POST['id']) || !isset($_POST['state'])) {
                throw new RuntimeException('Faltan datos para actualizar el estado de asistencia.');
            }
            $res = updateWillBeAbsent($_POST['id'], $_POST['state']);
        } elseif ($_POST['action'] === 'addComment') {
            if (!isset($_POST['id']) || !isset($_POST['comment']) || !isset($_POST['author'])) {
                throw new RuntimeException('Faltan datos para agregar un comentario.');
            }
            $res = insertComment($_POST['id'], $_POST['comment'], $_POST['author']);
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
get_head('FA');
?>

<body style="display: block;">
    <?php require_once INCLUDES_DIR . '/templates/header.php';
get_header('Firma de Actas');
?>

    <main class="container content marco">
        <div class="sectionsFA">
            <h3>Hola Mundo</h3>
            <p>Hola Mundo</p>
        </div>
        <br>

        <!-- TABLA DE PROGRAMAS -->
        <table class="table table-white table-nostriped" id="programsTable">
            <thead class="thead-dark">
                <tr>
                    <th style="width: 25%;">Programa</th>
                    <th style="width: 25%;">Materia</th>
                    <th style="width: 25%;">Profesor</th>
                    <th style="width: 25%;">Acciones</th>
                </tr>
            </thead>
            <tbody id="programsTableBody">
                <!-- Aquí se insertarán las filas dinámicamente -->
            </tbody>
        </table>
        <br>

        <!-- BOTONES INFERIORES -->
        <div class="d-flex justify-content-between">
            <button id="confirmChanges" class="btn btn-outline-success w-50" style="width: 200px;" disabled>
                <span>Confirmar Cambios</span>
            </button>
            <button id="generateReport" class="btn btn-outline-primary" style="width: 200px;" data-filename="reporte_programas">
                <span>Generar Reporte</span>
            </button>
        </div>

        <!-- NUMERO DE PROGRAMAS SELECCIONADOS EN CHECKBOXES -->
        <div id="selectedCountContainer">
            <p style="margin-top:15px; font-size: 20px; font-weight: bold;">Programas seleccionados: 
                <span id="selectedCount">0</span>
            </p>
        </div>
    </main>

    <div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel">
        <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Más grande y centrado -->
            <div class="modal-content shadow-lg rounded-3">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="commentModalLabel">Agregar comentario</h5>
                    <button type="button" class="btn-close btn-close-white" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body bg-light">
                    <div class="mb-3">
                        <label for="commentText" class="form-label fw-semibold">Comentario</label>
                        <textarea id="commentText" class="form-control" placeholder="Escribe un comentario..." rows="4"></textarea>
                    </div>
                    <div>
                        <label for="commentAuthor" class="form-label fw-semibold">Autor</label>
                        <input type="text" id="commentAuthor" class="form-control" placeholder="Nombre o alias">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="saveCommentBtn">Añadir</button>
                </div>
            </div>
        </div>
    </div>


    <?php include INCLUDES_DIR . '/templates/footer.php'; ?>

    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/util.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/FA/scripts.js"></script>
</body>

</html>
