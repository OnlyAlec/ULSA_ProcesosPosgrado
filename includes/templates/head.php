<?php
function get_head($title)
{
    echo '<head>
        <title>' . $title . ' | Posgrados</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link rel="stylesheet" href="' . ASSETS_PATH . '/css/bootstrap-ulsa.min.css" type="text/css">
        <link rel="stylesheet" href="' . ASSETS_PATH . '/css/jquery-ui.css" type="text/css">
        <link rel="stylesheet" href="' . ASSETS_PATH . '/css/indivisa.css" type="text/css">
        <link rel="stylesheet" href="' . ASSETS_PATH . '/css/style.css" type="text/css">
        <link rel="stylesheet" href="' . ASSETS_PATH . '/css/fa_all.css" type="text/css">
        
        <script src="' . ASSETS_PATH . '/js/util.js"></script>
    </head>';
}