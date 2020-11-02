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
        use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
        use Box\Spout\Common\Entity\Row;
        
        use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

        $inputFileName = './contratosgalicia.ods';
        $writer = WriterEntityFactory::createODSWriter();
        $writer->openToFile($inputFileName);
        $GLOBALS['reader']=false;
     /* 
      * $reader = ReaderEntityFactory::createODSReader();
      *  $sheet=$reader->getSheetIterator()->current();
      */ 
        
        // Variable para contar el número de filas
        $num_fila_actual=1;
        
        ini_set('max_execution_time', 600); //300 seconds = 5 minutes
        set_time_limit(600);

        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        //Constantes
        define('NOMBRE_FICHERO', './contratosgalicia.ods');
        define('URL_BASE', 'https://www.contratosdegalicia.gal//licitacion?N=');
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
        //$id = '';
        //$indice = '';  
        
        /**
         * 
         * @param type $codigo
         * @return Numero de fila o false
         */
        function comprobarCodigo($codigo){
            $sql ="SELECT fila FROM codigo WHERE codigo='".$codigo."';";
            $resultado=$GLOBALS['codigos']->query($sql);
            if($row = $resultado->fetchArray()){
                return $row['fila'];
            }
            return false;
        }

        function insertarFila($datos) {
            $exist_row=false;
            $sheet=$GLOBALS['writer']->getCurrentSheet();
            echo "<p> Datos: </p>";
            var_dump($datos);
            $fila = comprobarCodigo($datos[0]);
            if($fila==false){
                $GLOBALS['writer']->addRow(WriterEntityFactory::createRowFromArray($datos));
            }else{
                $reader =$GLOBALS['reader'];
                $sheet=$reader->getSheetIterator()->current();
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    if($rowIndex==$fila){
                        $exist_row=true;
                        $cells = WriterEntityFactory::createRowFromArray($datos)->getCells();
                        $row->setCells($cells);
                    }
                }
            }
        }

        function leerDatosContrato($numero) {
            global $datos_funcion;
            $datos_funcion=[];
            $url = URL_BASE . $numero;
            echo "<p>jjj" . $url . "</p>";
            $client = new Client();
            $peticionCorrecta = true;
            try {
                $crawler = $client->request('GET', $url);
                if ($crawler == null) {
                    $peticionCorrecta = false;
                }
            } catch (Exception $ex) {
                $peticionCorrecta = false;
            }
            if (!$peticionCorrecta) {
                return $peticionCorrecta;
            }
            $datos_funcion[]=$numero;

            //Calculo del historico
            $fecha = '';
            if (!empty($historico = $crawler->filterXPath("//table[@id='tabHistorico']//tr[1]//td[1]"))) {
                echo "<hr/>";
                var_dump(count($historico));
                echo "<hr/>";
                if (count($historico) > 0) {
                    $fecha = $historico->text();
                }
            }
            $datos_funcion[]=$fecha;
            $dt = $crawler->filter("dt");
            if (count($dt) > 0) {
                $dt->each(
                    function ($node) {
                        $propiedad = $node->text();
                        $valor = "";
                        try {
                            $valor = $node->siblings()->text();
                        } catch (Exception $ex) {

                        }
                        echo "<p>" . $node->text() . "</p>";
                        echo "<p>" . $valor . "</p>";
                        $datos_funcion[]=$valor;
                        array_push($GLOBALS['datos_funcion'],$valor);
                        echo "<h1>Datos cosas</h1>";
                        var_dump($GLOBALS['datos_funcion']);
                });
            }
            insertarFila($datos_funcion);
        }
        
        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        //Se comprueba si la hoja de estilos esta vacia y sino se gurdan los códigos
        $spreadsheet;
       
        if (file_exists($inputFileName)) {
            echo "<p>Existe fichero</p>";
            $reader = ReaderEntityFactory::createReaderFromFile($inputFileName);
            $GLOBALS['reader']=$reader;
            //var_dump($reader);
            $reader->open($inputFileName);
            var_dump($reader);
            $numFila = 1;
            $inicio = NUM_INICIO;
            $sheet=$reader->getSheetIterator()->current();
            $GLOBALS['sheet_lectura']=$sheet;
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    if ($rowIndex> 1) {
                        $cells = $row->getCells();
                        if(empty($cells)){
                            break;
                        }
                        $codigo = $cells[0]->getValue();
                        $filaHoja = $cells[1]->getValue();
                        $sql = "SELECT codigo, fila FROM codigo WHERE codigo='" . $codigo . "';";
                        $resultado = $GLOBALS['codigos']->query($sql);
                        $row = $resultado->fetchArray(SQLITE3_ASSOC);
                        if (!$row) {
                            /* @var \Ds\Map() $codigos */
                            $sql = 'INSERT INTO codigo VALUES (' . $codigo . ',' . $fila . ');';
                            $GLOBALS['codigos']->query($sql);
                        }
                    }
                    $numFila=$rowIndex;
                }
            $GLOBALS['num_fila_actual']=$numFila;
        }

       if($numFila<=1){
        $cells = ['id',
                'Historico(ultima modificacion)',
                'obxeto',
                'Tipo de procedemento',
                'Nº de lotes',
                'Orzamento base de licitación',
                'Tipo de contrato',
                'Sistemas de contratación',
                'Compra pública estratéxica',
                'Data de difusión en Contratos Públicos de Galicia',
                'Selo',
                'data publicación perfil',
                'data publicación bop',
                'data publicación dog',
                'data publicación boe',
                'data publicación doue',
                'código cpv',
                'Lote cpv',
                'data difusión',
                'NUT',
                'Lote NUT',
                'Data difusión nut',
                'Lugar de presentación',
                'Data e hora(presentación)',
                'Hora do rexistro presentación',
                'Documentación de inicio',
                'Documentación pregos',
                'Documentación outros'
            ];
        $row = WriterEntityFactory::createRowFromArray($cells);
        $writer->addRow($row);
       }
        
        $url = "https://www.contratosdegalicia.gal/rss/ultimas-publicacions.rss";
        $leer_texto = false;

        $fallos = 0;
        $num_contrato = 700000;
        while ($fallos < 5000 && $num_contrato<700800) {
            echo "<p>En el while</p>";
            $resultado = leerDatosContrato($num_contrato);
            if (!$resultado) {
                $fallos++;
            } else {
        
            }
            $num_contrato++;
        }
        $writer->close();
        //$writter = new Writter($spreadsheet);
        //$writter->save(NOMBRE_FICHERO);
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
        