<?php
error_reporting(E_ALL);

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, $severity, $severity, $file, $line);
});

try {
    $userId = isset($_GET['userId']) ? $_GET['userId'] : null;
    $apiKey = isset($_GET['apiKey']) ? $_GET['apiKey'] : null;

    if ($userId === null || $apiKey === null || $apiKey !== 'your_api_key') { // replace 'your_api_key' with your actual API key
        http_response_code(400);
        echo json_encode(['error' => 'Missing userId or API key']);
        exit;
    }

    $userScriptPath = "user/" . $userId . "/script.php";
    if (!file_exists($userScriptPath)) {
        http_response_code(404);
        echo json_encode(['error' => 'User script not found']);
        exit;
    }

    $scriptContents = file_get_contents($userScriptPath);
    preg_match("/'AES-256-CBC', '(.*?)', 0, substr\('(.*?)', 0, 16\)/", $scriptContents, $matches);
    $key = $matches[1] ?? null;
    $iv = substr($matches[2], 0, 16);

    if ($key === null || $iv === null) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to extract encryption key or IV from user script']);
        exit;
    }


    $date = isset($_GET['date']) ? DateTime::createFromFormat('Y-m-d', $_GET['date'])->format('Y-m-d') : date('Y-m-d');
    $logFilePath = "user/" . $userId . "/" . $date . "/log.txt";
    if (!file_exists($logFilePath)) {
        http_response_code(404);
        echo json_encode(['error' => 'Log not found']);
        exit;
    }

    $encryptedData = base64_decode(file_get_contents($logFilePath));
    $decryptedData = openssl_decrypt($encryptedData, 'AES-256-CBC', $key, 0, $iv);
    if ($decryptedData === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Decryption failed']);
        exit;
    }
    $data = json_decode($decryptedData, true);
    if ($data === null) {
        http_response_code(500);
        echo json_encode(['error' => 'JSON decoding failed']);
        exit;
    }
        $data = json_decode($decryptedData, true);

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    exit;
}
?>
