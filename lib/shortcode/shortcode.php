<?php


    // Include shortcode functions
    require("shortcode.functions.php");
    require("shortcode.ajax.php");
    
    
    function Zotpress_func($atts)
    {
        extract(shortcode_atts(array(
            
            'user_id' => false, // deprecated
            'userid' => false,
            'nickname' => false,
            'nick' => false,
            
            'author' => false,
            'authors' => false,
            'year' => false,
            'years' => false,
            
            'data_type' => false, // deprecated
            'datatype' => "items",
            
            'collection_id' => false,
            'collection' => false,
            'collections' => false,
            
            'item_key' => false,
            'item' => false,
            'items' => false,
            
            'inclusive' => "yes",
            
            'tag_name' => false,
            'tag' => false,
            'tags' => false,
            
            'style' => false,
            'limit' => false,
            
            'sortby' => "default",
            'order' => false,
            'sort' => false,
            
            'title' => "no",
            
            'image' => false,
            'images' => false,
            'showimage' => "no",
            
            'showtags' => "no",
            
            'downloadable' => "no",
            'download' => "no",
            
            'note' => false,
            'notes' => "no",
            
            'abstract' => false,
            'abstracts' => "no",
            
            'cite' => "no",
            'citeable' => false,
            
            'metadata' => false,
            
            'target' => false,
			'urlwrap' => false,
			
			'highlight' => false,
			'forcenumber' => false,
			'forcenumbers' => false
            
        ), $atts, "zotpress"));
        
        
        // FORMAT PARAMETERS
        
        // Filter by account
        if ($user_id) $api_user_id = str_replace('"','',html_entity_decode($user_id));
        else if ($userid) $api_user_id = str_replace('"','',html_entity_decode($userid));
        else $api_user_id = false;
        
        if ($nickname) $nickname = str_replace('"','',html_entity_decode($nickname));
        if ($nick) $nickname = str_replace('"','',html_entity_decode($nick));
        
        // Filter by author
        $author = str_replace('"','',html_entity_decode($author));
        if ($authors) $author = str_replace('"','',html_entity_decode($authors));
        
        // Filter by year
        if ( $year ) $year = str_replace('"','',html_entity_decode($year));
        else if ($years) $year = str_replace('"','',html_entity_decode($years));
        else if (strpos($year, ",") > 0) $year = explode(",", $year);
		else $year = "";
        
        // Format with datatype and content
        if ($data_type) $data_type = str_replace('"','',html_entity_decode($data_type));
        else $data_type = str_replace('"','',html_entity_decode($datatype));
        
        // Filter by collection
        if ($collection_id) $collection_id = str_replace('"','',html_entity_decode($collection_id));
        else if ($collection) $collection_id = str_replace('"','',html_entity_decode($collection));
        else if ($collections) $collection_id = str_replace('"','',html_entity_decode($collections));
		$collection_id = str_replace(" ", "", $collection_id );
        
        if (strpos($collection_id, ",") > 0) $collection_id = explode(",", $collection_id);
        if ($data_type == "collections" && isset($_GET['zpcollection']) ) $collection_id = htmlentities( urldecode( $_GET['zpcollection'] ) );
        
        // Filter by tag
        if ($tag_name) $tag_name = str_replace('"','',html_entity_decode($tag_name));
        else if ($tags) $tag_name = str_replace('"','',html_entity_decode($tags));
        else $tag_name = str_replace('"','',html_entity_decode($tag));
        
        $tag_name = str_replace("+", "", $tag_name);
        if (strpos($tag_name, ",") > 0) $tag_name = explode(",", $tag_name);
        if ($data_type == "tags" && isset($_GET['zptag']) ) $tag_name = htmlentities( urldecode( $_GET['zptag'] ) );
        
        // Filter by itemkey
        if ($item_key) $item_key = str_replace('"','',html_entity_decode($item_key));
        if ($items) $item_key = str_replace('"','',html_entity_decode($items));
        if ($item) $item_key = str_replace('"','',html_entity_decode($item));
        if (strpos($item_key, ",") > 0) $item_key = explode(",", $item_key);
		$item_key = str_replace(" ", "", $item_key );
        
		// Inclusive (for multiple authors)
        if ($inclusive == "yes" || $inclusive == "true" || $inclusive === true ) $inclusive = true; else $inclusive = false;
        
        // Format style
        $style = str_replace('"','',html_entity_decode($style));
        
        // Limit
        $limit = str_replace('"','',html_entity_decode($limit));
        
        // Order / sort
        $sortby = str_replace('"','',html_entity_decode($sortby));
        
        if ($order) $order = str_replace('"','',html_entity_decode($order));
        else if ($sort) $order = str_replace('"','',html_entity_decode($sort));
        if ($order === false) $order = "ASC";
        
        // Show title
		// EVENTUAL TO-DO: Zotpress API 3 doesn't allow multiple sortby params
		// Can I use any set sortby param, then do the year sort in JS?
        $title = str_replace('"','',html_entity_decode($title));
        if ($title == "yes" || $title == "true" || $title === true)
        {
            $title = true;
            $sortby = "year";
            $order= "DESC";
        }
        else { $title = false; }
        
        // Show image
        if ($showimage) $showimage = str_replace('"','',html_entity_decode($showimage));
        if ($image) $showimage = str_replace('"','',html_entity_decode($image));
        if ($images) $showimage = str_replace('"','',html_entity_decode($images));
        
        if ($showimage == "yes" || $showimage == "true" || $showimage === true ) $showimage = true;
		else if ( $showimage === "openlib") $showimage = "openlib";
        else $showimage = false;
        
        // Show tags
        if ($showtags == "yes" || $showtags == "true" || $showtags === true) $showtags = true;
        else $showtags = false;
        
        // Show download link
        if ($download == "yes" || $download == "true" || $download === true
                || $downloadable == "yes" || $downloadable == "true" || $downloadable === true)
            $download = true; else $download = false;
        
        // Show notes
        if ($notes) $notes = str_replace('"','',html_entity_decode($notes));
        else if ($note) $notes = str_replace('"','',html_entity_decode($note));
        
        if ($notes == "yes" || $notes == "true" || $notes === true) $notes = true;
        else $notes = false;
        
        // Show abstracts
        if ($abstracts) $abstracts = str_replace('"','',html_entity_decode($abstracts));
        if ($abstract) $abstracts = str_replace('"','',html_entity_decode($abstract));
        
        if ($abstracts == "yes" || $abstracts == "true" || $abstracts === true) $abstracts = true;
        else $abstracts = false;
        
        // Show cite link
        if ($cite) $cite = str_replace('"','',html_entity_decode($cite));
        if ($citeable) $cite = str_replace('"','',html_entity_decode($citeable));
        
        if ($cite == "yes" || $cite == "true" || $cite === true) $cite = true;
        else $cite = false;
        
        if ( !preg_match("/^[0-9a-zA-Z]+$/", $metadata) ) $metadata = false;
        
		// URL attributes
        if ($target == "yes" || $target == "_blank" || $target == "new" || $target == "true" || $target === true)
        $target = true; else $target = false;
        
        if ($urlwrap == "title" || $urlwrap == "image" ) $urlwrap = str_replace('"','',html_entity_decode($urlwrap));
		else $urlwrap = false;
        
        if ($highlight ) $highlight = str_replace('"','',html_entity_decode($highlight)); else $highlight = false;
        
        if ($forcenumber == "yes" || $forcenumber == "true" || $forcenumber === true)
        $forcenumber = true; else $forcenumber = false;
        if ($forcenumbers == "yes" || $forcenumbers == "true" || $forcenumbers === true)
        $forcenumber = true; else $forcenumber = false;
        
        
        
        // GET ACCOUNT
        
        global $wpdb;
		
		wp_enqueue_script( 'zotpress.shortcode.bib.js' );
		
        // Get account (api_user_id)
        $zp_account = false;
        
        if ($nickname !== false)
        {
            $zp_account = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."zotpress WHERE nickname='".$nickname."'", OBJECT);
			
			if ( is_null($zp_account) ):
                return "<p>Sorry, but the selected Zotpress nickname can't be found.</p>";
            endif;
			
            $api_user_id = $zp_account->api_user_id;
        }
        else if ($api_user_id !== false)
        {
            $zp_account = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."zotpress WHERE api_user_id='".$api_user_id."'", OBJECT);
			
			if ( is_null($zp_account) ):
                return "<p>Sorry, but the selected Zotpress account can't be found.</p>";
            endif;
			
            $api_user_id = $zp_account->api_user_id;
        }
        else if ($api_user_id === false && $nickname === false)
        {
            if (get_option("Zotpress_DefaultAccount") !== false)
            {
                $api_user_id = get_option("Zotpress_DefaultAccount");
                $zp_account = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."zotpress WHERE api_user_id ='".$api_user_id."'", OBJECT);
            }
            else // When all else fails ...
            {
                $zp_account = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."zotpress LIMIT 1", OBJECT);
                $api_user_id = $zp_account->api_user_id;
            }
        }
        
        // Generate instance id for shortcode
		if ( is_array( $item_key ) ) $temp_item_key = implode( "-", $item_key); else $temp_item_key = $item_key;
		if ( is_array( $collection_id ) ) $temp_collection_id = implode( "-", $collection_id); else $temp_collection_id = $collection_id;
		if ( is_array( $tag_name ) ) $temp_tag_name = implode( "-", $tag_name); else $temp_tag_name = $tag_name;
		if ( is_array( $author ) ) $temp_author = implode( "-", $author); else $temp_author = $author;
		if ( is_array( $year ) ) $temp_year = implode( "-", $year); else $temp_year = $year;
		if ( is_array( $sortby ) ) $temp_sortby = implode( "-", $sortby); else $temp_sortby = $sortby;
        $zp_instance_id = "zotpress-".md5($api_user_id.$nickname.$temp_author.$temp_year.$data_type.$temp_collection_id.$temp_item_key.$temp_tag_name.$style.$temp_sortby.$order.$limit.$showimage.$download.$note.$cite.$inclusive);
        
		// Prepare item key
		if ( $item_key ) if ( gettype( $item_key ) != "string" ) $item_key = implode( ",", $item_key );
        
		// Prepare collection
		if ( $collection_id ) if ( gettype( $collection_id ) != "string" ) $collection_id = implode( ",", $collection_id );
        
		// Prepare tags
		if ( $tag_name ) if ( gettype( $tag_name ) != "string" ) $tag_name = implode( ",", $tag_name );
		
		$zp_output = '<div id="' . $zp_instance_id . '" class="zp-Zotpress zp-Zotpress-Bib';
		if ( $forcenumber ) $zp_output .= " forcenumber";
		$zp_output .= '">
		
			<span class="ZP_API_USER_ID" style="display: none;">'.$api_user_id.'</span>
			<span class="ZP_ITEM_KEY" style="display: none;">'.$item_key.'</span>
			<span class="ZP_COLLECTION_ID" style="display: none;">'.$collection_id.'</span>
			<span class="ZP_TAG_ID" style="display: none;">'.$tag_name.'</span>
			<span class="ZP_AUTHOR" style="display: none;">'.$author.'</span>
			<span class="ZP_YEAR" style="display: none;">'.$year.'</span>
			<span class="ZP_DATATYPE" style="display: none;">'.$data_type.'</span>
			<span class="ZP_INCLUSIVE" style="display: none;">'.$inclusive.'</span>
			<span class="ZP_STYLE" style="display: none;">'.$style.'</span>
			<span class="ZP_LIMIT" style="display: none;">'.$limit.'</span>
			<span class="ZP_SORTBY" style="display: none;">'.$sortby.'</span>
			<span class="ZP_ORDER" style="display: none;">'.$order.'</span>
			<span class="ZP_TITLE" style="display: none;">'.$title.'</span>
			<span class="ZP_SHOWIMAGE" style="display: none;">'.$showimage.'</span>
			<span class="ZP_SHOWTAGS" style="display: none;">'.$showtags.'</span>
			<span class="ZP_DOWNLOADABLE" style="display: none;">'.$download.'</span>
			<span class="ZP_NOTES" style="display: none;">'.$notes.'</span>
			<span class="ZP_ABSTRACT" style="display: none;">'.$abstracts.'</span>
			<span class="ZP_CITEABLE" style="display: none;">'.$cite.'</span>
			<span class="ZP_TARGET" style="display: none;">'.$target.'</span>
			<span class="ZP_URLWRAP" style="display: none;">'.$urlwrap.'</span>
			<span class="ZP_FORCENUM" style="display: none;">'.$forcenumber.'</span>
			<span class="ZP_HIGHLIGHT" style="display: none;">'.$highlight.'</span>
			<span class="ZOTPRESS_PLUGIN_URL" style="display:none;">'.ZOTPRESS_PLUGIN_URL.'</span>
			
			<div class="zp-List loading">';
       
		
        // GENERATE SHORTCODE
        
        if ($zp_account === false)
            $zp_output .= "\n<div id='".$zp_instance_id."' class='zp-Zotpress'>Sorry, no citation(s) found for this account.</div>\n";
		
		$zp_output .= "</div><!-- .zp-List --></div><!--.zp-Zotpress-->\n\n";
		
		
		// Display shortcode
		
		$GLOBALS['zp_is_shortcode_displayed'] = true;
		
		return $zp_output;
    }
    
	
?>