<?php

header('Content-Type: application/json');

// Function to crawl the site and extract required data
function crawl($url, $apiKey) {
    // Create a stream context for making the request
    $context = stream_context_create([
        'http' => [
            'header' => [
                "User-Agent: PHP\r\n",
                "Authorization: Bearer $apiKey\r\n"
            ]
        ]
    ]);

    // Get the HTML content of the main page
    $html = @file_get_contents($url, false, $context);
    if ($html === FALSE) {
        return ['error' => 'Unable to access site'];
    }

    // Load the main page's HTML into DOMDocument and create XPath
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Collect categories and their URLs
    $categories = [];
    foreach ($xpath->query('/html/body/header/div[2]/div[3]/div/nav/ul/li/div[2]/div[2]/div/div/div/div/a') as $categoryNode) {
        $categoryName = trim($categoryNode->nodeValue);
        $categoryUrl = $categoryNode->getAttribute('href'); // Get the link for the category
        $categories[] = [
            'name' => $categoryName,
            'url' => $categoryUrl
        ];
    }

    // Now, for each category, follow the URL and gather product information
    $categoryProducts = [];
    foreach ($categories as $category) {
        $categoryUrl = $category['url'];

        // Handle relative URLs by adding the domain if necessary
        if (strpos($categoryUrl, 'http') === false) {
            $categoryUrl = 'https://www.euronics.ee' . $categoryUrl;
        }

        // Get the HTML content of the category page
        $categoryHtml = @file_get_contents($categoryUrl, false, $context);
        if ($categoryHtml === FALSE) {
            continue; // Skip if we can't access the category page
        }

        $categoryDom = new DOMDocument();
        @$categoryDom->loadHTML($categoryHtml);
        $categoryXpath = new DOMXPath($categoryDom);

        // Collect product information from the category page
        $products = [];
        foreach ($categoryXpath->query('//article[contains(@class, "product-card")]') as $productNode) {
            // Extract product name, price, and URL from the data attributes
            $productName = $productNode->getAttribute('data-product-name');
            $productPrice = $productNode->getAttribute('data-product-price');
            $productUrlNode = $categoryXpath->query('.//a[contains(@class, "product_name")]', $productNode)->item(0);
            $productUrl = $productUrlNode ? $productUrlNode->getAttribute('href') : '';

            if ($productName) {
                $products[] = [
                    'name' => $productName,
                    'price' => $productPrice ?: 'N/A',
                    'url' => $productUrl
                ];
            }
        }

        // Store products and product count for this category
        $categoryProducts[$category['name']] = [
            'count' => count($products),
            'products' => $products
        ];
    }

    // Return collected data
    return [
        'categories' => $categoryProducts
    ];
}

// Define API key and URL
$apiKey = 'TEST_KEY_112';
$urlTo = 'https://www.euronics.ee/?api_key=' . $apiKey;

// Call the function to crawl the URL
$result = crawl($urlTo, $apiKey);

// Return the result as a JSON response
echo json_encode($result);
?>
