<?php

/**
 * ZOTPRESS SHORTCODE AJAX
 *
 * Retrieves data from Zotero library based on shortcode.
 *
 * Used by:    zotpress.php
 *
 * @return     string          JSON array with: (a) meta about request , and (b) all data for this request
 */
function Zotpress_shortcode_AJAX()
{
	check_ajax_referer( 'zpShortcode_nonce_val', 'zpShortcode_nonce' );

	// Set up database
	global $wpdb;

	// Prep request vars
	$zpr = Zotpress_prep_request_vars();

	// Include relevant classes and functions
	include( dirname(__FILE__) . '/../request/request.class.php' );
  include( dirname(__FILE__) . '/../request/request.functions.php' );
  include( dirname(__FILE__) . '/../request/libzotero.php' );

	// Set up request queue (for items)
	$zp_request_queue = array(); // Structure: [api_user_id] => [items], [requests]

	// Set up Zotpress request
	$zp_import_contents = new ZotpressRequest();

	// Set up request meta
	$zp_request_meta = array( "request_last" => $zpr["request_last"], "request_next" => 0 );

	// Set up data variable
	$zp_all_the_data = array();



	/**
	*
	*  Format Zotero request URL:
	*
	*/

	// Account for items + collection_id
	if ( $zpr["item_type"] == "items" && $zpr["collection_id"] !== false )
	{
		$zpr["item_type"] = "collections";
		$zpr["sub"] = "items";
		$zpr["get_top"] = false;
	}

	// Account for items + zp_tag_id
	if ( $zpr["item_type"] == "items" && $zpr["tag_id"] !== false )
		$zpr["get_top"] = false;

	// Account for collection_id + get_top
	if ( $zpr["get_top"] !== false && $zpr["collection_id"] !== false )
	{
		$zpr["get_top"] = false;
		$zpr["sub"] = "collections";
	}

	// Account for tag display - let's limit it
	if ( $zpr["is_dropdown"] === true && $zpr["item_type"] == "tags" )
	{
		$zpr["sortby"] = "numItems"; // title
		$zpr["order"] = "desc"; // asc
		$zpr["limit"] = "100"; if ( $zpr["maxtags"] ) $zpr["limit"] = $zpr["maxtags"];
		$zpr["overwrite_request"] = true;
	}

	// Account for $zpr["maxresults"]
	if ( $zpr["maxresults"] )
	{
		// If 50 or less, set as limit
		if ( intval($zpr["maxresults"]) <= 50 )
		{
			$zpr["limit"] = $zpr["maxresults"];
			$zpr["overwrite_request"] = true;
		}

		// If over 50, then overwrite last_request
		else
		{
			$zpr["overwrite_last_request"] = $zpr["maxresults"];
		}
	}

	// Handle the possible formats of item/s for bib and in-text
	//
	// IN-TEXT FORMATS:
	// [zotpressInText item="NCXAA92F"]
	// [zotpressInText item="{NCXAA92F,10-15}"]
	// [zotpressInText items="{NCXAA92F,10-15},{55MKF89B,1578},{3ITTIXHP}"]
	// So no multiples without curlies or non-curlies in multiples
	//
	// BIB FORMATS:
	// [zotpress item="GMGCJU34"]
	// [zotpress items="GMGCJU34,U9Z5JTKC"]
	// [zotpress item="{000001:XH4BS8MA},{000001:CN73PTWE},{000003:CZR96TX9}"]

	if ( $zpr["item_key"] )
	{
		// Possible format: {api_user_id:item_key}, ...
		if ( strpos( $zpr["item_key"], ":" ) !== false )
		{
			$zp_item_groups = explode( "},{", $zpr["item_key"] );
			$zp_item_groups[0] = substr($zp_item_groups[0], 1);
			$zp_item_groups[count($zp_item_groups)-1] = substr($zp_item_groups[count($zp_item_groups)-1], 0, -1);

			foreach ( $zp_item_groups as $item_group_raw )
			{
				// Divide api_user_id from $zpr["item_key"]
				$item_group = explode( ":", $item_group_raw );

				// Store for entry order
				array_push($zpr["item_keys_order"], $item_group[1]);

				// Add to queue
				if ( array_key_exists("items", $zp_request_queue[$item_group[0]]) )
					$zp_request_queue[$item_group[0]]["items"] .= ",";
				$zp_request_queue[$item_group[0]]["items"] .= $item_group[1];
			}
		}

		// Deal with in-text citations
		else if ( strpos( $zpr["item_key"], "{" ) !== false )
		{
			// Possible format: item_key,{item_key,3-9};{item_key,8}
			// Also possible: {item_key}

			// First, try separating multiple citations
			$zp_item_groups = explode( ";", $zpr["item_key"] );

			$zpr["item_key"] = ""; // prep empty

			foreach ( $zp_item_groups as $item_group )
			{
				// Try to separate multiple sources in one citation
				$zpr["item_key"] = explode( "},{", $item_group );

				foreach ( $zpr["item_key"] as $key )
				{
					// Strip of curlies
					$key = str_replace( "{", "", str_replace( "}", "" , $key ) );

					// Skip duplicates in this group
					// Change to string, just to be safe
					if ( substr_count( implode( ',', $zpr["item_key"] ), $key ) > 1 )
						continue;

					// Separate from page/s
					if ( strpos( $key, "," ) !== false )
					{
						$key = explode( ",", $key );
						$key = $key[0];
					}

					// Skip duplicates in the queue
					if ( array_key_exists($zpr["api_user_id"], $zp_request_queue)
							&& array_key_exists("items", $zp_request_queue[$zpr["api_user_id"]])
							&& strpos($zp_request_queue[$zpr["api_user_id"]]["items"], $key ) !== false )
						continue;

					// Add to queue
					// First, add account if needed
					if ( ! array_key_exists($zpr["api_user_id"], $zp_request_queue) )
						$zp_request_queue[$zpr["api_user_id"]] = array();

					// Then add items
					if ( array_key_exists("items", $zp_request_queue[$zpr["api_user_id"]]) )
						$zp_request_queue[$zpr["api_user_id"]]["items"] .= ",";
					$zp_request_queue[$zpr["api_user_id"]]["items"] .= $key;
				}
			}
		}

		// Deal with old style
		else if ( strpos( $zpr["item_key"], ";" ) !== false )
		{
			// Add to queue
			if ( array_key_exists("items", $zp_request_queue[$zpr["api_user_id"]]) )
				$zp_request_queue[$zpr["api_user_id"]] .= ",";
			$zp_request_queue[$zpr["api_user_id"]]["items"] .= str_replace(";", ",", $zpr["item_key"]);
		}
		else {
			$zp_request_queue[$zpr["api_user_id"]]["items"] .= $zpr["item_key"];
		}
	}


	// BUILD REQUEST URL FOR EVERY REQUEST
	if ( count($zp_request_queue) > 0 )
	{
		// REVIEW: Does setting $zp_request_queue here overwrite it for each account?
		foreach ( $zp_request_queue as $api_user_id => $zp_request_account )
			$zp_request_queue = Zotpress_prep_request_URl($wpdb, $zpr, $zp_request_queue, $api_user_id);
	}
	else {
		$zp_request_queue = Zotpress_prep_request_URl($wpdb, $zpr, $zp_request_queue);
	}





	/**
	*
	*	 TESTING:
	*
	*/

	// var_dump($zp_request_queue); exit;


    //if ( $zpr["request_start"] == 50 ) {
    //    var_dump("shortcode.ajax.php TESTING: ");
    //    print_r($_GET); var_dump("<br /><br />url: ".$zp_import_url);
    //    var_dump(" AFTER \n\n");
    //}





	/**
	*
	*	 Request the data:
	*
	*/

	$zp_request = array();

	foreach ( $zp_request_queue as $zp_request_account )
	{
		if ( count($zp_request_account["requests"]) > 1 )
		{
			foreach ( $zp_request_account["requests"] as $zp_request_url )
			{
				$zp_imported = $zp_import_contents->get_request_contents( $zp_request_url, $zpr["update"], $zpr["showtags"] );

				// Create all-requests json if doesn't exists
				if ( empty($zp_request) ) $zp_request = $zp_imported;

				// Add to existing all-requests json
				$zp_request["json"] = rtrim($zp_request["json"], "]") . "," . $zp_imported["json"] . "]";
			}
		}
		else
		{
			$zp_imported = $zp_import_contents->get_request_contents( $zp_request_account["requests"][0], $zpr["update"], $zpr["showtags"] );

			// Create all-requests json if doesn't exists
			if ( empty($zp_request) )
				$zp_request = $zp_imported;

			// Add to existing all-requests json
			else
				$zp_request["json"] = rtrim($zp_request["json"], "]") . "," . ltrim($zp_imported["json"], "[") . "]";

		}
	} // Request the data

	// Fix formatting quirk
	$zp_request["json"] = str_replace("}}]]", "}}]", $zp_request["json"]);


	// OLD WAY:
	// $zp_request = $zp_import_contents->get_request_contents( $zp_import_url, $zpr["update"], $zpr["showtags"] );


	if ( $zp_request["json"] != "0" )
	{
		$temp_headers = json_decode( $zp_request["headers"] );
		$temp_data = json_decode( $zp_request["json"] );

		// Figure out if there's multiple requests and how many
		if ( $zpr["request_start"] == 0
				&& isset($temp_headers->link) && strpos( $temp_headers->link, 'rel="last"' ) !== false )
		{
			$temp_link = explode( ";", $temp_headers->link );
			$temp_link = explode( "start=", $temp_link[1] );
			$temp_link = explode( "&", $temp_link[1] );

			$zp_request_meta["request_last"] = $temp_link[0];
		}

		// Figure out the next starting position for the next request, if any
		if ( $zp_request_meta["request_last"] >= ($zpr["request_start"] + $zpr["limit"]) )
			$zp_request_meta["request_next"] = $zpr["request_start"] + $zpr["limit"] ;

		// Overwrite request if tag limit
		if ( $zpr["overwrite_request"] === true )
		{
			$zp_request_meta["request_next"] = 0;
			$zp_request_meta["request_last"] = 0;
		}

		// Overwrite last_request
		if ( $zpr["overwrite_last_request"] )
		{
			// Make sure it's less than the total available items
			if ( isset( $temp_headers->{"total-results"} )
					&& $temp_headers->{"total-results"} < $zpr["overwrite_last_request"] )
				$zpr["overwrite_last_request"] = intval( ceil( intval($temp_headers->{"total-results"}) / $zpr["limit"] ) - 1 ) * $zpr["limit"];
			else
				$zpr["overwrite_last_request"] = intval( ceil( $zpr["overwrite_last_request"] / $zpr["limit"] ) ) * $zpr["limit"];

			$zp_request_meta["request_last"] = $zpr["overwrite_last_request"];
		}



		/**
		*
		*	 Format the data:
		*
		*/

		if ( count($temp_data) > 0 )
		{
			// If single, place the object into an array
			if ( gettype($temp_data) == "object" )
			{
				$temp = $temp_data;
				$temp_data = array();
				$temp_data[0] = $temp;
			}

			// Set up conditional vars
			if ( $zpr["shownotes"] ) $zp_notes_num = 1;
			if ( $zpr["showimage"] ) $zp_showimage_keys = "";

      $i = 0;
			// Get individual items
			foreach ( $temp_data as $item )
			{
				// Set target for links
				$zp_target_output = ""; if ( $zpr["target"] ) $zp_target_output = "target='_blank' ";

				// Author filtering: skip non-matching authors
				// EVENTUAL TODO: Zotero API 3 searches title and author, so wrong authors can appear
				if ( $zpr["author"] && count($item->data->creators) > 0 )
				{
					$zp_authors_check = false;

					// Deal with multiple authors
					if ( gettype($zpr["author"]) != "array"
							&& strpos($zpr["author"], ",") !== false )
					{
						$zp_authors = explode( ",", $zpr["author"] );

						foreach ( $zp_authors as $author )
							if ( zp_check_author_continue( $item, $author ) === true )
								$zp_authors_check = true;
					}
					else // single or inclusive
					{
						if ( $zpr["inclusive"] === false )
						{
							$author_exists_count = 1;

							foreach ( $zpr["author"] as $author )
								if ( zp_check_author_continue( $item, $author ) === true )
									$author_exists_count++;

							if ( $author_exists_count == count($zpr["author"])+1 )
								$zp_authors_check = true;
						}
						else // inclusive and single
						{
							if ( zp_check_author_continue( $item, $zpr["author"] ) === true )
								$zp_authors_check = true;
						}
					}

					if ( $zp_authors_check === false ) continue;
				}

				// Year filtering: skip non-matching years
				if ( $zpr["year"] && isset($item->meta->parsedDate) )
				{
					if ( strpos($zpr["year"], ",") !== false ) // multiple
					{
						$zp_years_check = false;
						$zp_years = explode( ",", $zpr["year"] );

						foreach ( $zp_years as $year )
							if ( zp_get_year( $item->meta->parsedDate ) == $year ) $zp_years_check = true;

						if ( ! $zp_years_check ) continue;
					}
					else // single
					{
						if ( zp_get_year( $item->meta->parsedDate ) != $zpr["year"] ) continue;
					}
				}

				// Skip non-matching years for author-year pairs
				if ( $zpr["year"] && $zpr["author"] && isset($item->meta->parsedDate) )
					if ( zp_get_year( $item->meta->parsedDate ) != $zpr["year"] ) continue;

				// Add item key for show image
				if ( $zpr["showimage"] ) $zp_showimage_keys .= " ".$item->key;

				// Modify style based on language
				// Languages: jp
				if ( isset( $item->data->language ) && $item->data->language != "" )
				{
					if ( $item->data->language == "ja" )
					{
						// Change ", and " to comma
						$item->bib = str_ireplace(", and ", ", ", $item->bib);

						// Remove "In "
						$item->bib = str_ireplace("In ", "", $item->bib);
					}
				}

				// Hyperlink or URL Wrap
				if ( isset( $item->data->url ) && strlen($item->data->url) > 0 )
				{
					if ( $zpr["urlwrap"] && $zpr["urlwrap"] == "title" && $item->data->title )
					{
						// First: Get rid of text URL if it appears as text in the citation:
						// REVIEW: Does this account for all citation styles?
						/* chicago-author-date */ $item->bib = str_ireplace( htmlentities($item->data->url."."), "", $item->bib ); // Note the period
						/* APA */ $item->bib = str_ireplace( htmlentities($item->data->url), "", $item->bib );
						$item->bib = str_ireplace( " Retrieved from ", "", $item->bib );
						$item->bib = str_ireplace( " Available from: ", "", $item->bib );


						// Next, get rid of double space characters (two space characters next to each other):
						$item->bib = preg_replace( '/&#xA0;/', ' ', preg_replace( '/[[:blank:]]+/', ' ', $item->bib ) );
						$item->data->title = preg_replace( '/&#xA0;/', ' ', preg_replace( '/[[:blank:]]+/', ' ', $item->data->title ) );


						// Next, replace space entities with real spaces:
						$item->bib = str_ireplace("&nbsp;", " ", $item->bib );
						$item->data->title = str_ireplace("&nbsp;", " ", $item->data->title );


						// Next, replace entity quotes:
						$item->bib = str_ireplace( "&ldquo;", "&quot;",
										str_ireplace( "&rdquo;", "&quot;",
												htmlentities(
													html_entity_decode( $item->bib, ENT_QUOTES, "UTF-8" ),
													ENT_QUOTES,
													"UTF-8"
												)
											)
									);
						$item->data->title = str_ireplace( "&ldquo;", "&quot;",
										str_ireplace( "&rdquo;", "&quot;",
												htmlentities(
													html_entity_decode( $item->data->title, ENT_QUOTES, "UTF-8" ),
													ENT_QUOTES,
													"UTF-8"
												)
											)
									);


						// Next, replace special Word characters:
						// Thanks to Walter Tross @ Stack Overflow; CC BY-SA 3.0: https://creativecommons.org/licenses/by-sa/3.0/
						$chr_map = array(
							"\xC2\x82" => "'",			// U+0082U+201A single low-9 quotation mark
							"\xC2\x84" => '"',			// U+0084U+201E double low-9 quotation mark
							"\xC2\x8B" => "'",			// U+008BU+2039 single left-pointing angle quotation mark
							"\xC2\x91" => "'",			// U+0091U+2018 left single quotation mark
							"\xC2\x92" => "'",			// U+0092U+2019 right single quotation mark
							"\xC2\x93" => '"',			// U+0093U+201C left double quotation mark
							"\xC2\x94" => '"',			// U+0094U+201D right double quotation mark
							"\xC2\x9B" => "'",			// U+009BU+203A single right-pointing angle quotation mark
							"\xC2\xAB" => '"',			// U+00AB left-pointing double angle quotation mark
							"\xC2\xBB" => '"',			// U+00BB right-pointing double angle quotation mark
							"\xE2\x80\x98" => "'",	// U+2018 left single quotation mark
							"\xE2\x80\x99" => "'",	// U+2019 right single quotation mark
							"\xE2\x80\x9A" => "'",	// U+201A single low-9 quotation mark
							"\xE2\x80\x9B" => "'",	// U+201B single high-reversed-9 quotation mark
							"\xE2\x80\x9C" => '"',	// U+201C left double quotation mark
							"\xE2\x80\x9D" => '"',	// U+201D right double quotation mark
							"\xE2\x80\x9E" => '"',	// U+201E double low-9 quotation mark
							"\xE2\x80\x9F" => '"',	// U+201F double high-reversed-9 quotation mark
							"\xE2\x80\xB9" => "'",	// U+2039 single left-pointing angle quotation mark
							"\xE2\x80\xBA" => "'"	// U+203A single right-pointing angle quotation mark
						);
						$chr = array_keys( $chr_map );
						$rpl = array_values( $chr_map );
						$item->bib = str_ireplace( $chr, $rpl, html_entity_decode( $item->bib, ENT_QUOTES, "UTF-8" ) );
						$item->data->title = str_ireplace( $chr, $rpl, html_entity_decode( $item->data->title, ENT_QUOTES, "UTF-8" ) );

						// Re-encode for foreign characters, but don't encode quotes:
						$item->bib = htmlentities( $item->bib, ENT_NOQUOTES, "UTF-8" );
						$item->data->title = htmlentities( $item->data->title, ENT_NOQUOTES, "UTF-8" );


						// Next, prep title:
						// $item->data->title = htmlentities( $item->data->title, ENT_COMPAT, "UTF-8" );


						// If wrapping title, wrap it:
						$item->bib = str_ireplace(
								$item->data->title,
								"<a ".$zp_target_output."href='".$item->data->url."'>".$item->data->title."</a>",
								$item->bib
							);

						// Finally, revert bib entities:
						$item->bib = html_entity_decode( $item->bib, ENT_QUOTES, "UTF-8" );
						$item->data->title = html_entity_decode( $item->data->title, ENT_QUOTES, "UTF-8" );

					}
					else // Just hyperlink the URL text
					{
						$item->bib = str_ireplace(
								htmlentities($item->data->url),
								"<a ".$zp_target_output."href='".$item->data->url."'>".$item->data->url."</a>",
								$item->bib
							);
					}
				}

				// Hyperlink DOIs
				if ( isset( $item->data->DOI ) && strlen($item->data->DOI) > 0 )
				{
					// Styles without http
					if ( strpos( $item->bib, "doi:" ) !== false
                            && strpos( $item->bib, "doi.org" ) == false )
					{
						$item->bib = str_ireplace(
								"doi:" . $item->data->DOI,
								"<a ".$zp_target_output."href='http://doi.org/".$item->data->DOI."'>http://doi.org/".$item->data->DOI."</a>",
								$item->bib
							);
					}
					// Styles with http
					else if ( strpos( $item->bib, "http://doi.org/" ) !== false
                            && strpos( $item->bib, "</a>" ) == false )
					{
						$item->bib = str_ireplace(
								"http://doi.org/" . $item->data->DOI,
								"<a ".$zp_target_output."href='http://doi.org/".$item->data->DOI."'>http://doi.org/".$item->data->DOI."</a>",
								$item->bib
							);
					}
					// HTTPS format
					else if ( strpos( $item->bib, "https://doi.org/" ) !== false
                            && strpos( $item->bib, "</a>" ) == false)
					{
						$item->bib = str_ireplace(
								"https://doi.org/" . $item->data->DOI,
								"<a ".$zp_target_output."href='https://doi.org/".$item->data->DOI."'>https://doi.org/".$item->data->DOI."</a>",
								$item->bib
							);
					}
				}

				// Cite link (RIS)
				if ( $zpr["citeable"] )
					$item->bib = preg_replace( '~(.*)' . preg_quote('</div>', '~') . '(.*?)~', '$1' . " <a title='Cite in RIS Format' class='zp-CiteRIS' href='".ZOTPRESS_PLUGIN_URL."lib/request/request.cite.php?api_user_id=".$zpr["api_user_id"]."&amp;item_key=".$item->key."'>Cite</a> </div>" . '$2', $item->bib, 1 );

				// Highlight text
				if ( $zpr["highlight"] )
					$item->bib = str_ireplace( $zpr["highlight"], "<strong>".$zpr["highlight"]."</strong>", $item->bib );

				// Downloads, notes
				if ( $zpr["downloadable"] || $zpr["shownotes"] )
				{
					// Check if item has children that could be downloads
					if ( $item->meta->numChildren > 0 )
					{
						// Get the user's account
						$zp_account = zp_get_account ($wpdb, $zpr["api_user_id"]);

						$zp_child_url = "https://api.zotero.org/".$zp_account[0]->account_type."/".$zpr["api_user_id"]."/items";
						$zp_child_url .= "/".$item->key."/children?";
						if (is_null($zp_account[0]->public_key) === false && trim($zp_account[0]->public_key) != "")
							$zp_child_url .= "key=".$zp_account[0]->public_key."&";
						$zp_child_url .= "&format=json&include=data";

						// Get data
						$zp_import_child = new ZotpressRequest();
						$zp_child_request = $zp_import_child->get_request_contents( $zp_child_url, $zpr["update"] );
						$zp_children = json_decode( $zp_child_request["json"] );

						$zp_download_meta = false;
						$zp_notes_meta = array();

						foreach ( $zp_children as $zp_child )
						{
							// Check for downloads
							if ( $zpr["downloadable"] )
							{
								if ( isset($zp_child->data->linkMode)
                                    && ( $zp_child->data->linkMode == "imported_file"
                                        || $zp_child->data->linkMode == "imported_url")
                                    && preg_match('(pdf|doc|docx|ppt|pptx|latex|rtf|odt|odp)', $zp_child->data->filename) === 1
                                )
								{
									$zp_download_meta = array (
											"dlkey" => $zp_child->key,
											"contentType" => $zp_child->data->contentType
										);
								}
							}

							// Check for notes
							if ( $zpr["shownotes"] )
							{
								if ( isset($zp_child->data->itemType) && $zp_child->data->itemType == "note" )
									$zp_notes_meta[count($zp_notes_meta)] = $zp_child->data->note;
							}
						}

            // Display download link if file exists
            $downloadBib = "";
            if ( $zp_download_meta )
              $downloadBib = "<a title='Download' class='zp-DownloadURL' href='".ZOTPRESS_PLUGIN_URL."lib/request/request.dl.php?api_user_id=".$zpr["api_user_id"]."&amp;dlkey=".$zp_download_meta["dlkey"]."&amp;content_type=".$zp_download_meta["contentType"]."'>Download</a>";
            else // Display upload link if file does not exist
            {
              $upload_url = ZOTPRESS_PLUGIN_URL."lib/request/request.ul.php?api_user_id=".$zpr["api_user_id"]."&amp;key=".$item->key."&amp;content_type=application/pdf";
              $html_var = "
              <iframe name='uhiddenFrame".$i."' width='0' height='0' border='0' style='display: none;'></iframe> 
              <button id='umyBtn".$i."' style = 'font-size:8px;text-transform: uppercase;background-color: white;color:black'>Upload</button>
              <div id='umyModal".$i."' class='modal'>
                <div class='modal-content'>
                  <span id='uclose".$i."' class='close-button'>&times;</span>
                  <form id='uupload-form".$i."' action='".$upload_url."' method='post' enctype='multipart/form-data' target='uhiddenFrame".$i."'>
                  <input class='zp-UploadURL' type='file' name='fileToUpload' id='fileToUpload'> 
                  <input id='uupload-submit".$i."' class='zp-SubmitURL' type='submit' value='Upload' style = 'font-size:8px' name='upload'> 
                  </form>
                </div>
              </div>
              <script>
                // Get the modal
                var umodal".$i." = document.getElementById('umyModal".$i."');

                // Get the button that opens the modal
                var ubtn".$i." = document.getElementById('umyBtn".$i."');

                // Get the <span> element that closes the modal
                var uspan".$i." = document.getElementById('uclose".$i."');

                var uuploadForm".$i." = document.getElementById('uupload-form".$i."');
                var uuploadBtn".$i." = document.getElementById('uupload-submit".$i."');

                // When the user clicks the button, open the modal 
                ubtn".$i.".onclick = function() {
                  umodal".$i.".style.display = 'block';
                
                }

                // When the user clicks on <span> (x), close the modal
                uspan".$i.".onclick = function() {
                  umodal".$i.".style.display = 'none';
                }

                // When the user clicks anywhere outside of the modal, close it
                window.onclick = function(event) {
                  if (event.target == umodal".$i.") {
                    umodal".$i.".style.display = 'none';
                  }
                }

                uuploadForm".$i.".onclick = function(event) {
                  if (event.target == uuploadBtn".$i.") {
                    umodal".$i.".style.display = 'none';
                  }
                }
              </script>";
              $downloadBib = $html_var;
            }
            
            
            $itemBody = $item->data;
            $creators = $itemBody->creators;
            $dynamicAuthors = "";
            $j = 0;
            foreach($creators as $creator)
            {
              if($creator->creatorType=="author")
              {
                $dynamicAuthors .= "
                <input type='hidden' name='creators[".$j."][creatorType]' value='author' type='text' />
                Author first name: <input name='creators[".$j."][firstName]' value='".$creator->firstName."' type='text' />
                Author last name: <input name='creators[".$j."][lastName]' value='".$creator->lastName."' type='text' />
                <br>";
                $j++;
              }
            }
            $itemType = $itemBody->itemType;
            $dynamicForm = "Title: <input name='title' value='".$itemBody->title."' type='text' />".$dynamicAuthors;
            if($itemType == "book")
            {
                $dynamicForm .= "
                Place: <input name='place' value='".$itemBody->place."' type='text' />
                Publisher: <input name='publisher' value='".$itemBody->publisher."' type='text' />
                Date: <input name='date' value='".$itemBody->date."' type='text' />
                No. of Pages: <input name='numPages' value='".$itemBody->numPages."' type='text' />
                URL: <input name='url' value='".$itemBody->url."' type='text' />
                ";
            }
            elseif($itemType=="conferencePaper")
            {
              $dynamicForm .= "
                Abstract: <input name='abstractNote' value='".$itemBody->abstractNote."' type='text' />
                Proceedings Title: <input name='proceedingsTitle' value='".$itemBody->proceedingsTitle."' type='text' />
                Place: <input name='place' value='".$itemBody->place."' type='text' />
                Publisher: <input name='publisher' value='".$itemBody->publisher."' type='text' />
                Pages: <input name='pages' value='".$itemBody->pages."' type='text' />
                Date: <input name='date' value='".$itemBody->date."' type='text' />
                URL: <input name='url' value='".$itemBody->url."' type='text' />
                ";
            }
            elseif($itemType == "journalArticle")
            {
              $dynamicForm .= "
                Abstract: <input name='abstractNote' value='".$itemBody->abstractNote."' type='text' />
                Volume: <input name='volume' value='".$itemBody->volume."' type='text' />
                Issue: <input name='issue' value='".$itemBody->issue."' type='text' />
                Pages: <input name='pages' value='".$itemBody->pages."' type='text' />
                Publication Title: <input name='publicationTitle' value='".$itemBody->publicationTitle."' type='text' />
                Date: <input name='date' value='".$itemBody->date."' type='text' />
                DOI: <input name='DOI' value='".$itemBody->DOI."' type='text' />
                URL: <input name='url' value='".$itemBody->url."' type='text' />
                ";
            }
            elseif($itemType == "manuscript")
            {
              $dynamicForm .= "
                Place: <input name='place' value='".$itemBody->place."' type='text' />
                Date: <input name='date' value='".$itemBody->date."' type='text' />
                No. of pages: <input name='numPages' value='".$itemBody->numPages."' type='text' />
                URL: <input name='url' value='".$itemBody->url."' type='text' />
                ";

            }
            elseif($itemType == "report")
            {
              $dynamicForm .= "
                Report Number: <input name='reportNumber' value='".$itemBody->reportNumber."' type='text' />  
                Place: <input name='place' value='".$itemBody->place."' type='text' />
                Institution: <input name='institution' value='".$itemBody->institution."' type='text' />
                Date: <input name='date' value='".$itemBody->date."' type='text' />
                Pages: <input name='pages' value='".$itemBody->pages."' type='text' />
                URL: <input name='url' value='".$itemBody->url."' type='text' />
                ";
            }

            $edit_url = ZOTPRESS_PLUGIN_URL."lib/request/request.edit.php?api_user_id=".$zpr["api_user_id"]."&amp;key=".$itemBody->key;
            $edit_html_var = "
        <iframe name='hiddenFrame".$i."' width='0' height='0' border='0' scrolling='yes' style='display: none;overflow: scroll;'></iframe> 
        <button id='myBtn".$i."' style = 'font-size:8px;text-transform: uppercase;background-color: white;color:black'>Edit</button>
        <div id='myModal".$i."' class='modal'>
          <div class='modal-content'>
            <span id='eclose".$i."' class='close-button'>&times;</span>
            <form id='upload-form".$i."' action='".$edit_url."' method='post' target='hiddenFrame".$i."'>
            ".$dynamicForm."
            <input id='upload-submit".$i."' class='zp-SubmitURL' type='submit' value='submit' style = 'font-size:8px' name='submit'> 
            </form>
          </div>
        </div>
        <script>
          // Get the modal
          var modal".$i." = document.getElementById('myModal".$i."');

          // Get the button that opens the modal
          var btn".$i." = document.getElementById('myBtn".$i."');

          // Get the <span> element that closes the modal
          var span".$i." = document.getElementById('eclose".$i."');

          var uploadForm".$i." = document.getElementById('upload-form".$i."');
          var uploadBtn".$i." = document.getElementById('upload-submit".$i."');

          // When the user clicks the button, open the modal 
          btn".$i.".onclick = function() {
            modal".$i.".style.display = 'block';
            modal".$i.".style.overflow = 'scroll';
          }

          // When the user clicks on <span> (x), close the modal
          span".$i.".onclick = function() {
            modal".$i.".style.display = 'none';
          }

          // When the user clicks anywhere outside of the modal, close it
          window.onclick = function(event) {
            if (event.target == modal".$i.") {
              modal".$i.".style.display = 'none';
            }
          }
          
          uploadForm".$i.".onclick = function(event) {
            if (event.target == uploadBtn".$i.") {
              modal".$i.".style.display = 'none';
            }
          }

        </script></div>";

            $item->bib = preg_replace('~(.*)' . preg_quote( '</div>', '~') . '(.*?)~', '$1' .$downloadBib. $edit_html_var. '$2', $item->bib, 1 );

						// Display notes, if any
						if ( count($zp_notes_meta) > 0 )
						{
							$temp_notes = "<li id=\"zp-Note-".$item->key."\">\n";

							if ( count($zp_notes_meta) == 1 )
							{
								$temp_notes .= $zp_notes_meta[0]."\n";
							}
							else // multiple
							{
								$temp_notes .= "<ul class='zp-Citation-Item-Notes'>\n";

								foreach ($zp_notes_meta as $zp_note_meta)
									$temp_notes .= "<li class='zp-Citation-note'>" . $zp_note_meta . "\n</li>\n";

								$temp_notes .= "\n</ul><!-- .zp-Citation-Item-Notes -->\n\n";
							}

							// Add to item
							$item->notes = $temp_notes . "</li>\n";

							// Add note reference to citation
							$item->bib = preg_replace('~(.*)' . preg_quote('</div>', '~') . '(.*?)~', '$1' . " <sup class=\"zp-Notes-Reference\"><a href=\"#zp-Note-".$item->key."\">".$zp_notes_num."</a></sup> </div>" . '$2', $item->bib, 1);
							$zp_notes_num++;
						}
					}
				} // $zpr["downloadable"]

        $i++;
				array_push( $zp_all_the_data,  $item);
			} // foreach item



			// Show images
			if ( $zpr["showimage"] )
			{
				// Get images for all item keys from zpdb, if they exist
				$zp_images = $wpdb->get_results(
					"
					SELECT * FROM ".$wpdb->prefix."zotpress_zoteroItemImages
					WHERE ".$wpdb->prefix."zotpress_zoteroItemImages.item_key IN ('".str_replace( " ", "', '", trim($zp_showimage_keys) )."')
					"
				);

				if ( count($zp_images) > 0 )
				{
					foreach ( $zp_images as $image )
					{
						$zp_thumbnail = wp_get_attachment_image_src($image->image);

						foreach ( $zp_all_the_data as $id => $data )
						{
							if ( $data->key == $image->item_key)
							{
								$zp_all_the_data[$id]->image = $zp_thumbnail;

								// URL Wrap for images
								if ( $zpr["urlwrap"] && $zpr["urlwrap"] == "image" && $zp_all_the_data[$id]->data->url != "" )
								{
									// Get rid of default URL listing
									// TO-DO: Does this account for all citation styles?
									$zp_all_the_data[$id]->bib = str_replace( htmlentities($zp_all_the_data[$id]->data->url), "", $zp_all_the_data[$id]->bib );
									$zp_all_the_data[$id]->bib = str_replace( " Retrieved from ", "", $zp_all_the_data[$id]->bib );
									$zp_all_the_data[$id]->bib = str_replace( " Available from: ", "", $zp_all_the_data[$id]->bib );
								}
							}
						}
					}
				} // If images found in zpdb

				// Check open lib next
				if ( $zpr["showimage"] === "openlib" )
				{
					$zp_showimage_keys = explode( ",", $zp_showimage_keys );

					foreach ( $zp_all_the_data as $id => $data )
					{
						if ( ! in_array( $data->key,  $zp_showimage_keys )
								&& ( isset($data->data->ISBN) && $data->data->ISBN != "" ) )
						{
							$openlib_url = "http://covers.openlibrary.org/b/isbn/".$data->data->ISBN."-M.jpg";
							$openlib_headers = @get_headers( $openlib_url );

							if ( $openlib_headers[0] != "HTTP/1.1 404 Not Found" )
							{
								$zp_all_the_data[$id]->image = array( $openlib_url );

								// URL Wrap for images
								if ( $zpr["urlwrap"] && $zpr["urlwrap"] == "image" && $zp_all_the_data[$id]->data->url != "" )
								{
									// Get rid of default URL listing
									// TO-DO: Does this account for all citation styles?
									$zp_all_the_data[$id]->bib = str_replace( htmlentities($zp_all_the_data[$id]->data->url), "", $zp_all_the_data[$id]->bib );
									$zp_all_the_data[$id]->bib = str_replace( " Retrieved from ", "", $zp_all_the_data[$id]->bib );
									$zp_all_the_data[$id]->bib = str_replace( " Available from: ", "", $zp_all_the_data[$id]->bib );
								}
							}
						}
					}
				}
			}

			// Re-sort with order of entry if bib and default sort
			if ( $zpr["item_type"] == "items"
					&& $zpr["sortby"] == "default"
				 	&& count($zpr["item_keys_order"]) > 0 )
			{
				$temp_arr = array();

				foreach ( $zpr["item_keys_order"] as $temp_key )
				{
					foreach ( $zp_all_the_data as $temp_data )
					{
						if ( $temp_data->key == $temp_key ) array_push( $temp_arr, $temp_data );
					}
				}

				$zp_all_the_data = $temp_arr;
			}
		}
	}

	else // No results
	{
		$zp_all_the_data = ""; // Necessary?
	}

	/**
	*
	*	 Output the data:
	*
	*/

	if ( count($zp_all_the_data) > 0 )
	{
		echo json_encode(
				array (
					"instance" => $zpr["instance_id"],
					"meta" => $zp_request_meta,
					"data" => $zp_all_the_data
				)
			);
	}
	else // No data
	{
		echo "0";
	}

	unset($zp_import_contents);
	unset($zp_import_url);
	unset($zp_xml);
	unset($api_user_id);
	unset($zp_account);

	$wpdb->flush();

	exit();
}
add_action( 'wp_ajax_zpRetrieveViaShortcode', 'Zotpress_shortcode_AJAX' );
add_action( 'wp_ajax_nopriv_zpRetrieveViaShortcode', 'Zotpress_shortcode_AJAX' );



