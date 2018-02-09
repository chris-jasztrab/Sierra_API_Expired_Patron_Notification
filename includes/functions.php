<?php

// nothing in this file needs to be changed

  require_once('config.php');

function getPatron($patronID) {
    $uri = 'https://';
    $uri .= appServer;
    $uri .= ':443/iii/sierra-api/v';
    $uri .= apiVer;
    $uri .= '/patrons/';
    $uri .= $patronID;
    $uri .= '?fields=names,emails,barcodes,expirationDate';

    $apiToken = getCurrentApiAccessToken();

    // Build the header
    $headers = array(
        "Authorization: Bearer " . $apiToken
    );

    // make the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = json_decode(curl_exec($ch),true);

    curl_close($ch);
    return $result;
}


function stripped($fatId) {
    $lastSlash = strrpos($fatId, '/');
    $strippedId = substr($fatId, $lastSlash + 1, strlen($fatId));
    return $strippedId;
}

function setApiAccessToken()
{

    $uri = 'https://' . appServer . ':443/iii/sierra-api/v' . apiVer . '/token/';
    $authCredentials = base64_encode(apiKey . ":" . apiSecret);

    // Build the header
    $headers = array(
        "Authorization: Basic " . $authCredentials,
        "Content-Type: application/x-www-form-urlencoded"
    );

    // make the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

    $result = json_decode(curl_exec($ch));

    curl_close($ch);

    // save the access token and creation time to a session variable
    $_SESSION['apiAccessToken'] = $result->access_token;
    $_SESSION['apiAccessTokenCreationDate'] = time();
}

function getCurrentApiAccessToken() {

    $now = time();
    $elapsedTime = $now - $_SESSION['apiAccessTokenCreationDate'];

    if ($elapsedTime >= 360) {
        // if the current token is older than 6 minutes, get a new one
        $this->setApiAccessToken();
    }

    return $_SESSION['apiAccessToken'];
}




?>
