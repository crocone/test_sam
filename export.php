<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\OzonApiClient;
use App\ProductXlsxExporter;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$clientId = $_ENV['OZON_CLIENT_ID'] ?? exit("OZON_CLIENT_ID not set\n");
$apiKey   = $_ENV['OZON_API_KEY']   ?? exit("OZON_API_KEY not set\n");
$filterJson = $_ENV['OZON_FILTER_JSON'] ?? '{"visibility":"ALL"}';

$filter = json_decode($filterJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    exit("Invalid OZON_FILTER_JSON: " . json_last_error_msg() . "\n");
}

$client   = new OzonApiClient($clientId, $apiKey);
$products = $client->fetchAllProducts($filter, 100);

$exporter = new ProductXlsxExporter(__DIR__ . '/exports');
$file = $exporter->export($products);

echo "Done: {$file}\n";