function Zotpress_prep_request_vars()
{
	$zpr = array();

	$zpr["limit"] = 50; // max 100, 22 seconds
	$zpr["overwrite_request"] = false;
	$zpr["overwrite_last_request"] = false;

	// Deal with incoming variables
	$zpr["type"] = "basic"; if ( isset($_GET['type']) && $_GET['type'] != "" ) $zpr["type"] = $_GET['type'];
	if ( isset( $_GET['api_user_id'] ) ) $zpr["api_user_id"] = $_GET['api_user_id']; else $zpr["api_user_id"] = false;
	$zpr["item_type"] = "items"; if ( isset($_GET['item_type']) && $_GET['item_type'] != "" ) $zpr["item_type"] = $_GET['item_type'];
	$zpr["get_top"] = false; if ( isset($_GET['get_top']) ) $zpr["get_top"] = true;
	$zpr["sub"] = false;
	$zpr["is_dropdown"] = false; if ( isset($_GET['is_dropdown']) ) $zpr["is_dropdown"] = true;
	$zpr["update"] = false; if ( isset($_GET['update']) && $_GET['update'] == "true" ) $zpr["update"] = true;

	// instance id, item key, collection id, tag id
	$zpr["instance_id"] = false; if ( isset($_GET['instance_id']) ) $zpr["instance_id"] = $_GET['instance_id'];

	$zpr["item_key"] = false;
	if ( isset($_GET['item_key'])
			&& ( $_GET['item_key'] != "false" && $_GET['item_key'] !== false ) )
		$zpr["item_key"] = $_GET['item_key'];

	$zpr["collection_id"] = false;
	if ( isset($_GET['collection_id'])
			&& ( $_GET['collection_id'] != "false" && $_GET['collection_id'] !== false ) )
		$zpr["collection_id"] = $_GET['collection_id'];

	$zpr["tag_id"] = false;
	if ( isset($_GET['tag_id'])
			&& ( $_GET['tag_id'] != "false" && $_GET['tag_id'] !== false ) )
		$zpr["tag_id"] = $_GET['tag_id'];

	// Author, year, style, limit, title
	$zpr["author"] = false; if ( isset($_GET['author']) && $_GET['author'] != "false" ) $zpr["author"] = $_GET['author'];
	$zpr["year"] = false; if ( isset($_GET['year']) && $_GET['year'] != "false" ) $zpr["year"] = $_GET['year'];
	$zpr["style"] = zp_Get_Default_Style(); if ( isset($_GET['style']) && $_GET['style'] != "false" && $_GET['style'] != "default" ) $zpr["style"] = $_GET['style'];
	if ( isset($_GET['limit']) && $_GET['limit'] != 0 )
	{
		$zpr["limit"] = intval($_GET['limit']);
		$zpr["overwrite_request"] = true;
	}
	$zpr["title"] = false; if ( isset($_GET['title']) ) $zpr["title"] = $_GET['title'];

	// Max tags, max results
	$zpr["maxtags"] = false; if ( isset($_GET['maxtags']) ) $zpr["maxtags"] = intval($_GET['maxtags']);
	$zpr["maxresults"] = false; if ( isset($_GET['maxresults']) ) $zpr["maxresults"] = intval($_GET['maxresults']);

	// Term, filter
	$zpr["term"] = false; if ( isset($_GET['term']) ) $zpr["term"] = $_GET['term'];
	$zpr["filter"] = false; if ( isset($_GET['filter']) ) $zpr["filter"] = $_GET['filter'];

	// Sorty by, order
	$zpr["sortby"] = false;
	$zpr["order"] = false;
	$zpr["item_keys_order"] = array();


	// SPECIAL SETTINGS

	if ( isset($_GET['sort_by']) )
	{
		if ( $_GET['sort_by'] == "author" )
		{
			$zpr["sortby"] = "creator";
			$zpr["order"] = "asc";
		}
		else if ( $_GET['sort_by'] == "default" )
		{
			$zpr["sortby"] = "default"; // entry order
		}
		else if ( $_GET['sort_by'] == "year" )
		{
			$zpr["sortby"] = "date";
			$zpr["order"] = "desc";
		}
		else if ( $zpr["type"] == "intext" && $_GET['sort_by'] == "default" )
		{
			$zpr["sortby"] = "default";
		}
		else
		{
			$zpr["sortby"] = $_GET['sort_by'];
		}
	}

	if ( isset($_GET['order'])
			&& ( strtolower($_GET['order']) == "asc" || strtolower($_GET['order']) == "desc" ) )
		$zpr["order"] = strtolower($_GET['order']);

	// Show images, show tags, downloadable, inclusive, notes, abstracts, citeable
	$zpr["showimage"] = false;
	if ( isset($_GET['showimage']) )
		if ( $_GET['showimage'] == "yes" || $_GET['showimage'] == "true"
				|| $_GET['showimage'] === true || $_GET['showimage'] == 1 )
			$zpr["showimage"] = true;
		elseif ( $_GET['showimage'] == "openlib" )
			$zpr["showimage"] = "openlib";

	$zpr["showtags"] = false;
	if ( isset($_GET['showtags'])
			&& ( $_GET['showtags'] == "yes" || $_GET['showtags'] == "true"
					|| $_GET['showtags'] === true || $_GET['showtags'] == 1 ) )
		$zpr["showtags"] = true;

	$zpr["downloadable"] = false;
	if ( isset($_GET['downloadable'])
			&& ( $_GET['downloadable'] == "yes" || $_GET['downloadable'] == "true" || $_GET['downloadable'] === true || $_GET['downloadable'] == 1 ) )
		$zpr["downloadable"] = true;

	$zpr["inclusive"] = false;
	if ( isset($_GET['inclusive'])
			&& ( $_GET['inclusive'] == "yes" || $_GET['inclusive'] == "true" || $_GET['inclusive'] === true || $_GET['inclusive'] == 1 ) )
		$zpr["inclusive"] = true;

	$zpr["shownotes"] = false;
	if ( isset($_GET['shownotes'])
			&& ( $_GET['shownotes'] == "yes" || $_GET['shownotes'] == "true" || $_GET['shownotes'] === true || $_GET['shownotes'] == 1 ) )
		$zpr["shownotes"] = true;

	$zpr["showabstracts"] = false;
	if ( isset($_GET['showabstracts'])
			&& ( $_GET['showabstracts'] == "yes" || $_GET['showabstracts'] == "true" || $_GET['showabstracts'] === true || $_GET['showabstracts'] == 1 ) )
		$zpr["showabstracts"] = true;

	$zpr["citeable"] = false;
	if ( isset($_GET['citeable'])
			&& ( $_GET['citeable'] == "yes" || $_GET['citeable'] == "true" || $_GET['citeable'] === true || $_GET['citeable'] == 1 ) )
		$zpr["citeable"] = true;

	// Target, urlwrap, forcenum
	$zpr["target"] = false;
	if ( isset($_GET['target'])
			&& ( $_GET['target'] == "yes" || $_GET['target'] == "true" || $_GET['target'] === true || $_GET['target'] == 1 ) )
		$zpr["target"] = true;

	$zpr["urlwrap"] = false;
	if ( isset($_GET['urlwrap']) && ( $_GET['urlwrap'] == "title" || $_GET['urlwrap'] == "image" ) )
		$zpr["urlwrap"] = $_GET['urlwrap'];

	$zpr["highlight"] = false;
	if ( isset($_GET['highlight']) ) $zpr["highlight"] = trim( htmlentities( $_GET['highlight'] ) );

	$zpr["forcenum"] = false;
	if ( isset($_GET['forcenum'])
			&& ( $_GET['forcenum'] == "yes" || $_GET['forcenum'] == "true" || $_GET['forcenum'] === true || $_GET['forcenum'] == 1 ) )
		$zpr["forcenum"] = true;


	$zpr["request_start"] = 0; if ( isset($_GET['request_start']) ) $zpr["request_start"] = intval($_GET['request_start']);
	$zpr["request_last"] = 0; if ( isset($_GET['request_last']) ) $zpr["request_last"] = intval($_GET['request_last']);

	return $zpr;

} // function Zotpress_prep_request_vars



