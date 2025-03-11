<?php
/*error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);*/
//require_once './include/bd_pdo.php';
require_once './include/util.php';
?>
<!DOCTYPE html>

<head>
    <title>Proyectos | Facultad de ingeniería</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="./css/bootstrap-ulsa.min.css" type="text/css">
    <link rel="stylesheet" href="./css/jquery-ui.css" type="text/css">
    <link rel="stylesheet" href="./css/indivisa.css" type="text/css">
    <link rel="stylesheet" href="./css/style.css" type="text/css">
    <link rel="stylesheet" href="./css/fa_all.css" type="text/css">

    <script src="./js/util.js"></script>
</head>

<body style="display: block;">
    <?php
    include("./include/header.php");
    ?>

    <main class="container content marco">
        <div class="row">
            <?php
            $modules_dir = "./modules/";

            // Check if directory exists
            if (!is_dir($modules_dir)) {
                echo '<div class="alert alert-danger">Modules directory not found!</div>';
            } else {
                $modules = scandir($modules_dir);
                $valid_modules = [];

                // Get module metadata if available
                $module_info = [];
                if (file_exists('./config/module_descriptions.json')) {
                    $module_info = json_decode(file_get_contents('./config/module_descriptions.json'), true);
                }

                // Filter and sort modules
                foreach ($modules as $module) {
                    if ($module !== "." && $module !== ".." && is_dir("$modules_dir$module")) {
                        $valid_modules[] = $module;
                    }
                }

                // Sort alphabetically
                sort($valid_modules);

                // Display modules
                foreach ($valid_modules as $module) {
                    $module_path = htmlspecialchars("$modules_dir$module/index.php", ENT_QUOTES, 'UTF-8');
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

    </main><!-- ./container -->

    <?php
    include("./include/footer.php");
    ?>

    <script src="./js/jquery.min.js"></script>
    <script src="./js/bootstrap/popper.min.js"></script>
    <script src="./js/bootstrap/bootstrap.min.js"></script>
    <script src="./js/util.js"></script>
    <script src="./js/sidebarmenu.js"></script>

</body>

</html>