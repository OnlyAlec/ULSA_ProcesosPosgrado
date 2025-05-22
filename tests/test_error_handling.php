<?php

if (!defined('VENDOR_DIR')) {
    define('VENDOR_DIR', __DIR__ . '/vendor');
}
if (!defined('INCLUDES_DIR')) {
    define('INCLUDES_DIR', __DIR__ . '/includes');
}
require_once INCLUDES_DIR . '/utilities/database.php';
require_once INCLUDES_DIR . '/utilities/handleErrors.php';

function testErrorHandling($functionName, $params = [])
{
    echo "Probando $functionName...\n";

    try {
        $result = call_user_func_array($functionName, $params);

        echo 'Resultado: ';
        if (is_object($result)) {
            echo 'Objeto ' . get_class($result) . "\n";
        } elseif (is_array($result)) {
            echo 'Array con ' . count($result) . " elementos\n";
        } else {
            echo var_export($result, true) . "\n";
        }

        if (ErrorList::hasErrors()) {
            echo "ErrorList contiene:\n";
            foreach (ErrorList::getAll() as $error) {
                echo "- $error\n";
            }
        } else {
            echo "No hay errores en ErrorList\n";
        }
    } catch (\RuntimeException $e) {
        echo 'Excepción RuntimeException: ' . $e->getMessage() . "\n";
    } catch (\Exception $e) {
        echo 'Excepción: ' . $e->getMessage() . "\n";
    }

    echo "\n";
    ErrorList::clear();
}

echo "=== Prueba de manejo de conexión ===\n";
$originalEnv = $_ENV;
$_ENV['DB_HOST'] = 'host_no_existente';
try {
    getDatabaseConnection();
} catch (\RuntimeException $e) {
    echo 'RuntimeException capturada correctamente: ' . $e->getMessage() . "\n";
}
$_ENV = $originalEnv;
echo "\n";

echo "=== Prueba de manejo con ErrorList ===\n";
testErrorHandling('getStudentByUlsaID', ['ID_NO_EXISTENTE']);
testErrorHandling('getStudentByUlsaID', ['239277']);

echo "=== Prueba de función de actualización ===\n";
testErrorHandling('updateStudentFieldBoolean', ['239277', 'sed', true]);

echo "Pruebas completadas.\n";
