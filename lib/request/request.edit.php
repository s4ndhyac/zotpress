<?php

require('../../../../../wp-load.php');
define('WP_USE_THEMES', false);

// Include Request Functionality
require('request.class.php');
require('request.functions.php');


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
  $itemBody = $_POST["item"];
  $library = new Library($zp_account[0]->account_type, $zp_api_user_id, '', $zp_account[0]->public_key);
  
  //add child attachment
  //get attachment template
  echo "updating item\n";
  try{
  
  $ch = curl_init();
  $httpHeaders = array();
  //set api version - allowed to be overridden by passed in value
  if(!isset($headers['Zotero-API-Version'])){
      $headers['Zotero-API-Version'] = ZOTERO_API_VERSION;
  }

  if(!isset($headers['Content-Type'])){
    $headers['Content-Type'] = 'application/json';
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
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
  curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($itemBody));

  if(TRUE){
      $responseBody = curl_exec($ch);
  }

  if(!$responseBody){
      echo $createAttachmentResponse->getStatus() . "\n";
      echo $createAttachmentResponse->getBody() . "\n";
      die("Error creating attachment item\n\n");
  }
  else {   
    echo '<pre>'; print_r($responseBody); echo '</pre>';
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