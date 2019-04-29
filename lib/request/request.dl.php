<?php

	// Include WordPress
	require('../../../../../wp-load.php');
	define('WP_USE_THEMES', false);

	// Include Request Functionality
	require('request.class.php');
	require('request.functions.php');

	// Content prep
	$zp_xml = false;

	// Key
	if (isset($_GET['dlkey']) && preg_match("/^[a-zA-Z0-9]{3,40}$/", $_GET['dlkey']))
		$zp_item_key = trim(urldecode($_GET['dlkey']));
	else
		$zp_xml = "No key provided, or format incorrect.";

	// Api User ID
	if (isset($_GET['api_user_id']) && preg_match("/^[a-zA-Z0-9]{3,15}$/", $_GET['api_user_id']))
		$zp_api_user_id = trim(urldecode($_GET['api_user_id']));
	else
		$zp_xml = "No API User ID provided, or format incorrect.";

	// Content type
	if (isset($_GET['content_type']) && preg_match("/^[a-zA-Z0-9\/]{3,40}$/", $_GET['content_type']))
		$zp_content_type = trim(urldecode($_GET['content_type']));
	else
		$zp_xml = "No content type provided, or format incorrect.";


	if ($zp_xml === false)
	{
		// Access WordPress db
		global $wpdb;

		// Get account
		$zp_account = zp_get_account ($wpdb, $zp_api_user_id);

		// Build import structure
		$zp_import_contents = new ZotpressRequest();

		$zp_import_url = "https://api.zotero.org/".$zp_account[0]->account_type."/".$zp_api_user_id."/items/";
		$zp_import_url .= $zp_item_key."/file/view?key=".$zp_account[0]->public_key;

		// Read the external data
        $zp_xml = $zp_import_contents->get_request_contents( $zp_import_url, true ); // Unsure about "true"

		// Determine filename based on content type
		$zp_filename ="download-".$zp_item_key.".";
		if ( strpos( $zp_content_type, "pdf" ) ) $zp_filename .= "pdf";
		else if ( strpos( $zp_content_type, "wordprocessingml.document" ) ) $zp_filename .= "docx";
		else if ( strpos( $zp_content_type, "msword" ) ) $zp_filename .= "doc";
		else if ( strpos( $zp_content_type, "latex" ) ) $zp_filename .= "latex";
		else if ( strpos( $zp_content_type, "presentationml.presentation" ) ) $zp_filename .= "pptx";
		else if ( strpos( $zp_content_type, "ms-powerpointtd" ) ) $zp_filename .= "ppt";
		else if ( strpos( $zp_content_type, "rtf" ) ) $zp_filename .= "rtf";
		else if ( strpos( $zp_content_type, "opendocument.text" ) ) $zp_filename .= "odt";
		else if ( strpos( $zp_content_type, "opendocument.presentation" ) ) $zp_filename .= "odp";

		if ( $zp_xml !== false && strlen(trim($zp_xml["json"])) > 0 )
		{
			header( "Content-Type:".$zp_content_type);
			header( "Content-Disposition:attachment;filename=".$zp_filename);
			echo $zp_xml["json"];
		}
		else {
			$zp_xml = "No cite file found.";
		}
	}
	else {
		echo $zp_xml;
	}
?>
