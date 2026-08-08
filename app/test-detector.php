<?php

require __DIR__ . '/vendor/autoload.php';

use App\Service\TechnologyDetectorService;
use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();

$detector = new TechnologyDetectorService($client);

$url = $argv[1] ?? 'https://wordpress.org';

try {
    $result = $detector->analyze($url);

    echo "Technologies détectées pour {$url} :\n\n";

    print_r($result);
} catch (\Throwable $e) {
    echo "ERREUR : " . $e->getMessage() . PHP_EOL;
    exit(1);
}
