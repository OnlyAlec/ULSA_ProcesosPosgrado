<?php

function get_header($title)
{
    $menuItems = get_modules_links();
    $logo = ASSETS_PATH . "/img/logo_lasalle.png";
    $home = BASE_URL;
    $header = <<< HTML
    <div class="overlay"></div><!-- Dark Overlay element -->
    
    <aside id="sidebar" class="bg-light defaultShadow d-flex flex-column p-4">
        <div class="d-flex mainMain align-items-center mb-1">
            <div class="logotipo"><a href="https://lasalle.mx/" target="_blank">
                    <img src="$logo" id="logo" border="0" class="img-fluid">
                </a>
            </div>
            <div class="flex-grow-1 d-flex justify-content-end">
                <div class="d-flex mainMenu justify-content-center align-items-center">
                    <div class="max-h iconSesion">
                        <a href="#" class="iconOff max-h pl-3 d-flex justify-content-start align-items-center"><i
                                class="ing-salir"></i></a>
                    </div>
                    <div class="max-h">
                        <div class="bg-secondary rounded-circle pointer max-h max-w d-flex justify-content-center align-items-center"
                            id="dismiss">
                            <div class="text-white"><i class="ing-cancelar"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="accordion px-2" id="accordionMenu">
            <p class="mb-0 mt-3 ml-4 pl-1">
                <a class="d-block side-menu" href="$home">
                    <i class="fas fa-home mr-2" style="width: 20px; text-align: center;"></i>
                    Página Principal
                </a>
                <hr>
             </p>
            $menuItems
        </div>
    </aside>
    
    
    <header class="sticky-top bg-white bg-head">
        <div class="menu d-flex align-items-center" style="visibility: visible;">
            <div class="logotipo"><a href="https://lasalle.mx/" target="_blank">
                    <img id="logo" src="$logo" border="0" class="img-fluid">
                </a>
            </div>
            <div class="flex-grow-1 d-flex justify-content-end">
                <nav class="navbar navbar-expand-md d-none d-sm-flex">
                </nav>
    
                <div class="d-flex mainMenu justify-content-center align-items-center">
                    <div class="max-h iconSesion">
                        <a href="#" class="iconOff max-h pl-3 d-flex justify-content-start align-items-center"><i
                                class="ing-salir"></i></a>
                    </div>
                    <div class="max-h">
                        <span id="sidebarCollapse" style="font-size: 44px;"
                            class="ing-menu bg-white rounded-circle pointer max-w d-flex justify-content-center align-items-center"></span>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="row bg-light mx-0">
        <div class="marco">
            <div class="col-12">
                <div class="mx-0 py-3 marco">
                    <h4 class="text-info">GPP | <small>Gestión de Procesos de Posgrado</small></h4>
                    <h2 class="text-uppercase"> $title </h2>
                </div>
            </div>
        </div>
    </div>
    HTML;

    echo $header;
}

function get_modules_links()
{
    $modules_file_config = CONFIG_PATH . '/module_descriptions.json';
    $modules = scandir(MODULES_DIR);
    $valid_modules = [];
    $sideMenu = [];
    $module_info = [];

    if (file_exists($modules_file_config)) {
        $module_info = json_decode(file_get_contents($modules_file_config), true);
    }

    foreach ($modules as $module) {
        if ($module !== "." && $module !== ".." && is_dir(MODULES_DIR . "/$module")) {
            $valid_modules[] = $module;
        }
    }

    if (empty($valid_modules)) {
        return '<div class="col-12"><div class="alert alert-info">No modules available.</div></div>';
    }

    sort($valid_modules);
    foreach ($valid_modules as $module) {
        $module_path = htmlspecialchars(BASE_URL . "/modules/$module/index.php", ENT_QUOTES, 'UTF-8');
        $name = $module_info[$module]['name'] ?? ucfirst($module);
        $icon = $module_info[$module]['icon'] ?? 'fas fa-cube';
        $sideMenu[] = <<< HTML
                    <p class="mb-0 mt-3 ml-4 pl-1">
                        <a class="d-block side-menu" href="$module_path">
                            <i class="$icon mr-2" style="width: 20px; text-align: center;"></i> 
                            $name
                        </a>
                    </p>
                    HTML;
    }

    return implode("\n", $sideMenu);
}
