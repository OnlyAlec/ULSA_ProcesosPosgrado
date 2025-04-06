<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config/constants.php'; ?>
<!DOCTYPE html>

<?php
require_once INCLUDES_DIR . '/templates/head.php';
get_head("AFI");
?>

<body style="display: block;">
    <?php
    require_once INCLUDES_DIR . '/templates/header.php';
get_header("Homepage");
?>

    <main class="container content marco">
        <div class="row">
            <?php
        $modules_dir = MODULES_DIR;
$modules_file_config = CONFIG_PATH . '/module_descriptions.json';

// Check if directory exists
if (!is_dir($modules_dir)) {
    echo '<div class="alert alert-danger">Modules directory not found!</div>';
} else {
    $modules = scandir($modules_dir);
    $valid_modules = [];

    // Get module metadata if available
    $module_info = [];
    if (file_exists($modules_file_config)) {
        $module_info = json_decode(file_get_contents($modules_file_config), true);
    }

    // Filter and sort modules
    foreach ($modules as $module) {
        if ($module !== "." && $module !== ".." && is_dir("$modules_dir/$module")) {
            $valid_modules[] = $module;
        }
    }

    // Sort alphabetically
    sort($valid_modules);

    // Display modules
    foreach ($valid_modules as $module) {
        $module_path = htmlspecialchars(BASE_URL . "/modules/$module/index.php", ENT_QUOTES, 'UTF-8');
        $name = $module_info[$module]['name'] ?? ucfirst($module);
        $description = $module_info[$module]['description'] ?? 'This is a description of the module';
        $icon = $module_info[$module]['icon'] ?? 'fas fa-cube';
        ?>
                    <div class="col-md-4 col-sm-6 col-12 mb-4">
                        <div class="card h-100 border-primary">
                            <div class="card-body">
                                <h4 class="card-title">
                                    <i class="<?= $icon ?>"></i> <?= $name ?>
                                </h4>
                                <p class="card-text"><?= htmlspecialchars($description) ?></p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="<?= $module_path ?>" class="btn btn-primary w-100">Acceder</a>
                            </div>
                        </div>
                    </div>
                    <?php
    }

    if (empty($valid_modules)) {
        echo '<div class="col-12"><div class="alert alert-info">No modules available.</div></div>';
    }
}
?>
        </div>

    </main>
    <?php include INCLUDES_DIR . '/templates/footer.php'; ?>

    <script src="<?= ASSETS_PATH ?>/js/jquery.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/popper.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/util.js"></script>
    <script src="<?= ASSETS_PATH ?>/js/sidebarmenu.js"></script>

</body>

</html>