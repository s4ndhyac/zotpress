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
		
		global $wpdb;
		$zp_limit = 50; // max 100, 22 seconds
		$zp_overwrite_request = false;
		$zp_overwrite_last_request = false;
		
		// Deal with incoming variables
		$zp_type = "basic"; if ( isset($_GET['type']) && $_GET['type'] != "" ) $zp_type = $_GET['type'];
		$zp_api_user_id = $_GET['api_user_id'];
		$zp_item_type = "items"; if ( isset($_GET['item_type']) && $_GET['item_type'] != "" ) $zp_item_type = $_GET['item_type'];
		$zp_get_top = false; if ( isset($_GET['get_top']) ) $zp_get_top = true;
		$zp_sub = false;
		$zp_is_dropdown = false; if ( isset($_GET['is_dropdown']) ) $zp_is_dropdown = true;
		$zp_update = false; if ( isset($_GET['update']) && $_GET['update'] == "true" ) $zp_update = true;
		
		// instance id, item key, collection id, tag id
		$zp_instance_id = false; if ( isset($_GET['instance_id']) ) $zp_instance_id = $_GET['instance_id'];
		
		$zp_item_key = false;
		if ( isset($_GET['item_key'])
				&& ( $_GET['item_key'] != "false" && $_GET['item_key'] !== false ) )
			$zp_item_key = $_GET['item_key'];
		
		$zp_collection_id = false;
		if ( isset($_GET['collection_id'])
				&& ( $_GET['collection_id'] != "false" && $_GET['collection_id'] !== false ) )
			$zp_collection_id = $_GET['collection_id'];
		
		$zp_tag_id = false;
		if ( isset($_GET['tag_id'])
				&& ( $_GET['tag_id'] != "false" && $_GET['tag_id'] !== false ) )
			$zp_tag_id = $_GET['tag_id'];
		
		// Author, year, style, limit, title
		$zp_author = false; if ( isset($_GET['author']) && $_GET['author'] != "false" ) $zp_author = $_GET['author'];
		$zp_year = false; if ( isset($_GET['year']) && $_GET['year'] != "false" ) $zp_year = $_GET['year'];
		$zp_style = zp_Get_Default_Style(); if ( isset($_GET['style']) && $_GET['style'] != "false" && $_GET['style'] != "default" ) $zp_style = $_GET['style'];
		if ( isset($_GET['limit']) && $_GET['limit'] != 0 )
		{
			$zp_limit = intval($_GET['limit']);
			$zp_overwrite_request = true;
		}
		$zp_title = false; if ( isset($_GET['title']) ) $zp_title = $_GET['title'];
		
		// Max tags, max results
		$zp_maxtags = false; if ( isset($_GET['maxtags']) ) $zp_maxtags = intval($_GET['maxtags']);
		$zp_maxresults = false; if ( isset($_GET['maxresults']) ) $zp_maxresults = intval($_GET['maxresults']);
		
		// Term, filter
		$zp_term = false; if ( isset($_GET['term']) ) $zp_term = $_GET['term'];
		$zp_filter = false; if ( isset($_GET['filter']) ) $zp_filter = $_GET['filter'];
		
		// Sorty by, order
		$zp_sortby = false;
		$zp_order = false;
		
		if ( isset($_GET['sort_by']) )
		{
			if ( $_GET['sort_by'] == "author" )
			{
				$zp_sortby = "creator";
				$zp_order = "asc";
			}
			else if ( $_GET['sort_by'] == "default" )
			{
				$zp_sortby = "dateModified";
			}
			else if ( $_GET['sort_by'] == "year" )
			{
				$zp_sortby = "date";
				$zp_order = "desc";
			}
			else if ( $zp_type == "intext" && $_GET['sort_by'] == "default" )
			{
				$zp_sortby = "default";
			}
			else
			{
				$zp_sortby = $_GET['sort_by'];
			}
		}
		
		if ( isset($_GET['order'])
				&& ( strtolower($_GET['order']) == "asc" || strtolower($_GET['order']) == "desc" ) )
			$zp_order = strtolower($_GET['order']);
		
		// Show images, show tags, downloadable, inclusive, notes, abstracts, citeable
		$zp_showimage = false;
		if ( isset($_GET['showimage']) )
			if ( $_GET['showimage'] == "yes" || $_GET['showimage'] == "true"
					|| $_GET['showimage'] === true || $_GET['showimage'] == 1 )
				$zp_showimage = true;
			elseif ( $_GET['showimage'] == "openlib" )
				$zp_showimage = "openlib";
		
		$zp_showtags = false;
		if ( isset($_GET['showtags'])
				&& ( $_GET['showtags'] == "yes" || $_GET['showtags'] == "true"
						|| $_GET['showtags'] === true || $_GET['showtags'] == 1 ) )
			$zp_showtags = true;
		
		$zp_downloadable = false;
		if ( isset($_GET['downloadable'])
				&& ( $_GET['downloadable'] == "yes" || $_GET['downloadable'] == "true" || $_GET['downloadable'] === true || $_GET['downloadable'] == 1 ) )
			$zp_downloadable = true;
		
		$zp_inclusive = false;
		if ( isset($_GET['inclusive'])
				&& ( $_GET['inclusive'] == "yes" || $_GET['inclusive'] == "true" || $_GET['inclusive'] === true || $_GET['inclusive'] == 1 ) )
			$zp_inclusive = true;
		
		$zp_shownotes = false;
		if ( isset($_GET['shownotes'])
				&& ( $_GET['shownotes'] == "yes" || $_GET['shownotes'] == "true" || $_GET['shownotes'] === true || $_GET['shownotes'] == 1 ) )
			$zp_shownotes = true;
		
		$zp_showabstracts = false;
		if ( isset($_GET['showabstracts'])
				&& ( $_GET['showabstracts'] == "yes" || $_GET['showabstracts'] == "true" || $_GET['showabstracts'] === true || $_GET['showabstracts'] == 1 ) )
			$zp_showabstracts = true;
		
		$zp_citeable = false;
		if ( isset($_GET['citeable'])
				&& ( $_GET['citeable'] == "yes" || $_GET['citeable'] == "true" || $_GET['citeable'] === true || $_GET['citeable'] == 1 ) )
			$zp_citeable = true;
		
		// Target, urlwrap, forcenum
		$zp_target = false;
		if ( isset($_GET['target'])
				&& ( $_GET['target'] == "yes" || $_GET['target'] == "true" || $_GET['target'] === true || $_GET['target'] == 1 ) )
			$zp_target = true;
		
		$zp_urlwrap = false;
		if ( isset($_GET['urlwrap']) && ( $_GET['urlwrap'] == "title" || $_GET['urlwrap'] == "image" ) )
			$zp_urlwrap = $_GET['urlwrap'];
		
		$zp_highlight = false;
		if ( isset($_GET['highlight']) ) $zp_highlight = trim( htmlentities( $_GET['highlight'] ) );
		
		$zp_forcenum = false;
		if ( isset($_GET['forcenum'])
				&& ( $_GET['forcenum'] == "yes" || $_GET['forcenum'] == "true" || $_GET['forcenum'] === true || $_GET['forcenum'] == 1 ) )
			$zp_forcenum = true;
		
		
		$zp_request_start = 0; if ( isset($_GET['request_start']) ) $zp_request_start = intval($_GET['request_start']);
		$zp_request_last = 0; if ( isset($_GET['request_last']) ) $zp_request_last = intval($_GET['request_last']);
		//$zp_request_next = false; if ( isset($_GET['request_next']) ) $zp_request_next = intval($_GET['request_next']);
		
		
		// Include relevant classes and functions
		include( dirname(__FILE__) . '/../request/request.class.php' );
		include( dirname(__FILE__) . '/../request/request.functions.php' );
		
		// Set up Zotpress request
		$zp_import_contents = new ZotpressRequest();
		
		// Get account
		$zp_account = zp_get_account ($wpdb, $zp_api_user_id);
		
		// Set up request meta
		$zp_request_meta = array( "request_last" => $zp_request_last, "request_next" => 0 );
		
		// Set up data variable
		$zp_all_the_data = array();
		
		
		
		/**
		*
		*  Format Zotero request URL:
		*
		*/
		
		// Account for items + collection_id
		if ( $zp_item_type == "items" && $zp_collection_id !== false )
		{
			$zp_item_type = "collections";
			$zp_sub = "items";
			$zp_get_top = false;
		}
		
		// Account for items + zp_tag_id
		if ( $zp_item_type == "items" && $zp_tag_id !== false )
			$zp_get_top = false;
		
		// Account for collection_id + get_top
		if ( $zp_get_top !== false && $zp_collection_id !== false )
		{
			$zp_get_top = false;
			$zp_sub = "collections";
		}
		
		// Account for tag display - let's limit it
		if ( $zp_is_dropdown === true && $zp_item_type == "tags" )
		{
			$zp_sortby = "numItems"; // title
			$zp_order = "desc"; // asc
			$zp_limit = "100"; if ( $zp_maxtags ) $zp_limit = $zp_maxtags;
			$zp_overwrite_request = true;
		}
		
		// Account for $zp_maxresults
		if ( $zp_maxresults )
		{
			// If 50 or less, set as limit
			if ( intval($zp_maxresults) <= 50 )
			{
				$zp_limit = $zp_maxresults;
				$zp_overwrite_request = true;
			}
			
			// If over 50, then overwrite last_request
			else
			{
				$zp_overwrite_last_request = $zp_maxresults;
			}
		}
		
		// Deal with in-text citations
		if ( $zp_item_key )
		{
			if ( strpos( $zp_item_key, "{" ) !== false )
			{
				// Possible format: NUM,{NUM,3-9};{NUM,8}
				$zp_item_groups = explode( ";", $zp_item_key );
				
				$zp_item_key = "";
				
				foreach ( $zp_item_groups as $item_group )
				{
					$zp_item_keys = explode( "},{", $item_group );
					
					foreach ( $zp_item_keys as $key )
					{
						// Skip duplicates
						if ( substr_count( $zp_item_key, $key ) != 0 ) continue;
						
						if ( strpos( $key, "," ) !== false )
						{
							$key = explode( ",", $key );
							$key = $key[0];
						}
						if ( $zp_item_key != "" ) $zp_item_key .= ",";
						$zp_item_key .= str_replace( "{", "", str_replace( "}", "" , $key ) );
					}
				}
			}
			else if ( strpos( $zp_item_key, ";" ) !== false )  // old style
			{
				$zp_item_key = str_replace(";", ",", $zp_item_key);
			}
		}
		
		// User type, user id, item type
		$zp_import_url = "https://api.zotero.org/".$zp_account[0]->account_type."/".$zp_api_user_id."/". $zp_item_type;
		
		// Top or single item key
		if ( $zp_get_top ) $zp_import_url .= "/top";
		if ( $zp_item_key && strpos( $zp_item_key,"," ) === false ) $zp_import_url .= "/" . $zp_item_key;
		if ( $zp_collection_id ) $zp_import_url .= "/" . $zp_collection_id;
		if ( $zp_sub ) $zp_import_url .= "/" . $zp_sub;
		$zp_import_url .= "?";
		
		// Public key, if needed
		if (is_null($zp_account[0]->public_key) === false && trim($zp_account[0]->public_key) != "")
			$zp_import_url .= "key=".$zp_account[0]->public_key."&";
		
		// Style
		$zp_import_url .= "style=".$zp_style;
		
		// Format, limit, etc.
		$zp_import_url .= "&format=json&include=data,bib&limit=".$zp_limit;
		
		// Sort and order
		if ( $zp_sortby && $zp_sortby != "default" )
		{
			$zp_import_url .= "&sort=".$zp_sortby;
			if ( $zp_order ) $zp_import_url .= "&direction=".$zp_order;
		}
		
		// Start if multiple
		if ( $zp_request_start != 0 ) $zp_import_url .= "&start=".$zp_request_start;
		//$zp_import_url .= "&start=".$zp_request_start;
		
		// Multiple item keys
		// EVENTUAL TO-DO: Limited to 50 item keys at a time ... can I get around this?
		if ( $zp_item_key && strpos( $zp_item_key,"," ) !== false ) $zp_import_url .= "&itemKey=" . $zp_item_key;
		
		// Tag-specific
		if ( $zp_tag_id )
		{
			if ( strpos($zp_tag_id, ",") !== false )
			{
				$temp = explode( ",", $zp_tag_id );
				
				foreach ( $temp as $temp_tag )
				{
					$zp_import_url .= "&tag=" . urlencode($temp_tag);
				}
			}
			else
			{
				$zp_import_url .= "&tag=" . urlencode($zp_tag_id);
			}
		}
		
		// Filtering: collections and tags take priority over authors and year
		// EVENTUAL TO-DO: Searching by two+ values is not supported on the Zotero side ...
		// For now, we get all and manually filter below
		$zp_author_or_year_multiple = false;
		
		if ( $zp_collection_id || $zp_tag_id )
		{
			// Check if author or year is set
			if ( $zp_year || $zp_author )
			{
				// Check if author year is set and multiple
				if ( ( $zp_author && strpos( $zp_author, "," ) !== false )
						|| ( $zp_year && strpos( $zp_year, "," ) !== false ) )
				{
					if ( $zp_author && strpos( $zp_author, "," ) !== false ) $zp_author_or_year_multiple = "author";
					else $zp_author_or_year_multiple = "year";
				}
				else // Set but not multiple
				{
					$zp_import_url .= "&qmode=titleCreatorYear";
					if ( $zp_author ) $zp_import_url .= "&q=".urlencode( $zp_author );
					if ( $zp_year && ! $zp_author ) $zp_import_url .= "&q=".$zp_year;
				}
			}
		}
		else // no collection or tag
		{
			if ( $zp_year || $zp_author )
			{
				$zp_import_url .= "&qmode=titleCreatorYear";
				
				if ( $zp_author )
				{
					if ( $zp_inclusive === false )
					{
						$zp_authors = explode( ",", $zp_author );
						$zp_import_url .= "&q=".urlencode( $zp_authors[0] );
						unset( $zp_authors[0] );
						$zp_author = $zp_authors;
					}
					else // inclusive
					{
						$zp_import_url .= "&q=".urlencode( $zp_author );
					}
				}
				
				if ( $zp_year && ! $zp_author ) $zp_import_url .= "&q=".$zp_year;
			}
		}
		
		// Avoid attachments and notes
		if ( $zp_item_type == "items"
				|| ( $zp_sub && $zp_sub == "items" ) )
			$zp_import_url .= "&itemType=-attachment+||+note";
		
		// Deal with possible term
		if ( $zp_term )
			if ( $zp_filter && $zp_filter == "tag")
				$zp_import_url .= "&tag=".urlencode( $wpdb->esc_like($zp_term) );
			else
				$zp_import_url .= "&q=".urlencode( $wpdb->esc_like($zp_term) );
		
		
		
		
		//var_dump($zp_account[0]->public_key);
		//print_r($_GET); var_dump("<br /><br />url: ".$zp_import_url); exit;
		
        
		
		
		
		/**
		*
		*	 Read the data:
		*
		*/
		
		$zp_request = $zp_import_contents->get_request_contents( $zp_import_url, $zp_update );
		
		if ( $zp_request["json"] != "0" )
		{
			$temp_headers = json_decode( $zp_request["headers"] );
			$temp_data = json_decode( $zp_request["json"] );
			
			// Figure out if there's multiple requests and how many
			if ( $zp_request_start == 0
					&& isset($temp_headers->link) && strpos( $temp_headers->link, 'rel="last"' ) !== false )
			{
				$temp_link = explode( ";", $temp_headers->link );
				$temp_link = explode( "start=", $temp_link[1] );
				$temp_link = explode( "&", $temp_link[1] );
				
				$zp_request_meta["request_last"] = $temp_link[0];
			}
			
			// Figure out the next starting position for the next request, if any
			if ( $zp_request_meta["request_last"] >= ($zp_request_start + $zp_limit) )
				$zp_request_meta["request_next"] = $zp_request_start + $zp_limit ;
			
			// Overwrite request if tag limit
			if ( $zp_overwrite_request === true )
			{
				$zp_request_meta["request_next"] = 0;
				$zp_request_meta["request_last"] = 0;
			}
			
			// Overwrite last_request
			if ( $zp_overwrite_last_request )
			{
				// Make sure it's less than the total available items
				if ( isset( $temp_headers->{"total-results"} ) 
						&& $temp_headers->{"total-results"} < $zp_overwrite_last_request )
					$zp_overwrite_last_request = intval( ceil( intval($temp_headers->{"total-results"}) / $zp_limit ) - 1 ) * $zp_limit;
				else
					$zp_overwrite_last_request = intval( ceil( $zp_overwrite_last_request / $zp_limit ) ) * $zp_limit;
				
				$zp_request_meta["request_last"] = $zp_overwrite_last_request;
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
				if ( $zp_shownotes ) $zp_notes_num = 1;
				if ( $zp_showimage ) $zp_showimage_keys = "";
				
				// Get individual items
				foreach ( $temp_data as $item )
				{
					// Set target for links
					$zp_target_output = ""; if ( $zp_target ) $zp_target_output = "target='_blank' ";
					
					// Author filtering: skip non-matching authors
					// EVENTUAL TO-DO: Zotero API 3 searches title and author, so wrong authors appear
					if ( $zp_author && count($item->data->creators) > 0 )
					{
						$zp_authors_check = false;
						
						if ( gettype($zp_author) != "array" && strpos($zp_author, ",") !== false ) // multiple
						{
							// Deal with multiple authors
							$zp_authors = explode( ",", $zp_author );
							
							foreach ( $zp_authors as $author )
								if ( zp_check_author_continue( $item, $author ) === true ) $zp_authors_check = true;
						}
						else // single or inclusive
						{
							if ( $zp_inclusive === false )
							{
								$author_exists_count = 1;
								
								foreach ( $zp_author as $author )
									if ( zp_check_author_continue( $item, $author ) === true ) $author_exists_count++;
								
								if ( $author_exists_count == count($zp_author)+1 ) $zp_authors_check = true;
							}
							else // inclusive and single
							{
								if ( zp_check_author_continue( $item, $zp_author ) === true ) $zp_authors_check = true;
							}
						}
						
						if ( $zp_authors_check === false ) continue;
					}
					
					// Year filtering: skip non-matching years
					if ( $zp_year && isset($item->meta->parsedDate) )
					{
						if ( strpos($zp_year, ",") !== false ) // multiple
						{
							$zp_years_check = false;
							$zp_years = explode( ",", $zp_year );
							
							foreach ( $zp_years as $year )
								if ( zp_get_year( $item->meta->parsedDate ) == $year ) $zp_years_check = true;
							
							if ( ! $zp_years_check ) continue;
						}
						else // single
						{
							if ( zp_get_year( $item->meta->parsedDate ) != $zp_year ) continue;
						}
					}
					
					// Skip non-matching years for author-year pairs
					if ( $zp_year && $zp_author && isset($item->meta->parsedDate) )
						if ( zp_get_year( $item->meta->parsedDate ) != $zp_year ) continue;
					
					// Add item key for show image
					if ( $zp_showimage ) $zp_showimage_keys .= " ".$item->key;
					
					// Hyperlink or URL Wrap
					if ( isset( $item->data->url ) && strlen($item->data->url) > 0 )
					{
						if ( $zp_urlwrap && $zp_urlwrap == "title" && $item->data->title )
						{
							///* TESTING, REMOVE */ if ( $item->key != "V8NZD8EX" ) continue;
							
							
							// First: Get rid of text URL if it appears as text in the citation:
							// TO-DO: Does this account for all citation styles?
							/* chicago-author-date */$item->bib = str_ireplace( htmlentities($item->data->url."."), "", $item->bib ); // Note the period
							/* APA */ $item->bib = str_ireplace( htmlentities($item->data->url), "", $item->bib );
							$item->bib = str_ireplace( " Retrieved from ", "", $item->bib );
							$item->bib = str_ireplace( " Available from: ", "", $item->bib );
							
							
							// Next, get rid of double space characters (two space characters next to each other):
							$item->bib = preg_replace( '/&#xA0;/', ' ', preg_replace( '/[[:blank:]]+/', ' ', $item->bib ) );
							
							
							// Next, prep bib by replacing entity quotes:
							$item->bib = str_ireplace( "&ldquo;", "&quot;",
													str_ireplace( "&rdquo;", "&quot;",
															htmlentities(
																html_entity_decode( $item->bib, ENT_QUOTES, "UTF-8" ),
																ENT_QUOTES,
																"UTF-8"
															)
														)
												);
							
							
							// Then replace non-entity quote characters with entities:
							// Note: The below conflicts with foreign characters ...
							//$item->bib = html_entity_decode( $item->bib, ENT_QUOTES, "UTF-8" );
							//$item->bib = iconv( "UTF-8", "ASCII//TRANSLIT", $item->bib );
							
							// Instead, let's try replacing special Word characters:
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
							
							// Re-encode for foreign characters, but don't encode quotes:
							$item->bib = htmlentities( $item->bib, ENT_NOQUOTES, "UTF-8" );
							
							
							// Next, prep title:
							$item->data->title = htmlentities( $item->data->title, ENT_COMPAT, "UTF-8" );
							$item->data->title = str_ireplace("&nbsp;", " ", $item->data->title );
							
							// If wrapping title, wrap it:
							$item->bib = str_ireplace(
									$item->data->title,
									"<a ".$zp_target_output."href='".$item->data->url."'>".$item->data->title."</a>",
									$item->bib
								);
							
							// Finally, revert bib entities:
							$item->bib = html_entity_decode( $item->bib, ENT_QUOTES, "UTF-8" );
							
							///* TESTING, REMOVE */ var_dump("\n<br><br>TESTWRAP: <br><br>\n\n".$item->data->title."<br><br>\n\n".$item->data->url."<br /><br />\n\n"); var_dump($item->bib);continue;
						
						
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
						// HTTP format
						if ( strpos( $item->bib, "doi:" ) !== false
                                && strpos( $item->bib, "doi.org" ) == false )
                        // Styles without the http://
						{
							$item->bib = str_ireplace(
									"doi:" . $item->data->DOI,
									"<a ".$zp_target_output."href='http://doi.org/".$item->data->DOI."'>http://doi.org/".$item->data->DOI."</a>",
									$item->bib
								);
						}
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
					if ( $zp_citeable )
						$item->bib = preg_replace( '~(.*)' . preg_quote('</div>', '~') . '(.*?)~', '$1' . " <a title='Cite in RIS Format' class='zp-CiteRIS' href='".ZOTPRESS_PLUGIN_URL."lib/request/request.cite.php?api_user_id=".$zp_api_user_id."&amp;item_key=".$item->key."'>Cite</a> </div>" . '$2', $item->bib, 1 );
					
					// Highlight text
					if ( $zp_highlight )
						$item->bib = str_ireplace( $zp_highlight, "<strong>".$zp_highlight."</strong>", $item->bib );
					
					// Downloads, notes
					if ( $zp_downloadable || $zp_shownotes )
					{
						// Check if item has children that could be downloads
						if ( $item->meta->numChildren > 0 )
						{
							$zp_child_url = "https://api.zotero.org/".$zp_account[0]->account_type."/".$zp_api_user_id."/items";
							$zp_child_url .= "/".$item->key."/children?";
							if (is_null($zp_account[0]->public_key) === false && trim($zp_account[0]->public_key) != "")
								$zp_child_url .= "key=".$zp_account[0]->public_key."&";
							$zp_child_url .= "&format=json&include=data";
							
							// Get data
							$zp_import_child = new ZotpressRequest();
							$zp_child_request = $zp_import_child->get_request_contents( $zp_child_url, $zp_update );
							$zp_children = json_decode( $zp_child_request["json"] );
							
							$zp_download_meta = false;
							$zp_notes_meta = array();
							
							foreach ( $zp_children as $zp_child )
							{
								// Check for downloads
								if ( $zp_downloadable )
								{
									if ( isset($zp_child->data->linkMode)
                                        && ( $zp_child->data->linkMode == "imported_file"
                                            || $zp_child->data->linkMode == "imported_url")
                                        && preg_match('(pdf|doc|docx|ppt|pptx|latex|rtf|odt|odp)', $zp_child->data->filename) === 1 
                                    )
									{
										$zp_download_meta = array (
												"key" => $zp_child->data->key,
												"contentType" => $zp_child->data->contentType
											);
									}
								}
								
								// Check for notes
								if ( $zp_shownotes )
								{
									if ( isset($zp_child->data->itemType) && $zp_child->data->itemType == "note" )
										$zp_notes_meta[count($zp_notes_meta)] = $zp_child->data->note;
								}
							}
							
							// Display download link if file exists
							if ( $zp_download_meta )
								$item->bib = preg_replace('~(.*)' . preg_quote( '</div>', '~') . '(.*?)~', '$1' . " <a title='Download' class='zp-DownloadURL' href='".ZOTPRESS_PLUGIN_URL."lib/request/request.dl.php?api_user_id=".$zp_api_user_id."&amp;key=".$zp_download_meta["key"]."&amp;content_type=".$zp_download_meta["contentType"]."'>Download</a></div>" . '$2', $item->bib, 1 );
							
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
					} // $zp_downloadable
					
					array_push( $zp_all_the_data,  $item);
				} // foreach item
				
				// Show images
				if ( $zp_showimage )
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
									if ( $zp_urlwrap && $zp_urlwrap == "image" && $zp_all_the_data[$id]->data->url != "" )
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
					if ( $zp_showimage === "openlib" )
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
									if ( $zp_urlwrap && $zp_urlwrap == "image" && $zp_all_the_data[$id]->data->url != "" )
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
				
				// Re-sort data if in-text and default sort
				if ( $zp_type == "intext" && $zp_sortby == "default" )
				{
					$temp_arr = array();
					
					foreach ( array_unique( explode( ",", $zp_item_key ) ) as $temp_key )
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
						"instance" => $zp_instance_id,
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
		unset($zp_api_user_id);
		unset($zp_account);
		
		$wpdb->flush();
		
		exit();
    }
    add_action( 'wp_ajax_zpRetrieveViaShortcode', 'Zotpress_shortcode_AJAX' );
    add_action( 'wp_ajax_nopriv_zpRetrieveViaShortcode', 'Zotpress_shortcode_AJAX' );
    
    
?>