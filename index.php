<?php
        require __DIR__.'/vendor/autoload.php';

        use Goutte\Client;
        use Symfony\Component\HttpClient\HttpClient;
        use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
        use Box\Spout\Common\Entity\Row;
        use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

        // Variable para contar el número de filas
        $num_fila_actual=2;
        function renameExistingFile($filename){
                $oldname=$filename;
                $increment = 0;
                $name=pathinfo($filename, PATHINFO_FILENAME);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                while(file_exists($filename)) {
                    $increment++;
                    $filename = $name. $increment . '.' . $ext;
                }
                rename($oldname,$filename);
        }

        function delete_olders($inicio){
            // 1 mes -> 2595600
            $ficheros= scandir('.');
            for ($i=0;$i<count($ficheros);$i++){
                if (preg_match('/('.$inicio.')\d+.*/', $ficheros[$i])) {
                     $tiempo=time()-filectime($ficheros[$i]);
                     if($tiempo> 2595600){
                         unlink($ficheros[$i]);
                    }
                }
            }
        }




        /**
         *
         * @param type $codigo
         * @return Numero de fila o false
         */
        function comprobarCodigo($codigo){
            $sql ="SELECT fila FROM codigos WHERE codigo='".$codigo."';";
            $res=$GLOBALS['codigos']->query($sql);
            if($row= $res->fetchArray()){
                return $row['fila'];
            }
            return false;
        }

        function insertarCodigo($codigo,$fila){
            $sql= "INSERT INTO codigos VALUES(".$codigo.",".$fila.");";
            $GLOBALS['codigos']->query($sql);
        }

    // -----------------------------------------------------------------------------------
    // ShareWithUser
    // -----------------------------------------------------------------------------------
    function addShared($service, $fileId, $userEmail, $role ){
        // role can be reader, writer, etc
        $userPermission = new Google_Service_Drive_Permission(array(
            'type' => 'user',
            'role' => $role,
            'emailAddress' => $userEmail
        ));

        $service->permissions->create(
            $fileId, $userPermission, array('fields' => 'id')
        );
    }


        $inputFileName = __DIR__.'/contratosgalicia.ods';
        if(file_exists($inputFileName)){
            renameExistingFile($inputFileName);
        }
        delete_olders(pathinfo($inputFileName,PATHINFO_FILENAME));
        $writer = WriterEntityFactory::createODSWriter();
        $writer->openToFile($inputFileName);
        $GLOBALS['reader']=false;

        ini_set('max_execution_time', 60000);
        set_time_limit(6000);

        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        //Constantes
        define('NOMBRE_FICHERO', './contratosgalicia.ods');
        define('URL_BASE', 'https://www.contratosdegalicia.gal//licitacion?N=');
        define('NUM_INICIO', 70000);
        //Subir Fichero
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.__DIR__.'/contratosgalicia.json');

        $googleClient = new \Google\Client();
        $googleClient->useApplicationDefaultCredentials();
        $googleClient->addScope(Google_Service_Drive::DRIVE);

        $codigos = new SQLite3(__DIR__.'/codigos.db');
        //resetea los codigos de la base de datos
        $GLOBALS['codigos']->query("DELETE FROM codigos  WHERE codigo != -1;");
        //$codigos =  mysqli_connect("localhost", "marcos", "abc123", "codigos") or die;
        //$codigos = new Flintstone('codigos', ['dir' => __DIR__, 'formatter' => new JsonFormatter()]);
        //$maximo = new Flintstone('maximo',['dir' => __DIR__, 'formatter' => new JsonFormatter()]);

        function putMaximo($max){
            $sql ="INSERT INTO codigos VALUES(-1,".$max.")";
            $GLOBALS['codigos']->query($sql);
            $sql = sprintf("UPDATE codigos SET fila=%d WHERE codigo=-1;",$max);
            $GLOBALS['codigos']->query($sql);
        }

        function getMaximo(){
            $sql ="SELECT fila FROM codigos WHERE codigo='-1'";
            $res=$GLOBALS['codigos']->query($sql);
            if($row = $res->fetchArray()){
                return $row["fila"];
            }
            return false;
        }


        function insertarFila($datos) {
            $sheet=$GLOBALS['writer']->getCurrentSheet();
            $fila = comprobarCodigo($datos[0]);
            if($fila==false){
                $GLOBALS['writer']->addRow(WriterEntityFactory::createRowFromArray($datos));
                $GLOBALS['num_fila_actual']++;
                insertarCodigo($datos[0],$GLOBALS['num_fila_actual']);
            }else{
                $reader = $GLOBALS['reader'];
                $sheet=$reader->getSheetIterator()->current();
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    if($rowIndex==$fila){
                        $cells = WriterEntityFactory::createRowFromArray($datos)->getCells();
                        $row->setCells($cells);
                    }
                }
            }
        }
        $client= new Client();
        function leerDatosContrato($numero) {
            global $datos_funcion;
            $datos_funcion=[];
            $url = URL_BASE . $numero;
            $client = $GLOBALS['client'];
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

            $fecha = '';
            $historico=$crawler->filterXPath("//table[@id='tabHistorico']//tr[1]//td[1]");
            if (!empty($historico)) {
                if (count($historico) > 0) {
                    $fecha = $historico->text();
                }
            }
            $datos_funcion[]=$fecha;
            $dl = $crawler->filter("dl");
            if (count($dl) > 0) {
                $dl->each(
                    function($node) {
                        $valor = "";
                        try {
                            $valor = $node->siblings()->text();
                        } catch (Exception $ex) {

                        }
                        //echo "<p>" . $node->text() . "</p>";
                        //echo "<p>" . $valor . "</p>";
                        $datos_funcion[]=$valor;
                        array_push($GLOBALS['datos_funcion'],$valor);
                        //echo "<h1>Datos cosas</h1>";
                        //var_dump($GLOBALS['datos_funcion']);
                });
            }
            //Faltan datos tablas
            insertarFila($datos_funcion);
            return true;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        //Se comprueba si la hoja de estilos esta vacia y sino se gurdan los códigos
        $spreadsheet;

        /*if (file_exists($inputFileName)) {
            echo "<p>Existe fichero</p>";
            $reader = ReaderEntityFactory::createReaderFromFile($inputFileName);
            $GLOBALS['reader']=$reader;
            $reader->open($inputFileName);
            var_dump($reader);
            $numFila = 1;
            $inicio = NUM_INICIO;
            $sheet=$reader->getSheetIterator()->current();
            $GLOBALS['sheet_lectura']=$sheet;
            $num_fila=0;
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    if ($rowIndex> 1) {
                        $cells = $row->getCells();
                        if(empty($cells)){
                            break;
                        }
                        $codigo = $cells[0];
                        insertarCodigo($codigo,$rowIndex);
                    }
                }
           $GLOBALS['num_fila_actual']=$rowIndex;
        }*/

        // Renombrar el fichero si ya existe

        $cells = ['id',
                'Historico(ultima modificacion)',
                'obxeto',
                'Tipo de tramitación',
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

        $url = "https://www.contratosdegalicia.gal/rss/ultimas-publicacions.rss";
        $feed = simplexml_load_file("https://www.contratosdegalicia.gal/rss/ultimas-publicacions.rss");
        $leer_texto = false;
        //Número en el que empieza el fichero
        //$nummaximo=getMaximo();
        //$fallos = 0;
        //$num_contrato = 770000;

        if($feed!=false){
            echo "<p>feed</p>";
            var_dump($feed);
            echo "<p>feed</p>";
            $enlace = $feed->channel->item[0]->link;
            $num_contratos = preg_split("/N=/",$enlace);
        }

        echo "Num contratos:";
        echo var_dump($num_contratos);
        $num_contrato = $num_contratos[1];
        $limite = $num_contratos[1]-40;

        while ($num_contrato>$limite) {
            $resultado = leerDatosContrato($num_contrato);
            $num_contrato--;
        }
        //putMaximo($num_contrato);
        $writer->close();


        $service = new Google_Service_Drive($googleClient);

            //Insert a file
            $file = new Google_Service_Drive_DriveFile();
            $file->setName('fichero.ods');
            $file->setDescription('Contratos de galicia');
            $file->setMimeType('application/vnd.oasis.opendocument.spreadsheet');
            $fileId=$file->getId();
            $data = file_get_contents($inputFileName);

            $createdFile = $service->files->create($file, array(
                'data' => $data,
                'mimeType' => 'application/vnd.oasis.opendocument.spreadsheet',
                'uploadType' => 'multipart'
            ));
            addShared($service,$createdFile->getId(), "marcosrandulfegarrido@gmail.com", "writer");
           // addShared($service,$createdFile->getId(), "jaime.barreiro.laredo@gmail.com", "writer");

exit();