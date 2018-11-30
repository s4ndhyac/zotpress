<?php

    function Zotpress_zotpressInTextBib ($atts)
    {
        /*
        *   RELIES ON THESE GLOBAL VARIABLES:
        *
        *   $GLOBALS['zp_shortcode_instances'][get_the_ID()] {instantiated previously}
        *   
        */
        
        extract(shortcode_atts(array(
            'style' => false,
            'sortby' => "default",
            'sort' => false,
            'order' => false,
            
            'image' => false,
            'images' => false,
            'showimage' => "no",
            'showtags' => "no",
			
            'title' => "no",
			
            'download' => "no",
            'downloadable' => false,
            'notes' => false,
            'abstract' => false,
            'abstracts' => false,
            'cite' => false,
            'citeable' => false,
			
            'target' => false,
			'urlwrap' => false,
			
			'highlight' => false,
            'forcenumber' => false,
            'forcenumbers' => false
			
        ), $atts));
        
        
        
        // FORMAT PARAMETERS
        $style = str_replace('"','',html_entity_decode($style));
        $sortby = str_replace('"','',html_entity_decode($sortby));
        
        if ($order) $order = str_replace('"','',html_entity_decode($order));
        else if ($sort) $order = str_replace('"','',html_entity_decode($sort));
        else $order = "ASC";
        
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
        
        $title = str_replace('"','',html_entity_decode($title));
        
        if ($download) $download = str_replace('"','',html_entity_decode($download));
        else if ($downloadable) $download = str_replace('"','',html_entity_decode($downloadable));
        if ($download == "yes" || $download == "true" || $download === true) $download = true; else $download = false;
        
        $notes = str_replace('"','',html_entity_decode($notes));
        
        if ($abstracts) $abstracts = str_replace('"','',html_entity_decode($abstracts));
        else if ($abstract) $abstracts = str_replace('"','',html_entity_decode($abstract));
        
        if ($cite) $cite = str_replace('"','',html_entity_decode($cite));
        else if ($citeable) $cite = str_replace('"','',html_entity_decode($citeable));
        
        if ($target == "new" || $target == "yes" || $target == "_blank" || $target == "true" || $target === true) $target = true;
        else $target = false;
        
        if ($urlwrap == "title" || $urlwrap == "image" )
		$urlwrap = str_replace('"','',html_entity_decode($urlwrap)); else $urlwrap = false;
		
        if ($highlight ) $highlight = str_replace('"','',html_entity_decode($highlight)); else $highlight = false;
        
        if ($forcenumber == "yes" || $forcenumber == "true" || $forcenumber === true)
        $forcenumber = true; else $forcenumber = false;
        if ($forcenumbers == "yes" || $forcenumbers == "true" || $forcenumbers === true)
        $forcenumber = true; else $forcenumber = false;
		
		$api_user_id = false;
		$item_key = "";
        
        
		// Get in-text items
		if ( isset( $GLOBALS['zp_shortcode_instances'][get_the_ID()] ) )
		{
			foreach ( $GLOBALS['zp_shortcode_instances'][get_the_ID()] as $intextitem )
			{
				if ( $api_user_id === false ) $api_user_id = $intextitem["api_user_id"];
				
				if ( $item_key != "" ) $item_key .= ";";
				$item_key .= $intextitem["items"];
			}
		}
		
        // Generate instance id for shortcode
        $zp_instance_id = "zotpress-".md5($item_key.$style.$sortby.$order.$showimage.$showtags.$title.$download.$notes.$abstracts.$cite.$target.$forcenumber);
		
		
        // GENERATE IN-TEXT BIB STRUCTURE
		$zp_output = "\n<div id='zp-InTextBib-".$zp_instance_id."' class='zp-Zotpress zp-Zotpress-InTextBib";
		if ( $forcenumber ) $zp_output .= " forcenumber";
		$zp_output .= " zp-Post-".get_the_ID()."'>";
		$zp_output .= '
			<span class="ZP_API_USER_ID" style="display: none;">'.$api_user_id.'</span>
			<span class="ZP_ITEM_KEY" style="display: none;">'.$item_key.'</span>
			<span class="ZP_STYLE" style="display: none;">'.$style.'</span>
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
			<span class="ZP_POSTID" style="display: none;">'.get_the_ID().'</span>
			<span class="ZOTPRESS_PLUGIN_URL" style="display:none;">'.ZOTPRESS_PLUGIN_URL.'</span>';
		
		$zp_output .= "<div class='zp-List loading'></div><!-- .zp-List --></div><!--.zp-Zotpress-->\n\n";
		
		// Show theme scripts
		$GLOBALS['zp_is_shortcode_displayed'] = true;
		
		return $zp_output;
	}

?>