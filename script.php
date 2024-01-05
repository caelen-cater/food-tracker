<?php
function processUPC($upc, $userId = 0) {
    if ($userId <= 0) {
        return ['error' => 'User ID must be greater than 0'];
    }

    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $domain = "$protocol://$_SERVER[HTTP_HOST]";
    $apiKey = "your_api_key"; // replace with your actual API key
    $upcApiUrl = $domain . "/api/upc/index.php?upc=" . $upc . "&apiKey=" . $apiKey;
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
            $defaultScript = "<?php\n\n\$data = \$_GET;\n\$date = date('Y-m-d');\n\$dateFolderPath = __DIR__ . '/' . \$date;\nif (!file_exists(\$dateFolderPath)) {\n    mkdir(\$dateFolderPath, 0777, true);\n}\n\$encryptedData = base64_encode(openssl_encrypt(json_encode(\$data), 'AES-256-CBC', '$encryption_key', 0, substr('$encryption_key', 0, 16)));\nfile_put_contents(\$dateFolderPath . '/log.txt', \$encryptedData);\n\n?>";
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
