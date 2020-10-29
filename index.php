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
    require './vendor/autoload.php';
    use Goutte\Client;
    use Symfony\Component\HttpClient\HttpClient;
    use PhpOffice\PhpSpreadsheet\Reader\Ods;
    use PhpOffice\PhpSpreadsheet\Writer\Ods as Writter;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use Cache\Adapter\Apcu\ApcuCachePool;

   // $pool = new ApcuCachePool();
   // $simpleCache = new \Cache\Bridge\SimpleCache\SimpleCacheBridge($pool);
   // \PhpOffice\PhpSpreadsheet\Settings::setCache($simpleCache);

    ini_set('max_execution_time', 600); //300 seconds = 5 minutes
    set_time_limit(600);

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    //Constantes
    define('NOMBRE_FICHERO','./contratosgalicia.ods');
    define('URL_BASE','https://www.contratosdegalicia.gal//licitacion?N=');
    define('NUM_INICIO', 50000);
    //Subir Fichero
    //$client = new Google_Client();
    // Get your credentials from the console
    //$client->setClientId('1032605733423-mg308q50k5ttk2bcnj5omorv2u6aj95t.apps.googleusercontent.com');
    //$client->setClientSecret('7h7-1DW0g85balewwYRI8x8b');
    //$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
    //$client->setScopes(array('https://www.googleapis.com/auth/drive.file'));
   /// $codigos = new \Ds\Map();
    
    $codigos = new SQLite3('./codigos.db');

   // if (isset($_GET['code']) || (isset($_SESSION['access_token']) && $_SESSION['access_token'])) {
        //$codigos = new \Ds\Map();
        $id = '';
        $letra = 'B';
        $numero = 2;
        $indice = '';

        function leerDatosContrato($numero){
                $GLOBALS['letra'] = 'B';
                $url = URL_BASE.$numero;
                echo "<p>jjj" . $url . "</p>";
                $client = new Client();
                $peticionCorrecta = true;
                try{
                    $crawler = $client->request('GET', $url);
                    if($crawler==null){
                        $peticionCorrecta=false;
                    }
                }catch(Exception $ex){
                    $peticionCorrecta=false;
                }
                if (!$peticionCorrecta) {
                    return $peticionCorrecta;
                }
                $GLOBALS['sheet']->setCellValue('A' . $GLOBALS['numero'], $numero);

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

                $dt =$crawler->filter("dt");
                if(count($dt)>0){
                    $dt->each(function ($node) {
                        $propiedad = $node->text();
                        $valor= "";
                        try{
                            $valor = $node->siblings()->text();
                        }catch(Exception $ex){

                        }
                        echo "<p>" . $node->text() . "</p>";
                        echo "<p>" . var_dump($valor) . "</p>";
                        echo "<p>Fila y columna: " . $GLOBALS['letra'] . $GLOBALS['numero'] . "</p>";
                        $GLOBALS['sheet']->setCellValue($GLOBALS['letra'] . $GLOBALS['numero'], $valor);
                        $GLOBALS['sheet']->setCellValue($GLOBALS['letra'] . '1', $propiedad);
                        $GLOBALS['letra'] = ++$GLOBALS['letra'];
                    });
                }
                $GLOBALS['numero']++;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        //Se comprueba si la hoja de estilos esta vacia y sino se gurdan los códigos
        $spreadsheet;
        $inputFileName = './contratosgalicia.ods';
        if(file_exists($inputFileName)){
            echo "<p>Existe fichero</p>";
            $reader = new Ods();
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
            $sql="SELECT codigo, fila FROM codigo WHERE codigo='".$codigo."';";
            $resultado = $GLOBALS['codigos']->query($sql);
            $row = $resultado->fetchArray(SQLITE3_ASSOC);
            if ($row) {
                $cod_fila = $row['fila'];
                $sheet->removeRow($cod_fila);
                $fila--;
            } else {
                /* @var \Ds\Map() $codigos */
                $sql = 'INSERT INTO codigo('.$codigo.','.$fila.');';
                $GLOBALS['codigos']->query($sql);
            }
            /*
             * echo "<p> Claves:".min($codigos->keys()->toArray())."</p>";
             * $inicio = min($codigos->keys()->toArray());
            */
            $sql = 'SELECT min(codigo) FROM codigo;';
            $resultado=$GLOBALS['codigos']->query($sql);
            $row = $resultado->fetchArray(SQLITE3_ASSOC);
        
            if($row){
                $fila=$row['codigo'];
            }else{
                $fila=0;
            }
        }
        $GLOBALS['numero'] = $fila;

         //Colocación de los nombres de las columnas independientes
        $GLOBALS['sheet']->setCellValue('AG' . 0, 'Fecha última modificación');
        $sheet->setCellValue('A1', 'id');
        $sheet->setCellValue('B1', 'último cambio');
        $url = "https://www.contratosdegalicia.gal/rss/ultimas-publicacions.rss";
        $file = $url;
        $leer_texto = false;
        $is_item = false;
        
        $letra = 'B';
        $numero = 1;
        $indice = '';

        $fallos= 0;
        $num_inicio=500000;
        while($fallos < 5000){
            echo "<p>En el while</p>";
            $resultado=leerDatosContrato($num_inicio);
            //$spreadsheet->garbageCollect();
            if (!$resultado) {
                $fallos++;
            }
            $num_inicio++;
        }
        $writter = new Writter($spreadsheet);
        $writter->save(NOMBRE_FICHERO);
/*
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
*/
/*    } else {
        $authUrl = $client->createAuthUrl();
        header('Location: ' . $authUrl);
        exit();
    }
*/