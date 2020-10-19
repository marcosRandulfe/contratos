<?php
require __DIR__ .'/../vendor/autoload.php';
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Ods as ReaderOds;

$inputFileName='./contratosgalicia.ods';
$reader = new ReaderOds();
$carga=$reader->load($inputFileName);
var_dump($carga);
//$spreadsheet = \PhpOffice\PhpSpreadsheet\Readerhoja::load('./contratosgalicia.ods');
?>