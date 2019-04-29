<?php


    require("shortcode.class.lib.php");
    
    
    function Zotpress_zotpressLib($atts)
    {
        extract(shortcode_atts(array(
            
            'user_id' => false, // deprecated
            'userid' => false,
            'nickname' => false,
            'nick' => false,
			
			'type' => false, // dropdown, searchbar
			'searchby' => false, // searchbar only - all [default], collections, items, tags
			'minlength' => 3, // searchbar only - 3 [default]
			'maxresults' => 50,
			'maxperpage' => 10,
			'maxtags' => 100, // dropdown only
			
			'sortby' => 'default',
			'order' => 'asc',
			
			'style' => false, 
			'cite' => false,
			'citeable' => false,
			'download' => false,
			'downloadable' => false,
			'showimage' => false,
			'showimages' => false,
			'showtags' => false, // not implemented
			'abstract' => false, // not implemented
			'notes' => false, // not implemented
			'forcenumber' => false, // not implemented
			
			'target' => false, 
			'urlwrap' => false 
            
        ), $atts, "zotpress"));
        
        
        // FORMAT PARAMETERS
        
        // Filter by account
        if ($user_id) $api_user_id = str_replace('"','',html_entity_decode($user_id));
        else if ($userid) $api_user_id = str_replace('"','',html_entity_decode($userid));
        else $api_user_id = false;
        
        if ($nickname) $nickname = str_replace('"','',html_entity_decode($nickname));
        if ($nick) $nickname = str_replace('"','',html_entity_decode($nick));
		
		
		// Type of display
		if ( $type ) $type = str_replace('"','',html_entity_decode($type)); else $type = "dropdown";
		
		
		// Filters
		if ( $searchby ) $searchby = str_replace('"','',html_entity_decode($searchby));
		
		// Style
		if ( $style ) $style = str_replace('"','',html_entity_decode($style));
		
		// Min length
		if ( $minlength ) $minlength = str_replace('"','',html_entity_decode($minlength));
		
		// Max results
		if ( $maxresults ) $maxresults = str_replace('"','',html_entity_decode($maxresults));
		
		// Max per page
		if ( $maxperpage ) $maxperpage = str_replace('"','',html_entity_decode($maxperpage));
		
		// Max tags
		if ( $maxtags ) $maxtags = str_replace('"','',html_entity_decode($maxtags));
		
		// Sortby
		if ( $sortby ) $sortby = str_replace('"','',html_entity_decode($sortby));
		
		// Order
		if ( $order ) $order = str_replace('"','',html_entity_decode($order));
		
		// Citeable
		if ( $cite ) $cite = str_replace('"','',html_entity_decode($cite));
		if ( $citeable ) $cite = str_replace('"','',html_entity_decode($citeable));
		
		// Downloadable
		if ( $download ) $download = str_replace('"','',html_entity_decode($download));
		if ( $downloadable ) $download = str_replace('"','',html_entity_decode($downloadable));
		
		// Show image
		if ( $showimages ) $showimage = str_replace('"','',html_entity_decode($showimages));
		if ( $showimage ) $showimage = str_replace('"','',html_entity_decode($showimage));
		
		if ( $urlwrap ) $urlwrap = str_replace('"','',html_entity_decode($urlwrap));
		
		if ( $target ) $target = true;
		
		
		// Get API User ID
		
		global $wpdb;
		
        if ($nickname !== false)
        {
            $zp_account = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."zotpress WHERE nickname='".$nickname."'", OBJECT);
			
			if ( is_null($zp_account) ): echo "<p>Sorry, but the selected Zotpress nickname can't be found.</p>"; return false; endif;
			
            $api_user_id = $zp_account->api_user_id;
        }
        else if ($api_user_id !== false)
        {
            $zp_account = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."zotpress WHERE api_user_id='".$api_user_id."'", OBJECT);
			
			if ( is_null($zp_account) ): echo "<p>Sorry, but the selected Zotpress account can't be found.</p>"; return false; endif;
			
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
		
		
		// Use Browse class
		
		$zpLib = new zotpressLib;
		
		$zpLib->setAccount($zp_account);
		$zpLib->setType($type);
		if ( $searchby ) $zpLib->setFilters($searchby);
		$zpLib->setMinLength($minlength);
		$zpLib->setMaxResults($maxresults);
		$zpLib->setMaxPerPage($maxperpage);
		$zpLib->setMaxTags($maxtags);
		$zpLib->setStyle($style);
		$zpLib->setSortBy($sortby);
		$zpLib->setOrder($order);
		$zpLib->setCiteable($cite);
		$zpLib->setDownloadable($download);
		$zpLib->setShowImage($showimage);
		$zpLib->setURLWrap($urlwrap);
		$zpLib->setTarget($target);
		
		// Show theme scripts
        $GLOBALS['zp_is_shortcode_displayed'] = true;
		
		return $zpLib->getLib();
	}

    
?>