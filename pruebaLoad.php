<?php
require __DIR__ . '/../vendor/autoload.php';
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Ods;

$inputFileName='./contratosgalicia.ods';
$reader = new Ods();
$reader->load($inputFileName);
var_dump($inputFileName);
//$spreadsheet = \PhpOffice\PhpSpreadsheet\Readerhoja::load('./contratosgalicia.ods');
?>