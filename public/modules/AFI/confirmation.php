<?php
require_once '../../../includes/config/constants.php';
require_once INCLUDES_DIR . "/utilities/database.php";
require_once INCLUDES_DIR . "/models/student.php";

if (!isset($_GET['token']) || strlen($_GET["token"]) != 64) {
    header("Location: " . BASE_URL);
    exit();
}

$msg = "";
$title = "";
$subtitle = "";
$status = true;
$token = $_GET["token"];
$student = getStudentByID(getStudentIDByToken($token));

if ($student) {
    switch ($student->getAfi()) {
        case true:
            $title = "¡Aviso ya confirmado!";
            $subtitle = "Verificación para: " . $student->getEmail();
            $msg = "Ya has confirmado tus avisos de fechas importantes. 🎉";
            break;
        default:
            $res = updateStudentFieldBoolean($student->getUlsaId(), 'afi', true);
            if ($res != 0) {
                $title = "¡Aviso confirmado!";
                $subtitle = "Verificación para: " . $student->getEmail();
                $msg = "Gracias por confirmar tus avisos de fechas importantes. 🎉";
            } else {
                $title = "¡Ups! Hubo un problema...";
                $subtitle = "Disculpa la molesita " . ucfirst($student->getName()) . ", intenta nuevamente más tarde. 😥";
                $msg = "Si el problema persiste, por favor contacta a su jefe de posgrado.";
                $status = false;
            }
            break;
    }

}
?>
<!DOCTYPE html>

<?php
require_once INCLUDES_DIR . '/templates/head.php';
get_head("Confirmación AFI");
?>

<body style="display: block;">
    <?php require_once INCLUDES_DIR . '/templates/header.php';
get_header("Confirmación de Avisos de Fechas Importantes");
?>

    <main class="container content marco">
        <div class="d-flex flex-column align-items-center">
            <div class="mb-4">
                <span
                    class="d-flex justify-content-center align-items-center rounded-circle <?= $status ? "bg-success" : "bg-danger" ?> text-white"
                    style="width: 8rem; height: 8rem;">
                    <i class="far <?= $status ? "fa-check-circle" : "fa-times-circle" ?> fa-4x"></i>
                </span>
            </div>
            <div class="text-center">
                <h3 class="display-4 font-weight-bold text-danger mb-4">
                    <?= $title ?>
                </h3>
                <span class="text-muted lead">
                    <b class="h4"><?= $subtitle ?></b>
                    <br><br>
                    <p><?= $msg ?></p>
                </span>
            </div>
        </div>
    </main>

    <?php include INCLUDES_DIR . '/templates/footer.php'; ?>

    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>
</body>

</html>