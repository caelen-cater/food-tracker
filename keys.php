<?php
if (empty(debug_backtrace())) {
    echo '<h1>Missing Access</h1>';
    exit;
}

return [
    'script' => 'script.php_api_key',
    'read' => 'read.php_api_key',
    'fda' => 'WYNFR1AFXFUGh684FZWakn41WDSKX4gJsM3CyQys',
];
