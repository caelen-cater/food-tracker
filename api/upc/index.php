<?php
function getApiData($upc) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $domain = "$protocol://$_SERVER[HTTP_HOST]";
    $apiKey = trim(file_get_contents($domain . "/api/values/fdakey.txt"));

    $upcApiUrl = "https://api.upcitemdb.com/prod/trial/lookup?upc=" . $upc;
    $upcResponse = file_get_contents($upcApiUrl);
    $upcData = json_decode($upcResponse, true);

    $title = $upcData['items'][0]['title'];
    $brand = $upcData['items'][0]['brand'];

    $usdaSearchUrl = "https://api.nal.usda.gov/fdc/v1/foods/search?api_key=" . $apiKey . "&sortBy=dataType.keyword&sortOrder=asc&query=" . urlencode($title) . "&brandOwner=" . urlencode($brand);
    $usdaSearchResponse = file_get_contents($usdaSearchUrl);
    $usdaSearchData = json_decode($usdaSearchResponse, true);

    $fdcId = $usdaSearchData['foods'][0]['fdcId'];

    $usdaFoodUrl = "https://api.nal.usda.gov/fdc/v1/food/" . $fdcId . "?api_key=" . $apiKey;
    $usdaFoodResponse = file_get_contents($usdaFoodUrl);
    $usdaFoodData = json_decode($usdaFoodResponse, true);

    return $usdaFoodData;
}

$upc = $_GET['upc'];
$data = getApiData($upc);
echo json_encode($data);
?>
