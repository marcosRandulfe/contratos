<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
</head>
<body>
    <h1>Contratos de galicia</h1>
    <?php
    require __DIR__ .'/../vendor/autoload.php';
    use Goutte\Client;
    use Symfony\Component\HttpClient\HttpClient;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Ods;
    use PhpOffice\PhpSpreadsheet\Reader\Ods as ReaderOds;

    //Constantes
    define('NOMBRE_FICHERO','contratosgalicia.ods');

    //Subir Fichero
    $client = new Google_Client();
    // Get your credentials from the console
    $client->setClientId('1032605733423-mg308q50k5ttk2bcnj5omorv2u6aj95t.apps.googleusercontent.com');
    $client->setClientSecret('7h7-1DW0g85balewwYRI8x8b');
    $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
    $client->setScopes(array('https://www.googleapis.com/auth/drive.file'));

    if (isset($_GET['code']) || (isset($_SESSION['access_token']) && $_SESSION['access_token'])) {
        $codigos = [];
        $id = '';

        function get_code($url){
            $url_components = parse_url($url);
            // Use parse_str() function to parse the
            // string passed via URL
            parse_str($url_components['query'], $params);
            // Display result
            $id = $params['N'];
            return $id;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        //$inputFileName = './contratosgalicia.ods';
        /** Load $inputFileName to a Spreadsheet Object  **/
        //Se comprueba si la hoja de estilos esta vacia y sino se gurdan los códigos
        $spreadsheet;
        $inputFileName = './contratosgalicia.ods';
        if(file_exists($inputFileName)){
            echo "<p>Existe fichero</p>";
            $reader = new ReaderOds();
            $inputFileName = './contratosgalicia.ods';
            $spreadsheet = $reader->load($inputFileName);
        }else{
            $spreadsheet = new Spreadsheet();
        }
        $sheet = $spreadsheet->getActiveSheet();
        $fila = 2;
        if (!is_null($sheet->getCell('A1')) && !is_null($sheet->getCell('A2'))) {
            $codigo = $sheet->getCell('A' . $fila);
            //hacer mapa
            if (in_array($codigo, $codigos)) {
                $sheet->removeRow($fila);
                $fila--;
            } else {
                $codigos[] = $codigo;
            }
        }
        $GLOBALS['numero'] = $fila;
        //Colocación de los nombres de las columnas independientes
        $GLOBALS['sheet']->setCellValue('AG1', 'Fecha última modificación');
        $sheet->setCellValue('A1', 'id');
        $url = "https://www.contratosdegalicia.gal/rss/ultimas-publicacions.rss";
        $file = $url;
        $leer_texto = false;
        $is_item = false;
        $writter = new Ods($spreadsheet);

        $letra = 'B';
        $numero = 1;
        $indice = '';





                /*if (in_array($id, $GLOBALS['codigos'])) {
                    $GLOBALS['sheet']->setCellValue('A' . $GLOBALS['numero'], $id);
                }
                $dt =$crawler->filter("dt");
                if(count($dt)>0){
                    $dt->each(function ($node) {
                        $propiedad = $node->text();
                        $valor = $node->siblings()->text();
                        echo "<p>" . $node->text() . "</p>";
                        echo "<p>" . var_dump($valor) . "</p>";
                        echo "<p>Fila y columna: " . $GLOBALS['letra'] . $GLOBALS['numero'] . "</p>";
                        $GLOBALS['sheet']->setCellValue($GLOBALS['letra'] . $GLOBALS['numero'], $valor);
                        $GLOBALS['sheet']->setCellValue($GLOBALS['letra'] . '1', $propiedad);
                        $GLOBALS['letra'] = ++$GLOBALS['letra'];
                    });
                }*/
            }
        }

        $writter->save(NOMBRE_FICHERO);

            if (isset($_GET['code'])) {
                $client->fetchAccessTokenWithAuthCode($_GET['code']);
                $_SESSION['access_token'] = $client->getAccessToken();
            } else
                $client->setAccessToken($_SESSION['access_token']);

            $service = new Google_Service_Drive($client);
            //Insert a file
            $file = new Google_Service_Drive_DriveFile();
            $file->setName('fichero.ods');
            $file->setDescription('Contratos de galicia');
            $file->setMimeType('application/vnd.oasis.opendocument.spreadsheet');

            $data = file_get_contents(NOMBRE_FICHERO);

            $createdFile = $service->files->create($file, array(
                'data' => $data,
                'mimeType' => 'application/vnd.oasis.opendocument.spreadsheet',
                'uploadType' => 'multipart'
            ));

            print_r($createdFile);

    } else {
        $authUrl = $client->createAuthUrl();
        header('Location: ' . $authUrl);
        exit();
    }
