<?php
$keys = include('keys.php');
$apiKeyScript = $keys['script'];
$apiKeyRead = $keys['read'];

$upc = isset($_GET['upc']) ? $_GET['upc'] : null;

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$hostDomain = "$protocol://$_SERVER[HTTP_HOST]";

try {
    if ($upc) {
        $urlScript = "$hostDomain/script.php";
        $ch = curl_init($urlScript);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'upc' => $upc,
            'userId' => 1,
            'apiKey' => $apiKeyScript
        )));
        $jsonScript = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), $httpCode);
        } elseif ($httpCode != 200) {
            throw new Exception("Received HTTP status code", $httpCode);
        }
        curl_close($ch);
    }

    $urlRead = "$hostDomain/read.php?userId=1&apiKey=$apiKeyRead";
    $jsonRead = @file_get_contents($urlRead);
    if ($jsonRead === FALSE) {
        $httpCode = http_response_code();
        throw new Exception("Failed to get contents", $httpCode);
    }

} catch (Exception $e) {
    echo 'HTTP status code: ',  $e->getCode(), "\n";
}

    $data = json_decode($jsonRead, true);

    $sumCalories = 0;
    $sumProtein = 0;
    $sumCarbohydrates = 0;

    if (isset($data['entries'])) {
        foreach ($data['entries'] as $entry) {
            if (isset($entry['data']['labelNutrients'])) {
                $nutrients = json_decode($entry['data']['labelNutrients'], true);
                $sumCalories += isset($nutrients['calories']['value']) ? $nutrients['calories']['value'] : 0;
                $sumProtein += isset($nutrients['protein']['value']) ? $nutrients['protein']['value'] : 0;
                $sumCarbohydrates += isset($nutrients['carbohydrates']['value']) ? $nutrients['carbohydrates']['value'] : 0;
            }
        }
    }

    $output = array(
        "Calories" => $sumCalories,
        "Protein" => $sumProtein,
        "Carbohydrates" => $sumCarbohydrates
    );

    try {
        header('Content-Type: application/json');
        echo json_encode($output);
    } catch (Exception $e) {
        echo 'HTTP status code: ',  $e->getCode(), "\n";
    }
