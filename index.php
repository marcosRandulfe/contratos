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
    $codigos = [];
    $id = '';

    function get_code($url)
    {
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
    require __DIR__ . '/vendor/autoload.php';

    use Goutte\Client;
    use Symfony\Component\HttpClient\HttpClient;
    // $crawler = $client->request('GET', 'https://www.symfony.com/blog/');

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Ods;

    $inputFileName = './contratosgalicia.ods';
    /** Load $inputFileName to a Spreadsheet Object  **/

    //Se comprueba si la hoja de estilos esta vacia y sino se gurdan los códigos
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
    $sheet = $spreadsheet->getActiveSheet();
    $fila = 2;
    if (!is_null($sheet->getCell('A1')) && !is_null($sheet->getCell('A2'))) {
        $codigo = $sheet->getCell('A' . $fila);
        if (in_array($codigo, $codigos)) {
            $sheet->removeRow($fila);
            $fila--;
        } else {
            $codigos[] = $codigo;
        }
    }
    $GLOBALS['numero'] = $fila;


    $sheet->setCellValue('A1', 'id');
    $sheet->setCellValue('A2', 'último cambio');
    $url = "https://www.contratosdegalicia.gal/rss/ultimas-publicacions.rss";
    $file = $url;
    $leer_texto = false;
    $is_item = false;
    $writter = new Ods($spreadsheet);

    function startElement($parser, $name, $attrs)
    {
        if ($name == "ITEM") {
            $GLOBALS['is_item'] = true;
            $GLOBALS['numero']++;
            $GLOBALS['letra'] = 'B';
        }
        if ($name === 'LINK' && $GLOBALS['is_item']) {
            $GLOBALS['leer_texto'] = true;
        }
    }

    function endElement($parser, $name)
    {
        if ($name === 'LINK') {
            $GLOBALS['leer_texto'] = false;
        }
    }
    $letra = 'B';
    $numero = 1;
    $indice = '';


    function characterData($parser, $data)
    {
        if ($GLOBALS['leer_texto'] == true) {
            $url = $data;
            echo "<p>" . $url . "</p>";
            $id = get_code($url);

            $client = new Client();

            $GLOBALS['sheet']->setCellValue('A' . $GLOBALS['numero'], $id);
            $crawler = $client->request('GET', $url);
            //Calculo del historico
            /* $fecha='';
                   
                    $historico=$crawler->filter("#tabHistorico");
                    if($historico->isE){
                        $fecha = $historico->filter("td")->text();
                        echo "<p>".$fecha."</p>";
                    }
                    */
            if (in_array($id, $GLOBALS['codigos'])) {
                $GLOBALS['sheet']->setCellValue('A' . $GLOBALS['numero'], $id);
            }
            $crawler->filter("dt")->each(function ($node) {
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

    $xml_parser = xml_parser_create();
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
    $writter->save('odsPrueba.ods');

    //Subir Fichero



    require_once "base.php";

    /*************************************************
     * Ensure you've downloaded your oauth credentials
     ************************************************/
    if (!$oauth_credentials = getOAuthCredentialsFile()) {
        echo missingOAuth2CredentialsWarning();
        return;
    }

    /************************************************
     * The redirect URI is to the current page, e.g:
     * http://localhost:8080/simple-file-upload.php
     ************************************************/
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    $client = new Google_Client();
    $client->setAuthConfig($oauth_credentials);
    $client->setRedirectUri($redirect_uri);
    $client->addScope("https://www.googleapis.com/auth/drive");
    $service = new Google_Service_Drive($client);

    // add "?logout" to the URL to remove a token from the session
    if (isset($_REQUEST['logout'])) {
        unset($_SESSION['upload_token']);
    }

    /************************************************
     * If we have a code back from the OAuth 2.0 flow,
     * we need to exchange that with the
     * Google_Client::fetchAccessTokenWithAuthCode()
     * function. We store the resultant access token
     * bundle in the session, and redirect to ourself.
     ************************************************/
    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        // store in the session also
        $_SESSION['upload_token'] = $token;

        // redirect back to the example
        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
    }

    // set the access token as part of the client
    if (!empty($_SESSION['upload_token'])) {
        $client->setAccessToken($_SESSION['upload_token']);
        if ($client->isAccessTokenExpired()) {
            unset($_SESSION['upload_token']);
        }
    } else {
        $authUrl = $client->createAuthUrl();
    }

    /************************************************
     * If we're signed in then lets try to upload our
     * file. For larger files, see fileupload.php.
     ************************************************/
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $client->getAccessToken()) {
        // We'll setup an empty 1MB file to upload.
        DEFINE("FILE", 'contratosgalicia.txt');
        if (!file_exists(FILE)) {
            $fh = fopen(FILE, 'w');
            fwrite($fh, "!", 1);
            fclose($fh);
        }

        // This is uploading a file directly, with no metadata associated.
        $file = new Google_Service_Drive_DriveFile();
        $result = $service->files->create(
            $file,
            array(
                'data' => file_get_contents(FILE),
                'mimeType' => 'application/octet-stream',
                'uploadType' => 'media'
            )
        );
    }
    ?>

</body>

</html>