/**
 * Preps and formats the Zotpero API request URL.
 *
 * Handles all possible Zotpress parameters for bibliography
 * shortcodes. Per user account.
 *
 * @param obj $wpdb WP DB object.
 * @param arr $zpr Holds all params for request.
 * @param arr $zp_request_queue Holds all requests for all accounts.
 * @param str $api_user_id Optional. API user ID.
 */
function Zotpress_prep_request_URl($wpdb, $zpr, $zp_request_queue, $api_user_id=false)
{
	// Get account and $api_user_id
	if ( $api_user_id ) {
		$zp_account = zp_get_account ($wpdb, $api_user_id);
	}
	else {
		if ( $zpr["api_user_id"] ) {
			$zp_account = zp_get_account ($wpdb, $zpr["api_user_id"]);
			$api_user_id = $zpr["api_user_id"];
		}
		else {
			$zp_account = zp_get_account ($wpdb);
			$api_user_id = $zp_account[0]->api_user_id;
		}
	}

	// Account for single item with new style
	if ( gettype( $zpr["item_key"] ) == "string"
			&& $zpr["item_key"][0] == "{" ) {
		$zpr_temp = explode(':', $zpr["item_key"]);
		if ( count($zpr_temp) > 1 ) $zpr_temp = $zpr_temp[1];
		else $zpr_temp = $zpr_temp[0];
		$zpr["item_key"] = rtrim( $zpr_temp, "}");
	}
	// Account for single item in array with new style: remove curly brackets
	else if ( gettype( $zpr["item_key"] ) == "array"
			&& count( $zpr["item_key"] ) == 1
			&& $zpr["item_key"][0][0] == "{" ) {
		// $zpr["item_key"] = rtrim( explode(':', $zpr["item_key"][0])[1] , "}");
		$zpr["item_key"] = ltrim( rtrim( $zpr["item_key"][0], "}" ), "{" );
	}

	// User type, user id, item type
	$zp_import_url = "https://api.zotero.org/".$zp_account[0]->account_type."/".$api_user_id."/".$zpr["item_type"];

	// Top or single item key
	if ( $zpr["get_top"] ) $zp_import_url .= "/top";

	if ( strpos($zp_request_queue[$api_user_id]["items"], ',') == false )
	// if ( $zpr["item_key"] )
		if ( gettype( $zpr["item_key"] ) == "array"
				&& count( $zpr["item_key"] ) == 1
				&& strpos( $zpr["item_key"][0], ',' ) == false )
			$zp_import_url .= "/" . $zpr["item_key"][0];
		else if ( gettype( $zpr["item_key"] ) == "string"
				&& ( strpos( $zpr["item_key"], "," ) === false
			 		&& strpos( $zpr["item_key"], ";" ) === false ) )
			$zp_import_url .= "/" . $zpr["item_key"];
	if ( $zpr["collection_id"] ) $zp_import_url .= "/" . $zpr["collection_id"];
	if ( $zpr["sub"] ) $zp_import_url .= "/" . $zpr["sub"];
	$zp_import_url .= "?";

	// Public key, if needed
	if (is_null($zp_account[0]->public_key) === false && trim($zp_account[0]->public_key) != "")
		$zp_import_url .= "key=".$zp_account[0]->public_key."&";

	// Style
	$zp_import_url .= "style=".$zpr["style"];

	// Format, limit, etc.
	$zp_import_url .= "&format=json&include=data,bib&limit=".$zpr["limit"];

	// Sort and order
	if ( $zpr["sortby"] && $zpr["sortby"] != "default" )
	{
		$zp_import_url .= "&sort=".$zpr["sortby"];
		if ( $zpr["order"] ) $zp_import_url .= "&direction=".$zpr["order"];
	}

	// Start if multiple
	if ( $zpr["request_start"] != 0 ) $zp_import_url .= "&start=".$zpr["request_start"];

	// Multiple item keys
	// EVENTUAL TO-DO: Limited to 50 item keys at a time ... can I get around this?
	// TODO: Test this with a bib that has 50+ items
	// if ( $zpr["item_key"] && strpos( $zpr["item_key"],"," ) !== false ) $zp_import_url .= "&itemKey=" . $zpr["item_key"];
	if ( substr_count($zp_request_account["items"], ",") >= 50 )
	{
		$items = explode( ",", $zp_request_account["items"] );

		$requests = array();
		$request_items = array();

		foreach ( $items as $item ) {
			if ( count($request_items) < 50 ) {
				array_push( $request_items, $item );
			}
			else {
				array_push( $requests, $request_items );
				unset( $request_items );
			}
		}

		$zp_request_queue[$api_user_id]["requests"] = $requests;
	}
	else {

		// $zp_request_queue[$api_user_id]["requests"] = explode( ",", $zp_request_account["items"] );
	}

	// Tag-specific
	if ( $zpr["tag_id"] )
	{
		if ( strpos($zpr["tag_id"], ",") !== false )
		{
			$temp = explode( ",", $zpr["tag_id"] );

			foreach ( $temp as $temp_tag )
			{
				$zp_import_url .= "&tag=" . urlencode( stripslashes( $temp_tag ));
			}
		}
		else
		{
			$zp_import_url .= "&tag=" . urlencode( stripslashes( $zpr["tag_id"] ));
		}
	}

	// Filtering: collections and tags take priority over authors and year
	// EVENTUAL TODO: Searching by two+ values is not supported on the Zotero side ...
	// For now, we get all and manually filter below
	$zp_author_or_year_multiple = false;

	if ( $zpr["collection_id"] || $zpr["tag_id"] )
	{
		// Check if author or year is set
		if ( $zpr["year"] || $zpr["author"] )
		{
			// Check if author year is set and multiple
			if ( ( $zpr["author"] && strpos( $zpr["author"], "," ) !== false )
					|| ( $zpr["year"] && strpos( $zpr["year"], "," ) !== false ) )
			{
				if ( $zpr["author"] && strpos( $zpr["author"], "," ) !== false ) $zp_author_or_year_multiple = "author";
				else $zp_author_or_year_multiple = "year";
			}
			else // Set but not multiple
			{
				$zp_import_url .= "&qmode=titleCreatorYear";
				if ( $zpr["author"] ) $zp_import_url .= "&q=".urlencode( $zpr["author"] );
				if ( $zpr["year"] && ! $zpr["author"] ) $zp_import_url .= "&q=".$zpr["year"];
			}
		}
	}
	else // no collection or tag
	{
		if ( $zpr["year"] || $zpr["author"] )
		{
			$zp_import_url .= "&qmode=titleCreatorYear";

			if ( $zpr["author"] )
			{
				if ( $zpr["inclusive"] === false )
				{
					$zp_authors = explode( ",", $zpr["author"] );
					$zp_import_url .= "&q=".urlencode( $zp_authors[0] );
					unset( $zp_authors[0] );
					$zpr["author"] = $zp_authors;
				}
				else // inclusive
				{
					$zp_import_url .= "&q=".urlencode( $zpr["author"] );
				}
			}

			if ( $zpr["year"] && ! $zpr["author"] ) $zp_import_url .= "&q=".$zpr["year"];
		}
	}

	// Avoid attachments and notes
	if ( $zpr["item_type"] == "items"
			|| ( $zpr["sub"] && $zpr["sub"] == "items" ) )
		$zp_import_url .= "&itemType=-attachment+||+note";

	// Deal with possible term
	if ( $zpr["term"] )
		if ( $zpr["filter"] && $zpr["filter"] == "tag")
			$zp_import_url .= "&tag=".urlencode( $wpdb->esc_like($zpr["term"]) );
		else
			$zp_import_url .= "&q=".urlencode( $wpdb->esc_like($zpr["term"]) );


	// DEAL WITH MULTIPLE REQUESTS
	if ( count($zp_request_queue) > 0 )
	{
		// Assume items
		if ( array_key_exists("requests", $zp_request_queue[$api_user_id])
				&& count($zp_request_queue[$api_user_id]["requests"]) > 1 )
		{
			$item_keys = "";

			foreach ( $zp_request_queue[$api_user_id]["requests"] as $num => $request ) {
				if ( $item_keys != "" ) $item_keys .= ",";
				$item_keys .= $request;
			}

			$zp_request_queue[$api_user_id]["requests"][$num] = $zp_import_url . "&itemKey=" . $item_keys;
		}
		else // one request or less
		{
			// Ignore for only one item
			if ( strpos( $zp_request_queue[$api_user_id]["items"], "," ) !== false )
			{
				if ( is_array($zp_request_queue[$api_user_id]["items"]) )
					$zp_request_queue[$api_user_id]["items"] = implode(",", $zp_request_queue[$api_user_id]["items"]);

				$zp_request_queue[$api_user_id]["requests"] = array( $zp_import_url . "&itemKey=" . $zp_request_queue[$api_user_id]["items"] );
			}
			else // one item
			{
				$zp_request_queue[$api_user_id]["requests"] = array( $zp_import_url );
			}
		}
	}
	else
	{
		// Assume normal
		$zp_request_queue[$api_user_id]["requests"] = array( $zp_import_url );
	}

	return $zp_request_queue;
}


?>
