<?php

if (!defined('VENDOR_DIR')) {
    define('VENDOR_DIR', __DIR__ . '/vendor');
}
if (!defined('INCLUDES_DIR')) {
    define('INCLUDES_DIR', __DIR__ . '/includes');
}
require_once INCLUDES_DIR . '/utilities/database.php';
require_once INCLUDES_DIR . '/utilities/handleErrors.php';

function showTestResult($functionName, $result, $error = null)
{
    echo "=================================================\n";
    echo "Probando función: $functionName\n";

    if ($error !== null) {
        echo '❌ Error: ' . $error . "\n";
    } else {
        echo "✅ Resultado exitoso:\n";
        if (is_array($result)) {
            echo '- Total de registros: ' . count($result) . "\n";
            if (count($result) > 0) {
                echo '- Primer registro: ' . print_r($result[0], true) . "\n";
            }
        } elseif (is_object($result)) {
            echo '- Objeto: ' . print_r($result, true) . "\n";
        } else {
            echo '- Valor: ' . print_r($result, true) . "\n";
        }
    }
    echo "=================================================\n\n";
}

function runTest($functionName, $callback)
{
    try {
        $result = $callback();
        showTestResult($functionName, $result);
        return true;
    } catch (Exception $e) {
        showTestResult($functionName, null, $e->getMessage());
        return false;
    }
}

echo "Iniciando pruebas de database.php\n\n";

runTest('getDatabaseConnection', function () {
    $connection = getDatabaseConnection();
    return $connection ? 'Conexión establecida' : 'Error de conexión';
});

runTest('getStudents', function () {
    $students = getStudents();
    return $students;
});

$testUlsaId = '209179';
runTest("getStudentByUlsaID('$testUlsaId')", function () use ($testUlsaId) {
    try {
        $student = getStudentByUlsaID($testUlsaId);
        return $student ?: "No se encontró el estudiante con ULSA ID: $testUlsaId";
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

$testStudentId = 49;
runTest("getStudentByID($testStudentId)", function () use ($testStudentId) {
    try {
        $student = getStudentByID($testStudentId);
        return $student === null ? "No se encontró el estudiante con ID: $testStudentId" : $student;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

runTest('getMastersPrograms', function () {
    try {
        $programs = getMastersPrograms();
        return empty($programs) ? 'No se encontraron programas de maestría' : $programs;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

runTest('getSpecialtyPrograms', function () {
    try {
        $programs = getSpecialtyPrograms();
        return empty($programs) ? 'No se encontraron programas de especialidad' : $programs;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

runTest('getPrograms', function () {
    try {
        $programs = getPrograms();
        return empty($programs) ? 'No se encontraron programas' : $programs;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

$testProgramId = 2;
runTest("getProgramByID($testProgramId)", function () use ($testProgramId) {
    try {
        $program = getProgramByID($testProgramId);
        return $program === null ? "No se encontró el programa con ID: $testProgramId" : $program;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

$testProgramName = 'Maestría en Ciberseguridad';
runTest("getProgramByName('$testProgramName')", function () use ($testProgramName) {
    try {
        $program = getProgramByName($testProgramName);
        return $program === null
            ? "No se encontró el programa con nombre: $testProgramName"
            : $program;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

$testConfigType = 'email';
runTest("getConfig('$testConfigType')", function () use ($testConfigType) {
    try {
        $config = getConfig($testConfigType);
        return $config === '' ? "No se encontró configuración para: $testConfigType" : $config;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

$testStudentIdForToken = 1;
runTest("getToken($testStudentIdForToken)", function () use ($testStudentIdForToken) {
    try {
        $token = getToken($testStudentIdForToken);
        return $token === ''
            ? "No se encontró token para el estudiante ID: $testStudentIdForToken"
            : $token;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

$testToken = 'abc123xyz';
runTest("getStudentIDByToken('$testToken')", function () use ($testToken) {
    try {
        $studentId = getStudentIDByToken($testToken);
        return $studentId === ''
            ? "No se encontró estudiante con el token: $testToken"
            : $studentId;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

// ! === Modificacion de datos ===

$testStudentIdForInsert = 50;
$testTokenForInsert = 'test_' . time();
runTest("insertToken($testStudentIdForInsert, '$testTokenForInsert')", function () use (
    $testStudentIdForInsert,
    $testTokenForInsert,
) {
    try {
        $result = insertToken($testStudentIdForInsert, $testTokenForInsert);
        return $result ? 'Token insertado correctamente' : 'No se pudo insertar el token';
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

$testUlsaIdForUpdate = '239277';
$testField = 'sed';
$testValue = 1;
runTest(
    "updateStudentFieldBoolean('$testUlsaIdForUpdate', '$testField', $testValue)",
    function () use ($testUlsaIdForUpdate, $testField, $testValue) {
        try {
            $result = updateStudentFieldBoolean($testUlsaIdForUpdate, $testField, $testValue);
            return $result ? 'Campo actualizado correctamente' : 'No se pudo actualizar el campo';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    },
);

$testConfigTypeForUpdate = 'test_config_' . time();
$testConfigValue = 'test_value';
runTest("updateConfig('$testConfigTypeForUpdate', '$testConfigValue')", function () use (
    $testConfigTypeForUpdate,
    $testConfigValue,
) {
    try {
        $result = updateConfig($testConfigTypeForUpdate, $testConfigValue);
        return $result
            ? 'Configuración actualizada correctamente'
            : 'No se pudo actualizar la configuración';
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

$testStudentIdForTokenUpdate = 72;
$testTokenForUpdate = 'updated_' . time();
runTest("updateToken($testStudentIdForTokenUpdate, '$testTokenForUpdate')", function () use (
    $testStudentIdForTokenUpdate,
    $testTokenForUpdate,
) {
    try {
        $result = updateToken($testStudentIdForTokenUpdate, $testTokenForUpdate);
        return $result ? 'Token actualizado correctamente' : 'No se pudo actualizar el token';
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

echo "\nPruebas completadas.\n";
