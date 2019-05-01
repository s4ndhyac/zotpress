<?php

require('../../../../../wp-load.php');
define('WP_USE_THEMES', false);

// Include Request Functionality
require('request.class.php');
require('request.functions.php');
require('libzotero.php');


// Content prep
$zp_xml = false;

// Key
if (isset($_GET['key']) && preg_match("/^[a-zA-Z0-9]+$/", $_GET['key']))
  $zp_item_key = trim(urldecode($_GET['key']));
else
  $zp_xml = "No key provided.";

// Api User ID
if (isset($_GET['api_user_id']) && preg_match("/^[a-zA-Z0-9]+$/", $_GET['api_user_id']))
  $zp_api_user_id = trim(urldecode($_GET['api_user_id']));
else
  $zp_xml = "No API User ID provided.";

  // Item version
session_start();
if (isset($_GET['version']) && preg_match("/^[a-zA-Z0-9]+$/", $_GET['version']))
{
  $zp_version = trim(urldecode($_GET['version']));  
  if($_SESSION[$zp_item_key] != $zp_version)
    $zp_version = $_SESSION[$zp_item_key];
}
else
  $zp_xml = "No version provided.";

if ($zp_xml === false)
{
  // Access WordPress db
  global $wpdb;
  
  // Get account
  $zp_account = zp_get_account ($wpdb, $zp_api_user_id);
  $zp_url = "https://api.zotero.org/".$zp_account[0]->account_type."/".$zp_api_user_id."/items/".$zp_item_key;
  $itemBody = $_POST;
  $library = new Zotero\Library($zp_account[0]->account_type, $zp_api_user_id, '', $zp_account[0]->public_key);
  echo '<pre>'; print_r($itemBody); echo '</pre>';
  //add child attachment
  //get attachment template
  echo "updating item\n";
  try{

  unset($itemBody['submit']);
  $requestData = json_encode($itemBody);
  $ch = curl_init();
  $httpHeaders = array();
  //set api version - allowed to be overridden by passed in value
  if(!isset($headers['Zotero-API-Version'])){
      $headers['Zotero-API-Version'] = ZOTERO_API_VERSION;
  }

  if(!isset($headers['Zotero-API-Key'])){
    $headers['Zotero-API-Key'] = $zp_account[0]->public_key;
    }

  if(!isset($headers['Content-Type'])){
    $headers['Content-Type'] = 'application/json';
}

if(!isset($headers['If-Unmodified-Since-Version'])){
  $headers['If-Unmodified-Since-Version'] = $zp_version;
}

if(!isset($headers['Expect'])){
  $headers['Expect'] = '';
}
  
  foreach($headers as $key=>$val){
      $httpHeaders[] = "$key: $val";
  }

  curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);


  curl_setopt($ch, CURLOPT_URL, $zp_url );
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLINFO_HEADER_OUT, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
  curl_setopt($ch, CURLOPT_POSTFIELDS,$requestData);

  if(TRUE){
      $responseBody = curl_exec($ch);
      $response = Zotero\HttpResponse::fromString($responseBody);
      $respheaders = Zotero\HttpResponse::extractHeaders($responseBody);
  }

  if(!$responseBody){
      echo $responseBody->getStatus() . "\n";
      echo $responseBody->getBody() . "\n";
      die("Error creating attachment item\n\n");
  }
  else {   
    echo '<pre>'; print_r($response); echo '</pre>';
    echo '<pre>'; print_r($responseBody); echo '</pre>';
    echo '<pre>'; print_r($respheaders['last-modified-version']); echo '</pre>';
    $_SESSION[$zp_item_key] = $respheaders['last-modified-version'];

  }
  }
  catch(Exception $e){
      echo $e->getMessage();
      $lastResponse = $library->getLastResponse();
      echo $lastResponse->getStatus() . "\n";
      echo $lastResponse->getRawBody() . "\n";
  }
}
else {
  echo $zp_xml;
}	

?>