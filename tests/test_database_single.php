<?php

if (!defined('VENDOR_DIR')) {
    define('VENDOR_DIR', __DIR__ . '/vendor');
}
if (!defined('INCLUDES_DIR')) {
    define('INCLUDES_DIR', __DIR__ . '/includes');
}
require_once INCLUDES_DIR . '/utilities/database.php';
require_once INCLUDES_DIR . '/utilities/handleErrors.php';

if ($argc < 2) {
    echo "Uso: php test_database_single.php nombre_funcion [parametro1] [parametro2] ...\n";
    exit(1);
}

$functionName = $argv[1];
$params = array_slice($argv, 2);

if (!function_exists($functionName)) {
    echo "Error: La función '$functionName' no existe en database.php\n";
    exit(1);
}

try {
    echo "Ejecutando: $functionName(" . implode(', ', $params) . ")\n";

    $result = call_user_func_array($functionName, $params);

    echo "\nResultado:\n";
    if (is_array($result)) {
        echo 'Array con ' . count($result) . " elementos:\n";
        foreach ($result as $key => $value) {
            if (is_object($value)) {
                echo "[$key] => " . get_class($value) . " Object\n";
                foreach (get_object_vars($value) as $prop => $val) {
                    echo "    [$prop] => " .
                        (is_string($val) ? $val : var_export($val, true)) .
                        "\n";
                }
            } else {
                echo "[$key] => " . (is_string($value) ? $value : var_export($value, true)) . "\n";
            }
        }
    } elseif (is_object($result)) {
        echo 'Objeto de la clase ' . get_class($result) . ":\n";
        foreach (get_object_vars($result) as $prop => $val) {
            echo "[$prop] => " . (is_string($val) ? $val : var_export($val, true)) . "\n";
        }
    } else {
        echo var_export($result, true) . "\n";
    }

    if (
        class_exists('ErrorList') &&
        method_exists('ErrorList', 'hasErrors') &&
        ErrorList::hasErrors()
    ) {
        echo "\nErrores:\n";
        foreach (ErrorList::getAll() as $error) {
            echo "- $error\n";
        }
    }
} catch (Exception $e) {
    echo "\nError en la ejecución:\n";
    echo $e->getMessage() . "\n";
}
