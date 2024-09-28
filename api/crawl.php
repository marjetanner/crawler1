<?php

header('Content-Type: application/json'); // Response type is JSON


function crawl($url) {

    $html = file_get_contents($url);

    // Check if we successfully fetched the HTML
    if ($html === FALSE) {
        return ['error' => 'Unable to access site'];
    }

    // Load the HTML into DOMDocument for parsing
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

$urlTo = 'https://www.euronics.ee/';

$result = crawl($urlTo);

echo json_encode($result);

