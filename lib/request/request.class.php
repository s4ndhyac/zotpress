<?php

/**
 *
 *  ZOTPRESS REQUEST CLASS
 *
 *  Based on Sean Huber's CURL library with additions by Mike Purvis.
 *
 *  Requires: request url (e.g. https://api.zotero.org/...), api user id (can be accessed from request url)
 *  Returns: array with json and headers (json-formatted)
 *
*/

if ( ! class_exists('ZotpressRequest') )
{
    class ZotpressRequest
    {
        // TIME: 300 seconds = 5 minutes; 3600 seconds = 60 minutes
        var $update = false, $request_error = false, $timelimit = 3600, $timeout = 300, $api_user_id;
        
        
        function zp_gzdecode($data)
        {
            // Thanks to Waynn Lue (StackOverflow)
            if ( function_exists("gzdecode") )
                return gzdecode($data);
            else
                return gzinflate(substr($data,10,-8));
        }
        
        
        function get_request_contents( $url, $update )
        {
            $this->update = $update;
            return $this->doRequest( $url );
        }
        
        
        function doRequest( $xml_url )
        {
            // Get and set api user id
            $divider = "users/"; if ( strpos( $xml_url, "groups" ) !== false ) $divider = "groups/";
            $temp1 = explode( $divider, $xml_url );
            $temp2 = explode( "/", $temp1[1] );
            $this->api_user_id = $temp2[0];
            
            // Get the data
            $data = $this->getXmlData( $xml_url );
            
            // Check for request errors
            if ( $this->request_error !== false )
            {
                return $this->request_error;
                exit();
            }
            
            // Otherwise, return the data
            else
            {
                return $data;
            }
        }
        
        
        // Limit Zotero request to every 10 mins
        function checkTime( $last_time )
        {
            $last_time = explode( " ", $last_time );
            $last_time = strtotime( $last_time[1] );
            $now = strtotime( date('h:i:s') );
            
            if ( round(abs($now - $last_time) / 60,2) > 10 )
            {
                return true;
            }
            else // Not time yet
            {
                return false;
            }
        }
        
        
        function getXmlData( $url )
        {
            global $wpdb;
            
            
            // Just want to check for cached version
            if ( $this->update === false )
            {
                // First, check db to see if cached version exists
                $zp_query =
                        "
                        SELECT DISTINCT ".$wpdb->prefix."zotpress_cache.*
                        FROM ".$wpdb->prefix."zotpress_cache
                        WHERE ".$wpdb->prefix."zotpress_cache.request_id = '".md5( $url )."'
                        AND ".$wpdb->prefix."zotpress_cache.api_user_id = '".$this->api_user_id."'
                        ";
                
                $zp_results = $wpdb->get_results($zp_query, OBJECT); unset($zp_query);
                
                if ( count($zp_results) != 0 )
                {
                    //$data = gzdecode($zp_results[0]->json);
                    $data = $this->zp_gzdecode($zp_results[0]->json);
                    $headers = $zp_results[0]->headers;
                }
                
                else // No cached
                {
                    $regular = $this->getRegular( $wpdb, $url );
                    
                    $data = $regular['data'];
                    $headers = $regular['headers'];
                }
                
                $wpdb->flush();
            }
            
            else // Normal
            {
                $regular = $this->getRegular( $wpdb, $url );
                
                $data = $regular['data'];
                $headers = $regular['headers'];
            }
            
            return array( "json" => $data, "headers" => $headers );
        }
        
        
        function getRegular( $wpdb, $url )
        {
            // First, check db to see if cached version exists
            $zp_query =
                    "
                    SELECT DISTINCT ".$wpdb->prefix."zotpress_cache.*
                    FROM ".$wpdb->prefix."zotpress_cache
                    WHERE ".$wpdb->prefix."zotpress_cache.request_id = '".md5( $url )."'
                    AND ".$wpdb->prefix."zotpress_cache.api_user_id = '".$this->api_user_id."'
                    ";
            $zp_results = $wpdb->get_results($zp_query, OBJECT); unset($zp_query);
            
            
            // Then, if no cached version, proceed and save one.
            // Or, if cached version exists, check to see if it's out of date,
            // and return whichever is newer (and cache the newest).
            
            if ( count($zp_results) == 0
                    || ( isset($zp_results[0]->retrieved) && $this->checkTime($zp_results[0]->retrieved) ) )
            {
                $headers_arr = array ( "Zotero-API-Version" => "3" );
                if ( count($zp_results) > 0 ) $headers_arr["If-Modified-Since-Version"] = $zp_results[0]->libver;
                
                // Get response
                $response = wp_remote_get( $url, array ( 'headers' => $headers_arr ) );
                $headers = json_encode( wp_remote_retrieve_headers( $response )->getAll() );
            }
            
            // Proceed if no cached version or to check server for newer
            if ( count($zp_results) == 0
                    || ( isset($response["response"]["code"]) && $response["response"]["code"] != "304" ) )
            {
                // Deal with errors
                if ( is_wp_error($response) || ! isset($response['body']) )
                {
                    $this->request_error = $response->get_error_message();
                    
                    if ($response->get_error_code() == "http_request_failed")
                    {
                        // Try again with less restrictions
                        add_filter('https_ssl_verify', '__return_false');
                        $response = wp_remote_get( $url, array( 'headers' => array("Zotero-API-Version" => "2") ) );
                        
                        if ( is_wp_error($response) || ! isset($response['body']) )
                        {
                            $this->request_error = $response->get_error_message();
                        }
                        else if ( $response == "An error occurred" || ( isset($response['body']) && $response['body'] == "An error occurred") )
                        {
                            $this->request_error = "WordPress was unable to import from Zotero. This is likely caused by an incorrect citation style name. For example, 'mla' is now 'modern-language-association'. Use the name found in the style's URL at the Zotero Style Repository.";
                        }
                        else // no errors this time
                        {
                            $this->request_error = false;
                        }
                    }
                }
                else if ( $response == "An error occurred" || ( isset($response['body']) && $response['body'] == "An error occurred") )
                {
                    $this->request_error = "WordPress was unable to import from Zotero. This is likely caused by an incorrect citation style name. For example, 'mla' is now 'modern-language-association'. Use the name found in the style's URL at the Zotero Style Repository.";
                }
                
                // Then, get actual data
                $data = wp_remote_retrieve_body( $response ); // Thanks to Trainsmart.com developer!
                
                // Make sure tags didn't return an error -- redo if so
                if ( $data == "Tag not found" )
                {
                    $url_break = explode("/", $url);
                    $url = $url_break[0]."//".$url_break[2]."/".$url_break[3]."/".$url_break[4]."/".$url_break[7];
                    $url = str_replace("=50", "=5", $url);
                    
                    $data = $this->getXmlData( $url );
                }
                
                // Add or update cache, if not attachment, etc.
                if ( isset($response["headers"]["last-modified-version"]) )
                {
                    $wpdb->query( $wpdb->prepare( 
                        "
                            INSERT INTO ".$wpdb->prefix."zotpress_cache
                            ( request_id, api_user_id, json, headers, libver, retrieved )
                            VALUES ( %s, %s, %s, %s, %d, %s )
                            ON DUPLICATE KEY UPDATE
                            json = VALUES(json),
                            headers = VALUES(headers),
                            libver = VALUES(libver),
                            retrieved = VALUES(retrieved)
                        ", 
                        array
                        (
                            md5( $url ),
                            $this->api_user_id,
                            gzencode($data),
                            $headers,
                            $response["headers"]["last-modified-version"],
                            date('m/d/Y h:i:s a')
                        )
                    ) );
                }
            }
            
            // Retrieve cached version
            else
            {
                $data = $this->zp_gzdecode($zp_results[0]->json);
                $headers = $zp_results[0]->headers;
            }
            
            $wpdb->flush();
            
            return array( "data" => $data, "headers" => $headers );
        }
    }
}

?>