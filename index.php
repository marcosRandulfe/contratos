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
    use PhpOffice\PhpSpreadsheet\Writter\Ods;
    use PhpOffice\PhpSpreadsheet\Reader\Ods as ReaderOds;

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    //Constantes
    define('NOMBRE_FICHERO','./contratosgalicia.ods');
    define('URL_BASE','https://www.contratosdegalicia.gal//licitacion?N=');
    define('NUM_INICIO', 5000);
    //Subir Fichero
    $client = new Google_Client();
    // Get your credentials from the console
    $client->setClientId('1032605733423-mg308q50k5ttk2bcnj5omorv2u6aj95t.apps.googleusercontent.com');
    $client->setClientSecret('7h7-1DW0g85balewwYRI8x8b');
    $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
    $client->setScopes(array('https://www.googleapis.com/auth/drive.file'));

    $codigos = new \Ds\Map();


    if (isset($_GET['code']) || (isset($_SESSION['access_token']) && $_SESSION['access_token'])) {
        $codigos = [];
        $id = '';
        $letra = 'B';
        $numero = 1;
        $indice = '';


        function leerDatosContrato($numero){
                $GLOBALS['numero']++;
                $GLOBALS['letra'] = 'B';
                $url = URL_BASE.$numero;
                echo "<p>" . $url . "</p>";
                //$id = get_code($url);
                $client = new Client();
                try{
                    $crawler = $client->request('GET', $url);
                    if($crawler==null){
                        return false;
                    }
                }catch(Exception $ex){
                    return false;
                }
                $GLOBALS['sheet']->setCellValue('A' . $GLOBALS['numero'], $id);

                //Calculo del historico
                $fecha = '';
                if (!empty($historico = $crawler->filterXPath("//table[@id='tabHistorico']//tr[1]//td[1]"))){
                    echo "<hr/>";
                    var_dump(count($historico));
                    echo "<hr/>";
                    if(count($historico)>0){
                        $fecha = $historico->text();
                    }
                }
                $GLOBALS['sheet']->setCellValue('AG' . $GLOBALS['numero'], $fecha);


               /* if (in_array($id, $GLOBALS['codigos'])) {
                    $GLOBALS['sheet']->setCellValue('A' . $GLOBALS['numero'], $id);
                }*/
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
                }
        }

        error_reporting(E_ALL);
        ini_set('display_errors', '1');


        //Se comprueba si la hoja de estilos esta vacia y sino se gurdan los códigos
        $spreadsheet;
        $inputFileName = './contratosgalicia.ods';
        if(file_exists($inputFileName)){
            echo "<p>Existe fichero</p>";
            $reader = new ReaderOds();
            //$spreadsheet = IOhoja::load('./odsPrueba.ods');
            $inputFileName = './contratosgalicia.ods';
            $spreadsheet = $reader->load($inputFileName);
        }else{
            $spreadsheet = new Spreadsheet();
        }
        $sheet = $spreadsheet->getActiveSheet();
        $fila = 2;
        $inicio = NUM_INICIO;
        if (!is_null($sheet->getCell('A1')) && !is_null($sheet->getCell('A2'))) {
            $codigo = $sheet->getCell('A' . $fila);
            //hacer mapa
            if ($codigos->hasKey($codigo)) {
                $sheet->removeRow($codigos->get($codigo));
                $fila--;
            } else {
                /* @var \Ds\Map() $codigos */
                $codigos->put($codigo,$fila);
            }
            $inicio = min($codigos->keys());
        }
        $GLOBALS['numero'] = $fila;

         //Colocación de los nombres de las columnas independientes
        $GLOBALS['sheet']->setCellValue('AG' . 0, 'Fecha última modificación');
        $sheet->setCellValue('A1', 'id');
        $sheet->setCellValue('A2', 'último cambio');
        $url = "https://www.contratosdegalicia.gal/rss/ultimas-publicacions.rss";
        $file = $url;
        $leer_texto = false;
        $is_item = false;
        $writter = new Ods($spreadsheet);
        $letra = 'B';
        $numero = 1;
        $indice = '';

        $fallos= 0;
        while($fallos < 500){
            leerDatosContrato($inicio);
            $inicio++;
        }

    /*     function characterData($parser, $data){
                if ($GLOBALS['leer_texto'] == true) {
                $url = $data;
                echo "<p>" . $url . "</p>";
                $id = get_code($url);
                $client = new Client();
                $GLOBALS['sheet']->setCellValue('A' . $GLOBALS['numero'], $id);
                $crawler = $client->request('GET', $url);

                //Calculo del historico
                $fecha = '';
                if (!empty($historico = $crawler->filterXPath("//table[@id='tabHistorico']//tr[1]//td[1]"))){
                    echo "<hr/>";
                    var_dump(count($historico));
                    echo "<hr/>";
                    if(count($historico)>0){
                        $fecha = $historico->text();
                    }
                }
                $GLOBALS['sheet']->setCellValue('AG' . $GLOBALS['numero'], $fecha);


                if (in_array($id, $GLOBALS['codigos'])) {
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
                }
            }
        }
*/
 /*       $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, "startElement", "endElement");
        xml_set_character_data_handler($xml_parser, "characterData");
        if (!($fp = fopen($file, "r"))) {
            die("could not open XML input");
        }

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($xml_parser, $data, feof($fp))) {
                die(sprintf(
                    "XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($xml_parser)),
                    xml_get_current_line_number($xml_parser)
                ));
            }
        }
        xml_parser_free($xml_parser);
*/
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
