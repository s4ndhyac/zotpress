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

if ($zp_xml === false)
{
  // Access WordPress db
  global $wpdb;
  
  // Get account
  $zp_account = zp_get_account ($wpdb, $zp_api_user_id);
  $zp_url = "https://api.zotero.org/".$zp_account[0]->account_type."/".$zp_api_user_id."/items/".$zp_item_key;
  $zp_version_url = $zp_url;

  $verch = curl_init();
  //headers
  $verhttpHeaders = array();
  //set api version - allowed to be overridden by passed in value
  if(!isset($verheaders['Zotero-API-Version'])){
      $verheaders['Zotero-API-Version'] = ZOTERO_API_VERSION;
  }

  if(!isset($verheaders['Zotero-API-Key'])){
    $verheaders['Zotero-API-Key'] = $zp_account[0]->public_key;
    }

  if(!isset($verheaders['Content-Type'])){
    $verheaders['Content-Type'] = 'application/json';
}
  
  foreach($verheaders as $key=>$val){
      $verhttpHeaders[] = "$key: $val";
  }

  // Set query data here with the URL
  curl_setopt($verch, CURLOPT_FRESH_CONNECT, true);
  curl_setopt($verch, CURLOPT_URL, $zp_version_url); 
  curl_setopt($verch, CURLOPT_HEADER, true);
  curl_setopt($verch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($verch, CURLINFO_HEADER_OUT, true);
  curl_setopt($verch, CURLOPT_HTTPHEADER, $verhttpHeaders);
  curl_setopt($verch, CURLOPT_MAXREDIRS, 3);
  curl_setopt($verch, CURLOPT_TIMEOUT, 3);
  
  $verresponseBody = curl_exec($verch);
  $verrespheaders = Zotero\HttpResponse::extractHeaders($verresponseBody);
  if(!$verresponseBody){
      echo $verresponseBody->getStatus() . "\n";
      echo $verresponseBody->getBody() . "\n";
      die("Error creating attachment item\n\n");
  }
  echo '<pre>'; print_r($verrespheaders); echo '</pre>';
  $zp_version = $verrespheaders['last-modified-version'];
  $ch = curl_init();
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
  $responseBody = curl_exec($ch);

  if(!$responseBody){
      echo $responseBody->getStatus() . "\n";
      echo $responseBody->getBody() . "\n";
      die("Error creating attachment item\n\n");
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