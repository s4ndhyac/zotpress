<?php
    
    

    /****************************************************************************************
    *
    *     ZOTPRESS BASIC IMPORT FUNCTIONS
    *
    ****************************************************************************************/
    
    function zp_db_prep ($input)
    {
        $input =  str_replace("%", "%%", $input);
        return ($input);
    }
    
    
    
    function zp_extract_year ($date)
    {
		if ( strlen($date) > 0 ):
			preg_match_all( '/(\d{4})/', $date, $matches );
			if ( isset($matches[0][0]) ):
				return $matches[0][0];
			else:
				return "";
			endif;
		else:
			return "";
		endif;
    }
    
    
    
    function zp_get_api_user_id ($api_user_id_incoming=false)
    {
        if (isset($_GET['api_user_id']) && preg_match("/^[0-9]+$/", $_GET['api_user_id']) == 1)
            $api_user_id = htmlentities($_GET['api_user_id']);
        else if ($api_user_id_incoming !== false)
            $api_user_id = $api_user_id_incoming;
        else
            $api_user_id = false;
        
        return $api_user_id;
    }
    
    
    
    function zp_get_account ($wpdb, $api_user_id_incoming=false)
    {
        if ($api_user_id_incoming !== false)
		{
            $zp_account = $wpdb->get_results(
										$wpdb->prepare(
										"SELECT * FROM ".$wpdb->prefix."zotpress WHERE api_user_id='%s'",
										$api_user_id_incoming
										)
									);
		}
        else
		{
            $zp_account = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."zotpress ORDER BY id DESC LIMIT 1");
		}
        
        return $zp_account;
    }



	// Moved to admin.functions
    //function zp_get_accounts ($wpdb)
    //{
    //    $zp_accounts = $wpdb->get_results("SELECT api_user_id FROM ".$wpdb->prefix."zotpress");
    //    
    //    return $zp_accounts;
    //}
	
    
	
    function zp_clear_cache_for_user ($wpdb, $api_user_id)
    {
        $wpdb->query("DELETE FROM ".$wpdb->prefix."zotpress_cache WHERE api_user_id='".$api_user_id."'");
    }
	
	
	
	// Takes single author
	function zp_check_author_continue( $item, $author )
	{
		$author_continue = false;
		$author = strtolower($author);
		
		// Accounts for last names with: de, van, el, seif
		if ( strpos( strtolower($author), "van " ) !== false )
		{
			$author = explode( "van ", $author );
			$author[1] = "van ".$author[1];
		}
		else if ( strpos( strtolower($author), "de " ) !== false )
		{
			$author = explode( "de ", $author );
			$author[1] = "de ".$author[1];
		}
		else if ( strpos( strtolower($author), "el " ) !== false )
		{
			$author = explode( "el ", $author );
			$author[1] = "el ".$author[1];
		}
		else if ( strpos( strtolower($author), "seif " ) !== false )
		{
			$author = explode( "seif ", $author );
			$author[1] = "seif ".$author[1];
		}
		else
		{
			// First and last names
			if ( strpos( strtolower($author), " " ) !== false )
			{
				$author = explode( " ", $author );
				
				//// Deal with last name only
				//if ( count($author) == 1 ) $author[1] = $author[0];
				
				// Deal with multiple blanks, assume multiple first/middle names
				if ( count($author) > 2 )
				{
					$new_name = array();
					foreach ( $author as $num => $author_name )
					{
						if ( $num == 0 ) $new_name[0] .= $author_name;
						else if ( $num != count($author)-1 ) $new_name[0] .= " ". $author_name;
						else if ( $num == count($author)-1 ) $new_name[1] .= $author_name;
					}
					$author = $new_name;
				}
			}
			else // Just last name
			{
				$author = array( $author );
			}
		}
		
		// Deal with blank firstname
		if ( $author[0] == "" )
		{
			$author[0] = $author[1];
			unset( $author[1] );
		}
		
		// Trim firstname
		$author[0] = trim($author[0]);
		
		// Check
		foreach ( $item->data->creators as $creator )
		{
			if ( count($author) == 1 ) // last name only
			{
				if ( ( isset($creator->lastName) && strtolower($creator->lastName) == $author[0] )
						|| ( isset($creator->name) && strtolower($creator->name) == $author[0] ) )
					$author_continue = true;
			}
			else // first and last names
			{
				if 	(
						( isset($creator->firstName)
							&& ( strtolower($creator->firstName) == $author[0]
									&& strtolower($creator->lastName) == $author[1] )
						)
						|| ( isset($creator->name) && ( strtolower($creator->name) == implode(" ", $author) ) )
					)
					$author_continue = true;
			}
		}
		
		return $author_continue;
	
	} // function zp_check_author_continue
    

?>