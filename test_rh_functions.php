<?php
// Test script for resources/human functions

try {
    echo "Testing inclusion of modulos/recursos_humanos/functions.php...\n";
    require_once __DIR__ . '/modulos/recursos_humanos/functions.php';
    echo "SUCCESS: Included without error.\n";

    if (function_exists('conectarDB')) {
        echo "conectarDB exists.\n";
    }

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
