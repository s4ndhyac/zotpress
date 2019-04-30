<?php
	// Include WordPress
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
    $zp_url = "https://api.zotero.org/".$zp_account[0]->account_type."/".$zp_api_user_id."/items/";
    $zp_filename = $_FILES["fileToUpload"]["name"];
    $zp_contentType = $_FILES['fileToUpload']['type'];
    $library = new Zotero\Library($zp_account[0]->account_type, $zp_api_user_id, '', $zp_account[0]->public_key);
    
    //add child attachment
    //get attachment template
    echo "adding attachment item\n";
    try{
    
    $templateItem = array();
    $attachmentBody = (object)array(
      'itemType' => 'attachment',
      'parentItem' => $zp_item_key,
      'linkMode' => 'imported_file',
      'tags' => array(),
      'title' => $zp_filename,
      'contentType' => $zp_contentType
    );


    $templateItem[] = $attachmentBody;

    $requestData = json_encode($templateItem);
    
    $url = 'https://api.zotero.org/users/'.$zp_api_user_id.'/items?key='.$zp_account[0]->public_key;
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
  

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);

    if(TRUE){
        $responseBody = curl_exec($ch);
        $responseInfo = curl_getinfo($ch);
        $zresponse = Zotero\HttpResponse::fromString($responseBody);
        
    }

    $createAttachmentResponse = $zresponse;
    if($createAttachmentResponse->isError()){
        echo $createAttachmentResponse->getStatus() . "\n";
        echo $createAttachmentResponse->getBody() . "\n";
        die("Error creating attachment item\n\n");
    }
    else {

        $attachmentItemTemplate = json_decode($createAttachmentResponse->getRawBody(), true);
        $uploadKey = $attachmentItemTemplate['success'][0];

        //upload file for attachment
        $fileContents = file_get_contents($_FILES["fileToUpload"]["tmp_name"]);
        
        $fileinfo = array('md5'=>md5($fileContents), 'filename'=>$zp_filename, 'filesize'=>filesize($_FILES["fileToUpload"]["tmp_name"]), 'mtime'=>filemtime($_FILES["fileToUpload"]["tmp_name"]));
        echo "<br /><br />\n\nFile Info:";
        var_dump($fileinfo);

        $postData = "md5={$fileinfo['md5']}&filename={$fileinfo['filename']}&filesize={$fileinfo['filesize']}&mtime={$fileinfo['mtime']}";
        $uploadheaders = array('If-None-Match'=>'*');


        $uploadurl = 'https://api.zotero.org/users/'.$zp_api_user_id.'/items/'.$uploadKey."/file";
        $uploadch = curl_init();
        $uploadhttpHeaders = array();
        //set api version - allowed to be overridden by passed in value
        if(!isset($uploadheaders['Zotero-API-Version'])){
            $uploadheaders['Zotero-API-Version'] = ZOTERO_API_VERSION;
        }

        if(!isset($uploadheaders['Content-Type'])){
          $uploadheaders['Content-Type'] = 'application/x-www-form-urlencoded';
        }

      if(!isset($uploadheaders['Zotero-API-Key'])){
        $uploadheaders['Zotero-API-Key'] = $zp_account[0]->public_key;
        }
        
        foreach($uploadheaders as $key=>$val){
            $uploadhttpHeaders[] = "$key: $val";
        }

        curl_setopt($uploadch, CURLOPT_FRESH_CONNECT, true);

        curl_setopt($uploadch, CURLOPT_URL, $uploadurl);
        curl_setopt($uploadch, CURLOPT_HEADER, true);
        curl_setopt($uploadch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($uploadch, CURLINFO_HEADER_OUT, true);
        curl_setopt($uploadch, CURLOPT_HTTPHEADER, $uploadhttpHeaders);
        curl_setopt($uploadch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($uploadch, CURLOPT_POST, true);
        curl_setopt($uploadch, CURLOPT_POSTFIELDS, $postData);

        if(TRUE){
            $upresponseBody = curl_exec($uploadch);
            $upresponseInfo = curl_getinfo($uploadch);
            $upzresponse = Zotero\HttpResponse::fromString($upresponseBody);
            
        }

        echo '<pre>'; print_r($upzresponse); echo '</pre>';
        
        if($upzresponse->getStatus() == 200){
          Zotero\libZoteroDebug("200 response from upload authorization ");
            $respbody = $upzresponse->getRawBody();
            $resObject = json_decode($respbody, true);
            if(!empty($resObject['exists'])){
              Zotero\libZoteroDebug("File already exists ");
                return true;//api already has a copy, short-circuit with positive result
            }
            else{
              Zotero\libZoteroDebug("uploading filecontents padded as specified ");
                //upload file padded with information we just got
                $uploadPostData = $resObject['prefix'] . $fileContents . $resObject['suffix'];
                Zotero\libZoteroDebug($uploadPostData);
                $uploadHeaders = array('Content-Type'=>$resObject['contentType']);


                $uploadFilech = curl_init();
                $uploadFilehttpHeaders = array();
                //set api version - allowed to be overridden by passed in value
                if(!isset($uploadHeaders['Zotero-API-Version'])){
                    $uploadHeaders['Zotero-API-Version'] = ZOTERO_API_VERSION;
                }

              if(!isset($uploadHeaders['Zotero-API-Key'])){
                $uploadHeaders['Zotero-API-Key'] = $zp_account[0]->public_key;
                }
                
                $uploadFilehttpHeaders[] = 'Expect:';

                foreach($uploadHeaders as $key=>$val){
                    $uploadFilehttpHeaders[] = "$key: $val";
                }

                echo '<pre>'; print_r($resObject['url']); echo '</pre>';
                echo '<pre>'; print_r($uploadFilehttpHeaders); echo '</pre>';
                echo '<pre>'; print_r($uploadPostData); echo '</pre>';

                curl_setopt($uploadFilech, CURLOPT_FRESH_CONNECT, true);

                curl_setopt($uploadFilech, CURLOPT_URL, $resObject['url']);
                curl_setopt($uploadFilech, CURLOPT_HEADER, true);
                curl_setopt($uploadFilech, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($uploadFilech, CURLINFO_HEADER_OUT, true);
                curl_setopt($uploadFilech, CURLOPT_HTTPHEADER, $uploadFilehttpHeaders);
                curl_setopt($uploadFilech, CURLOPT_MAXREDIRS, 3);
                curl_setopt($uploadFilech, CURLOPT_POST, true);
                curl_setopt($uploadFilech, CURLOPT_POSTFIELDS, $uploadPostData);

                if(TRUE){
                    $upFileresponseBody = curl_exec($uploadFilech);
                    $upFileresponseInfo = curl_getinfo($uploadFilech);
                    $upFilezresponse = Zotero\HttpResponse::fromString($upFileresponseBody);
                    
                }
                
                echo '<pre>'; print_r($upFilezresponse); echo '</pre>';
                echo '<pre>'; print_r($upFilezresponse->getStatus()); echo '</pre>';

                if($upFilezresponse->getStatus() == 201){
                  Zotero\libZoteroDebug("got upload response 201 ");
                    $regurl = 'https://api.zotero.org/users/'.$zp_api_user_id.'/items/'.$uploadKey."/file";
                    $registerUploadData = "upload=" . $resObject['uploadKey'];
                    $regHeaders = array('If-None-Match'=>'*');
                    $regFilech = curl_init();
                    $regFilehttpHeaders = array();
                    //set api version - allowed to be overridden by passed in value
                    if(!isset($regHeaders['Zotero-API-Version'])){
                        $regHeaders['Zotero-API-Version'] = ZOTERO_API_VERSION;
                    }

                    if(!isset($uploadheaders['Content-Type'])){
                      $uploadheaders['Content-Type'] = 'application/x-www-form-urlencoded';
                    }
    
                  if(!isset($regHeaders['Zotero-API-Key'])){
                    $regHeaders['Zotero-API-Key'] = $zp_account[0]->public_key;
                    }
                    
                    foreach($regHeaders as $key=>$val){
                        $regFilehttpHeaders[] = "$key: $val";
                    }
    
                    echo '<pre>'; print_r($regurl); echo '</pre>';
                    echo '<pre>'; print_r($regFilehttpHeaders); echo '</pre>';
                    echo '<pre>'; print_r($registerUploadData); echo '</pre>';
    
                    curl_setopt($regFilech, CURLOPT_FRESH_CONNECT, true);
    
                    curl_setopt($regFilech, CURLOPT_URL, $regurl);
                    curl_setopt($regFilech, CURLOPT_HEADER, true);
                    curl_setopt($regFilech, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($regFilech, CURLINFO_HEADER_OUT, true);
                    curl_setopt($regFilech, CURLOPT_HTTPHEADER, $regFilehttpHeaders);
                    curl_setopt($regFilech, CURLOPT_MAXREDIRS, 3);
                    curl_setopt($regFilech, CURLOPT_POST, true);
                    curl_setopt($regFilech, CURLOPT_POSTFIELDS, $registerUploadData);
    
                    if(TRUE){
                        $regFileresponseBody = curl_exec($regFilech);
                        $regFileresponseInfo = curl_getinfo($regFilech);
                        $regFilezresponse = Zotero\HttpResponse::fromString($regFileresponseBody);
                        
                    }
                    
                    echo '<pre>'; print_r($regFilezresponse); echo '</pre>';

                    if($regFilezresponse->getStatus() == 204){
                      Zotero\libZoteroDebug("successfully registered upload ");
                        return true;
                    }
                    else{
                      Zotero\libZoteroDebug("error in registering upload ");
                        return false;
                    }
                }
                else{
                  Zotero\libZoteroDebug("error in  uploading file ");
                    return false;
                }
            }
        }
        else{
          Zotero\libZoteroDebug("non-200 response from upload authorization ");
            return false;
        }

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