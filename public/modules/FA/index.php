<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php';
require_once INCLUDES_DIR . '/utilities/database.php';
require_once INCLUDES_DIR . '/utilities/responseHTTP.php';
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($_POST['action']) {
            case 'getProgramSubjects':
                $res = getProgramSubjects();
                break;

            case 'toggleSigned':
                if (!isset($_POST['id']) || !isset($_POST['state'])) {
                    throw new RuntimeException('Faltan datos para actualizar el estado de firma.');
                }
                $res = updateHasSigned($_POST['id'], $_POST['state']);
                break;

            case 'toggleAbsent':
                if (!isset($_POST['id']) || !isset($_POST['state'])) {
                    throw new RuntimeException(
                        'Faltan datos para actualizar el estado de asistencia.',
                    );
                }
                $res = updateWillBeAbsent($_POST['id'], $_POST['state']);
                break;

            case 'addComment':
                if (!isset($_POST['id']) || !isset($_POST['comment']) || !isset($_POST['author'])) {
                    throw new RuntimeException('Faltan datos para agregar un comentario.');
                }
                $res = insertComment($_POST['id'], $_POST['comment'], $_POST['author']);
                break;

            case 'uploadEvidence':
                if (!isset($_POST['id']) || !isset($_FILES['file'])) {
                    throw new RuntimeException('Faltan datos para subir la evidencia.');
                }
                $filePath = '/uploads/FA/' . $_FILES['file']['name'];
                if (
                    move_uploaded_file(
                        $_FILES['file']['tmp_name'],
                        $_SERVER['DOCUMENT_ROOT'] . $filePath,
                    )
                ) {
                    $res = insertEvidence($_POST['id'], $filePath);
                } else {
                    throw new RuntimeException('Error al mover el archivo.');
                }
                break;

            case 'getCommentsAndEvidence':
                if (!isset($_POST['id'])) {
                    throw new RuntimeException(
                        'Faltan datos para obtener los comentarios y la evidencia.',
                    );
                }
                $res = getCommentsAndEvidence($_POST['id']);
                break;

            default:
                throw new RuntimeException('Acción no reconocida.');
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
get_head('FA');
?>

<body style="display: block;">
    <?php
    require_once INCLUDES_DIR . '/templates/header.php';
get_header('Firma de Actas');
?>

    <main class="container content marco">
        <div class="sectionsFA">
            <h3>Lista de Materias Impartidas</h3>
            <p>Este módulo permite gestionar el proceso de firma de actas de calificaciones finales por parte de los docentes. A continuación, se describen las funcionalidades principales:</p>
            <ul>
                <li><strong>Captura de Firma:</strong> Las actas de calificaciones finales pueden ser marcadas como firmadas o no firmadas.</li>
                <li><strong>Captura de Ausencia:</strong> Permite marcar a los profesores que no podrán asistir a firmar en las fechas establecidas, indicando que el director firma en su lugar.</li>
                <li><strong>Captura de Observaciones:</strong> Permite registrar observaciones de autoridades académicas.</li>
                <li><strong>Anexar Documentos:</strong> Se pueden adjuntar documentos como correos electrónicos, boletos de avión, etc., para justificar ausencias y para auditorías.</li>
            </ul>
        </div>
        <br>

        <div class="d-flex justify-content-end mb-3">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="selectAll">
                <label class="form-check-label" for="selectAll">Todos</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="filterSigned">
                <label class="form-check-label" for="filterSigned">Firmados</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="filterAbsent">
                <label class="form-check-label" for="filterAbsent">Ausentes</label>
            </div>
        </div>

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
        <div class="d-flex justify-content-end mb-3">
            <button id="generateReport" class="btn btn-outline-primary" style="width: 200px;" data-filename="reporte_programas">
                <span>Generar Reporte</span>
            </button>
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
