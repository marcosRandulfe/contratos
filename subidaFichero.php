<?php
echo __DIR__;
require __DIR__.'/vendor/autoload.php';

//use Google\Auth\Client;


$client = new Google_Client();
// Get your credentials from the console
$client->setClientId('1032605733423-mg308q50k5ttk2bcnj5omorv2u6aj95t.apps.googleusercontent.com');
$client->setClientSecret('7h7-1DW0g85balewwYRI8x8b');
$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
$client->setScopes(array('https://www.googleapis.com/auth/drive.file'));

session_start();
if (isset($_GET['code']) || (isset($_SESSION['access_token']) && $_SESSION['access_token'])) {
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

       $data = file_get_contents('./contratosgalicia.ods');

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

?>
