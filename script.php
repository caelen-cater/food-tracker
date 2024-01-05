<?php
$keys = include($_SERVER['DOCUMENT_ROOT'] . '/keys.php');

if (!isset($_GET['apikey']) || $_GET['apikey'] !== $keys['script']) {
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

function processUPC($upc, $userId = 0) {
    global $keys;

    if ($userId <= 0) {
        return ['error' => 'User ID must be greater than 0'];
    }

    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $domain = "$protocol://$_SERVER[HTTP_HOST]";
    $upcApiKey = $keys['upc'];
    $upcApiUrl = $domain . "/api/upc/index.php?upc=" . $upc . "&apiKey=" . $upcApiKey;
    $upcResponse = file_get_contents($upcApiUrl);
    $upcData = json_decode($upcResponse, true);

    $description = $upcData['description'];
    $category = $upcData['brandedFoodCategory'];
    $labelNutrients = $upcData['labelNutrients'];

    $date = date('Y-m-d');
    $userFolderPath = "user/" . $userId;
    $dateFolderPath = $userFolderPath . "/" . $date;
    if (!file_exists($dateFolderPath)) {
        mkdir($dateFolderPath, 0777, true);
    }

    $userScriptPath = $userFolderPath . "/script.php";
    if (!file_exists($userScriptPath)) {
        $encryption_key = bin2hex(openssl_random_pseudo_bytes(16));
        $defaultScript = "<?php
        \$encryption_key = '$encryption_key';
        \$data = \$_GET;
        \$date = date('Y-m-d');
        \$time = date('H:i:s');
        \$entryData = ['date' => \$date, 'time' => \$time, 'data' => \$data];
        \$dateFolderPath = __DIR__ . '/' . \$date;
        if (!file_exists(\$dateFolderPath)) {
            mkdir(\$dateFolderPath, 0777, true);
        }
        \$logFilePath = \$dateFolderPath . '/' . (count(scandir(\$dateFolderPath)) - 1) . '.txt';
        \$logData = [\$entryData];
        \$encryptedLogData = base64_encode(openssl_encrypt(json_encode(\$logData), 'AES-256-CBC', \$encryption_key, 0, substr(\$encryption_key, 0, 16)));
        file_put_contents(\$logFilePath, \$encryptedLogData);
        echo json_encode(['success' => 'Log created successfully']);
        ?>";
        file_put_contents($userScriptPath, $defaultScript);
    }

    $userScriptUrl = $domain . "/" . $userScriptPath . "?description=" . urlencode($description) . "&category=" . urlencode($category) . "&labelNutrients=" . urlencode(json_encode($labelNutrients));
    $userScriptResponse = file_get_contents($userScriptUrl);
    $userScriptData = json_decode($userScriptResponse, true);

    return $userScriptData;
}

$upc = $_GET['upc'];
$userId = isset($_GET['userId']) ? $_GET['userId'] : 0;
$data = processUPC($upc, $userId);
echo json_encode($data);
?>
