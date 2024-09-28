<?php

header('Content-Type: application/json');

function crawl($url, $apiKey) {

    $context = stream_context_create([
        'http' => [
            'header' => [
                "User-Agent: PHP\r\n",
                "Authorization: Bearer $apiKey\r\n"
            ]
        ]
    ]);


    $html = @file_get_contents($url, false, $context);


    if ($html === FALSE) {
        return ['error' => 'Unable to access site'];
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $xpath = new DOMXPath($dom);

    $categories = [];
    foreach ($xpath->query('//span[@class="nav-item__label-name"]') as $categoryNode) {
        $categoryName = trim($categoryNode->nodeValue);
        $descriptionNode = $categoryNode->parentNode->getElementsByTagName('p')->item(0);
        $categoryDescription = $descriptionNode ? trim($descriptionNode->nodeValue) : '';

        $categories[] = [
            'name' => $categoryName,
            'description' => $categoryDescription
        ];
    }

    return [
        'categories' => $categories
    ];
}


$apiKey = 'TEST?KEY_112';
$urlTo = 'https://www.euronics.ee/?api_key=' . $apiKey;  // <-- API key added to the URL as a query parameter

$result = crawl($urlTo,$apiKey);

// Return the result as a JSON
echo json_encode($result);
