<?php

/*************************************************
 * Ensure you've downloaded your oauth credentials
 ************************************************/
function comprobarCredenciales(){
    if (!$oauth_credentials = getOAuthCredentialsFile()) {
        echo missingOAuth2CredentialsWarning();
        return;
    }
}

/************************************************
 * The redirect URI is to the current page, e.g:
 * http://localhost:8080/simple-file-upload.php
 ************************************************/
function get_redirect_uri(){
    return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
}
?>