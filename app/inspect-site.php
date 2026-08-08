<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

$url = $argv[1] ?? null;

if (!$url) {
    echo "Usage: php inspect-site.php https://example.com\n";
    exit(1);
}

$client = HttpClient::create();

$response = $client->request('GET', $url, [
    'timeout' => 15,
    'max_redirects' => 5,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0',
    ],
]);

$html = $response->getContent();
$headers = $response->getHeaders(false);

echo "\n========== HEADERS ==========\n\n";

foreach ($headers as $name => $values) {
    echo strtoupper($name) . ": " . implode(', ', $values) . PHP_EOL;
}

$crawler = new Crawler($html);

echo "\n========== META ==========\n\n";

$crawler->filter('meta')->each(function (Crawler $node) {
    echo $node->outerHtml() . PHP_EOL;
});

echo "\n========== SCRIPTS ==========\n\n";

$crawler->filter('script')->each(function (Crawler $node) {
    $src = $node->attr('src');

    if ($src) {
        echo "SCRIPT: {$src}" . PHP_EOL;
    }
});

echo "\n========== CSS ==========\n\n";

$crawler->filter('link[rel="stylesheet"]')->each(function (Crawler $node) {
    $href = $node->attr('href');

    if ($href) {
        echo "CSS: {$href}" . PHP_EOL;
    }
});

echo "\n========== COOKIES ==========\n\n";

if (isset($headers['set-cookie'])) {
    foreach ($headers['set-cookie'] as $cookie) {
        echo $cookie . PHP_EOL;
    }
}

echo "\n========== HTML KEYWORDS ==========\n\n";

$keywords = [
    'wordpress',
    'woocommerce',
    'shopify',
    'prestashop',
    'drupal',
    'joomla',
    'symfony',
    'laravel',
    'react',
    'vue',
    'angular',
    'jquery',
    'bootstrap',
    'tailwind',
    'elementor',
    'divi',
    'next.js',
    'nuxt',
];

$htmlLower = strtolower($html);

foreach ($keywords as $keyword) {
    if (str_contains($htmlLower, $keyword)) {
        echo "FOUND: {$keyword}" . PHP_EOL;
    }
}
