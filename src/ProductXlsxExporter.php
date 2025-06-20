<?php
declare(strict_types=1);

namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Exception;

class ProductXlsxExporter
{
    private string $outputDir;

    public function __construct(string $outputDir)
    {
        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
            throw new Exception("Cannot create dir: {$outputDir}");
        }
        $this->outputDir = rtrim($outputDir, '/\\');
    }

    public function export(array $products): string
    {
        if (empty($products)) {
            throw new Exception('No products to export.');
        }

        $sheet = (new Spreadsheet())->getActiveSheet();
        $sheet->fromArray([
            ['Product ID','Offer ID','Has FBO Stocks','Has FBS Stocks','Archived','Discounted']
        ], null, 'A1');

        // Данные
        $row = 2;
        foreach ($products as $p) {
            $sheet->setCellValue("A{$row}", (string)($p['product_id'] ?? ''));
            $sheet->setCellValue("B{$row}", (string)($p['offer_id']   ?? ''));

            $sheet->setCellValue(
                "C{$row}",
                ($p['has_fbo_stocks']  ?? false) ? 'yes' : 'no'
            );
            $sheet->setCellValue(
                "D{$row}",
                ($p['has_fbs_stocks']  ?? false) ? 'yes' : 'no'
            );
            $sheet->setCellValue(
                "E{$row}",
                ($p['archived']        ?? false) ? 'yes' : 'no'
            );
            $sheet->setCellValue(
                "F{$row}",
                ($p['is_discounted']   ?? false) ? 'yes' : 'no'
            );

            $row++;
        }

        $filename = 'ozon_products_' . date('Ymd_His') . '.xlsx';
        $path     = "{$this->outputDir}/{$filename}";

        (new Xlsx($sheet->getParent()))->save($path);

        return $path;
    }

}
