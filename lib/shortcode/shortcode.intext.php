<?php

    function Zotpress_zotpressInText ($atts)
    {
        /*
        *   GLOBAL VARIABLES
        *
        *   $GLOBALS['zp_shortcode_instances'] {instantiated in zotpress.php}
        *
        */
        
        extract(shortcode_atts(array(
            
            'item' => false,
            'items' => false,
            
            'pages' => false,
            'format' => "(%a%, %d%, %p%)",
			'brackets' => false,
            'etal' => false, // default (false), yes, no
            'separator' => false, // default (comma), semicolon
            'and' => false, // default (no), and, comma-and
            
            'userid' => false,
            'api_user_id' => false,
            'nickname' => false,
            'nick' => false
            
        ), $atts));
        
        
        
        // PREPARE ATTRIBUTES
        
        if ($items) $items = str_replace(" ", "", str_replace('"','',html_entity_decode($items)));
        else if ($item) $items = str_replace(" ", "", str_replace('"','',html_entity_decode($item)));
        
        $pages = str_replace('"','',html_entity_decode($pages));
        $format = str_replace('"','',html_entity_decode($format));
        $brackets = str_replace('"','',html_entity_decode($brackets));
        
        $etal = str_replace('"','',html_entity_decode($etal));
        if ($etal == "default") { $etal = false; }
        
        $separator = str_replace('"','',html_entity_decode($separator));
        if ($separator == "default") { $separator = false; }
        
        $and = str_replace('"','',html_entity_decode($and));
        if ($and == "default") { $and = false; }
        
        if ($userid) { $api_user_id = str_replace('"','',html_entity_decode($userid)); }
        if ($nickname) { $nickname = str_replace('"','',html_entity_decode($nickname)); }
        if ($nick) { $nickname = str_replace('"','',html_entity_decode($nick)); }
        
        
        
        // GET ACCOUNTS
        
        global $wpdb;
		
		wp_enqueue_script( 'zotpress.shortcode.intext.js' );
        
        $zp_account = false;
        
        if ($nickname !== false)
        {
            $zp_account = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."zotpress WHERE nickname='".$nickname."'", OBJECT);
            $api_user_id = $zp_account->api_user_id;
        }
        else if ($api_user_id !== false)
        {
            $zp_account = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."zotpress WHERE api_user_id='".$api_user_id."'", OBJECT);
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
		//$zp_instance_id = "zotpress-".md5(get_the_ID().$api_user_id.$items);
		$zp_instance_id = "zp-ID-".$api_user_id."-" . str_replace( " ", "_", str_replace( "&", "_", str_replace( "+", "_", str_replace( "/", "_", str_replace( "{", "-", str_replace( "}", "-", str_replace( ",", "_", $items ) ) ) ) ) ) ) ."-".get_the_ID();
		
		if ( ! isset( $GLOBALS['zp_shortcode_instances'][get_the_ID()] ) )
			$GLOBALS['zp_shortcode_instances'][get_the_ID()] = array();
		
        $GLOBALS['zp_shortcode_instances'][get_the_ID()][] = array( "instance_id" => $zp_instance_id, "api_user_id" =>$api_user_id, "items" => $items );
		
		
		// Show theme scripts
		$GLOBALS['zp_is_shortcode_displayed'] = true;
        
		// Output attributes and loading
		//return '<span id="zp-InText-'.$zp_instance_id.'"
		//return '<span class="zp-InText-'.$zp_instance_id."-".count($GLOBALS['zp_shortcode_instances'][get_the_ID()]).' zp-InText-Citation loading"
		return '<span id="zp-InText-'.$zp_instance_id."-".count($GLOBALS['zp_shortcode_instances'][get_the_ID()]).'"
						class="zp-InText-Citation loading"
						rel="{ \'api_user_id\': \''.$api_user_id.'\', \'pages\': \''.$pages.'\', \'items\': \''.$items.'\', \'format\': \''.$format.'\', \'brackets\': \''.$brackets.'\', \'etal\': \''.$etal.'\', \'separator\': \''.$separator.'\', \'and\': \''.$and.'\' }"></span>';
    }

    
?>