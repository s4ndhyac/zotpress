<?php

class HttpResponse
{
    /**
     * List of all known HTTP response codes - used by responseCodeAsText() to
     * translate numeric codes to messages.
     *
     * @var array
     */
    protected static $messages = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );
    /**
     * The HTTP version (1.0, 1.1)
     *
     * @var string
     */
    protected $version;
    /**
     * The HTTP response code
     *
     * @var int
     */
    protected $code;
    /**
     * The HTTP response code as string
     * (e.g. 'Not Found' for 404 or 'Internal Server Error' for 500)
     *
     * @var string
     */
    protected $message;
    /**
     * The HTTP response headers array
     *
     * @var array
     */
    protected $headers = array();
    /**
     * The HTTP response body
     *
     * @var string
     */
    protected $body;
    /**
     * HTTP response constructor
     *
     * In most cases, you would use Zend_Http_Response::fromString to parse an HTTP
     * response string and create a new Zend_Http_Response object.
     *
     * NOTE: The constructor no longer accepts nulls or empty values for the code and
     * headers and will throw an exception if the passed values do not form a valid HTTP
     * responses.
     *
     * If no message is passed, the message will be guessed according to the response code.
     *
     * @param int    $code Response code (200, 404, ...)
     * @param array  $headers Headers array
     * @param string $body Response body
     * @param string $version HTTP version
     * @param string $message Response code as text
     * @throws Exception
     */
    public function __construct($code, array $headers, $body = null, $version = '1.1', $message = null)
    {
        // Make sure the response code is valid and set it
        if (self::responseCodeAsText($code) === null) {
            
            throw new Exception("{$code} is not a valid HTTP response code");
        }
        $this->code = $code;
        foreach ($headers as $name => $value) {
            if (is_int($name)) {
                $header = explode(":", $value, 2);
                if (count($header) != 2) {
                    
                    throw new Exception("'{$value}' is not a valid HTTP header");
                }
                $name  = trim($header[0]);
                $value = trim($header[1]);
            }
            $this->headers[ucwords(strtolower($name))] = $value;
        }
        // Set the body
        $this->body = $body;
        // Set the HTTP version
        if (! preg_match('|^\d\.\d$|', $version)) {
            
            throw new Exception("Invalid HTTP response version: $version");
        }
        $this->version = $version;
        // If we got the response message, set it. Else, set it according to
        // the response code
        if (is_string($message)) {
            $this->message = $message;
        } else {
            $this->message = self::responseCodeAsText($code);
        }
    }
    /**
     * Check whether the response is an error
     *
     * @return boolean
     */
    public function isError()
    {
        $restype = floor($this->code / 100);
        if ($restype == 4 || $restype == 5) {
            return true;
        }
        return false;
    }
    /**
     * Check whether the response in successful
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        $restype = floor($this->code / 100);
        if ($restype == 2 || $restype == 1) { // Shouldn't 3xx count as success as well ???
            return true;
        }
        return false;
    }
    /**
     * Check whether the response is a redirection
     *
     * @return boolean
     */
    public function isRedirect()
    {
        $restype = floor($this->code / 100);
        if ($restype == 3) {
            return true;
        }
        return false;
    }
    /**
     * Get the response body as string
     *
     * This method returns the body of the HTTP response (the content), as it
     * should be in it's readable version - that is, after decoding it (if it
     * was decoded), deflating it (if it was gzip compressed), etc.
     *
     * If you want to get the raw body (as transfered on wire) use
     * $this->getRawBody() instead.
     *
     * @return string
     */
    public function getBody()
    {
        //added by fcheslack - curl adapter handles these things already so they are transparent to Zend_Response
        return $this->getRawBody();
        
        
        $body = '';
        // Decode the body if it was transfer-encoded
        switch (strtolower($this->getHeader('transfer-encoding'))) {
            // Handle chunked body
            case 'chunked':
                $body = self::decodeChunkedBody($this->body);
                break;
            // No transfer encoding, or unknown encoding extension:
            // return body as is
            default:
                $body = $this->body;
                break;
        }
        // Decode any content-encoding (gzip or deflate) if needed
        switch (strtolower($this->getHeader('content-encoding'))) {
            // Handle gzip encoding
            case 'gzip':
                $body = self::decodeGzip($body);
                break;
            // Handle deflate encoding
            case 'deflate':
                $body = self::decodeDeflate($body);
                break;
            default:
                break;
        }
        return $body;
    }
    /**
     * Get the raw response body (as transfered "on wire") as string
     *
     * If the body is encoded (with Transfer-Encoding, not content-encoding -
     * IE "chunked" body), gzip compressed, etc. it will not be decoded.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->body;
    }
    /**
     * Get the HTTP version of the response
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
    /**
     * Get the HTTP response status code
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->code;
    }
    /**
     * Return a message describing the HTTP response code
     * (Eg. "OK", "Not Found", "Moved Permanently")
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
    /**
     * Get the response headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    /**
     * Get a specific header as string, or null if it is not set
     *
     * @param string$header
     * @return string|array|null
     */
    public function getHeader($header)
    {
        $header = ucwords(strtolower($header));
        if (! is_string($header) || ! isset($this->headers[$header])) return null;
        return $this->headers[$header];
    }
    /**
     * Get all headers as string
     *
     * @param boolean $status_line Whether to return the first status line (IE "HTTP 200 OK")
     * @param string $br Line breaks (eg. "\n", "\r\n", "<br />")
     * @return string
     */
    public function getHeadersAsString($status_line = true, $br = "\n")
    {
        $str = '';
        if ($status_line) {
            $str = "HTTP/{$this->version} {$this->code} {$this->message}{$br}";
        }
        // Iterate over the headers and stringify them
        foreach ($this->headers as $name => $value)
        {
            if (is_string($value))
                $str .= "{$name}: {$value}{$br}";
            elseif (is_array($value)) {
                foreach ($value as $subval) {
                    $str .= "{$name}: {$subval}{$br}";
                }
            }
        }
        return $str;
    }
    /**
     * Get the entire response as string
     *
     * @param string $br Line breaks (eg. "\n", "\r\n", "<br />")
     * @return string
     */
    public function asString($br = "\n")
    {
        return $this->getHeadersAsString(true, $br) . $br . $this->getRawBody();
    }
    /**
     * Implements magic __toString()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->asString();
    }
    /**
     * A convenience function that returns a text representation of
     * HTTP response codes. Returns 'Unknown' for unknown codes.
     * Returns array of all codes, if $code is not specified.
     *
     * Conforms to HTTP/1.1 as defined in RFC 2616 (except for 'Unknown')
     * See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10 for reference
     *
     * @param int $code HTTP response code
     * @param boolean $http11 Use HTTP version 1.1
     * @return string
     */
    public static function responseCodeAsText($code = null, $http11 = true)
    {
        $messages = self::$messages;
        if (! $http11) $messages[302] = 'Moved Temporarily';
        if ($code === null) {
            return $messages;
        } elseif (isset($messages[$code])) {
            return $messages[$code];
        } else {
            return 'Unknown';
        }
    }
    /**
     * Extract the response code from a response string
     *
     * @param string $response_str
     * @return int
     */
    public static function extractCode($response_str)
    {
        preg_match("|^HTTP/[\d\.x]+ (\d+)|", $response_str, $m);
        if (isset($m[1])) {
            return (int) $m[1];
        } else {
            return false;
        }
    }
    /**
     * Extract the HTTP message from a response
     *
     * @param string $response_str
     * @return string
     */
    public static function extractMessage($response_str)
    {
        preg_match("|^HTTP/[\d\.x]+ \d+ ([^\r\n]+)|", $response_str, $m);
        if (isset($m[1])) {
            return $m[1];
        } else {
            return false;
        }
    }
    /**
     * Extract the HTTP version from a response
     *
     * @param string $response_str
     * @return string
     */
    public static function extractVersion($response_str)
    {
        preg_match("|^HTTP/([\d\.x]+) \d+|i", $response_str, $m);
        if (isset($m[1])) {
            return $m[1];
        } else {
            return false;
        }
    }
    /**
     * Extract the headers from a response string
     *
     * @param   string $response_str
     * @return  array
     */
    public static function extractHeaders($response_str)
    {
        $headers = array();
        // First, split body and headers
        $parts = preg_split('|(?:\r?\n){2}|m', $response_str, 2);
        if (! $parts[0]) return $headers;
        // Split headers part to lines
        $lines = explode("\n", $parts[0]);
        unset($parts);
        $last_header = null;
        foreach($lines as $line) {
            $line = trim($line, "\r\n");
            if ($line == "") break;
            // Locate headers like 'Location: ...' and 'Location:...' (note the missing space)
            if (preg_match("|^([\w-]+):\s*(.+)|", $line, $m)) {
                unset($last_header);
                $h_name = strtolower($m[1]);
                $h_value = $m[2];
                if (isset($headers[$h_name])) {
                    if (! is_array($headers[$h_name])) {
                        $headers[$h_name] = array($headers[$h_name]);
                    }
                    $headers[$h_name][] = $h_value;
                } else {
                    $headers[$h_name] = $h_value;
                }
                $last_header = $h_name;
            } elseif (preg_match("|^\s+(.+)$|", $line, $m) && $last_header !== null) {
                if (is_array($headers[$last_header])) {
                    end($headers[$last_header]);
                    $last_header_key = key($headers[$last_header]);
                    $headers[$last_header][$last_header_key] .= $m[1];
                } else {
                    $headers[$last_header] .= $m[1];
                }
            }
        }
        return $headers;
    }
    /**
     * Extract the body from a response string
     *
     * @param string $response_str
     * @return string
     */
    public static function extractBody($response_str)
    {
        $parts = preg_split('|(?:\r?\n){2}|m', $response_str, 2);
        if (isset($parts[1])) {
            return $parts[1];
        }
        return '';
    }
    /**
     * Decode a "chunked" transfer-encoded body and return the decoded text
     *
     * @param string $body
     * @return string
     */
    public static function decodeChunkedBody($body)
    {
        // Added by Dan S. -- don't fail on Transfer-encoding:chunked response
        //that isn't really chunked
        if (! preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", trim($body), $m)) {
            return $body;
        }
       
        $decBody = '';
        // If mbstring overloads substr and strlen functions, we have to
        // override it's internal encoding
        if (function_exists('mb_internal_encoding') &&
           ((int) ini_get('mbstring.func_overload')) & 2) {
            $mbIntEnc = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }
        while (trim($body)) {
            if (! preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $body, $m)) {
                
                throw new Exception("Error parsing body - doesn't seem to be a chunked message");
            }
            $length = hexdec(trim($m[1]));
            $cut = strlen($m[0]);
            $decBody .= substr($body, $cut, $length);
            $body = substr($body, $cut + $length + 2);
        }
        if (isset($mbIntEnc)) {
            mb_internal_encoding($mbIntEnc);
        }
        return $decBody;
    }
    /**
     * Decode a gzip encoded message (when Content-encoding = gzip)
     *
     * Currently requires PHP with zlib support
     *
     * @param string $body
     * @return string
     */
    public static function decodeGzip($body)
    {
        if (! function_exists('gzinflate')) {
            
            throw new Exception(
                'zlib extension is required in order to decode "gzip" encoding'
            );
        }
        return gzinflate(substr($body, 10));
    }
    /**
     * Decode a zlib deflated message (when Content-encoding = deflate)
     *
     * Currently requires PHP with zlib support
     *
     * @param string $body
     * @return string
     */
    public static function decodeDeflate($body)
    {
        if (! function_exists('gzuncompress')) {
            
            throw new Exception(
                'zlib extension is required in order to decode "deflate" encoding'
            );
        }
        /**
         * Some servers (IIS ?) send a broken deflate response, without the
         * RFC-required zlib header.
         *
         * We try to detect the zlib header, and if it does not exsit we
         * teat the body is plain DEFLATE content.
         *
         * This method was adapted from PEAR HTTP_Request2 by (c) Alexey Borzov
         *
         * @link http://framework.zend.com/issues/browse/ZF-6040
         */
        $zlibHeader = unpack('n', substr($body, 0, 2));
        if ($zlibHeader[1] % 31 == 0) {
            return gzuncompress($body);
        } else {
            return gzinflate($body);
        }
    }
    /**
     * Create a new Zend_Http_Response object from a string
     *
     * @param string $response_str
     * @return Zend_Http_Response
     */
    public static function fromString($response_str)
    {
        $code    = self::extractCode($response_str);
        $headers = self::extractHeaders($response_str);
        $body    = self::extractBody($response_str);
        $version = self::extractVersion($response_str);
        $message = self::extractMessage($response_str);
        return new HttpResponse($code, $headers, $body, $version, $message);
    }
    
    public function linkHeaders()
    {
        $parsedLinks = [];
        $linkHeader = $this->getHeader('Link');
        $links = explode(',', $linkHeader);
        $linkRegex = '/^<([^>]+)>; rel="([^\"]*)"$/';
        foreach($links as $link){
            $matches = [];
            preg_match($linkRegex, $link, $matches);
            if(count($matches)){
                $parsedLinks[$matches[2]] = $matches[1];
            }
        }
        return $parsedLinks;
    }
    
    public function parseResponseBody() {
        $bodyString = $this->getBody();
        if($this->isError()){
            throw new Exception("Request was an error: {$bodyString}");
        }
        if($this->getHeader('Content-Type') != 'application/json'){
            $contentType = $this->getHeader('Content-Type');
            throw new Exception("Unexpected content type not application/json. {$contentType}");
        }
        $parsedJson = json_decode($bodyString, true);
        return $parsedJson;
    }
    
    public function getTotalResults() {
        return intval($this->getHeader('Total-Results'));
    }
}

    
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
		
		// Show images, show tags, downloadable, uploadable, inclusive, notes, abstracts, citeable
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
      
    $zp_uploadable = false;
    if ( isset($_GET['uploadable'])
        && ( $_GET['uploadable'] == "yes" || $_GET['uploadable'] == "true" || $_GET['uploadable'] === true || $_GET['uploadable'] == 1 ) )
      $zp_uploadable = true;

    $zp_editable = true;
    
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
        
        $i = 0;
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
					if ( $zp_downloadable || $zp_shownotes || $zp_uploadable || $zp_editable)
					{
						// Check if item has children that could be downloads or downloads
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
              $zp_upload_meta = false;
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
              
              $downloadBib = "";
							// Display download link if file exists
              if ( $zp_download_meta )
                $downloadBib = "<a title='Download' class='zp-DownloadURL' href='".ZOTPRESS_PLUGIN_URL."lib/request/request.dl.php?api_user_id=".$zp_api_user_id."&amp;key=".$zp_download_meta["key"]."&amp;content_type=".$zp_download_meta["contentType"]."'>Download</a>";
              else // Display upload link if file does not exist
              {
                $upload_url = ZOTPRESS_PLUGIN_URL."lib/request/request.ul.php?api_user_id=".$zp_api_user_id."&amp;key=".$item->key."&amp;content_type=application/pdf";
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
              
              
              //if($zp_editable)
              {
                //get the item json  
                $get_url = 'https://api.zotero.org/users/'.$zp_api_user_id.'/items/'.$item->key;
                $getch = curl_init();
                $gethttpHeaders = array();
                //set api version - allowed to be overridden by passed in value
                if(!isset($getheaders['Zotero-API-Version'])){
                    $getheaders['Zotero-API-Version'] = ZOTERO_API_VERSION;
                }

              if(!isset($getheaders['Zotero-API-Key'])){
                $getheaders['Zotero-API-Key'] = $zp_account[0]->public_key;
                }
                
                foreach($getheaders as $key=>$val){
                    $gethttpHeaders[] = "$key: $val";
                }

                curl_setopt($getch, CURLOPT_FRESH_CONNECT, true);

                curl_setopt($getch, CURLOPT_URL, $get_url);
                curl_setopt($getch, CURLOPT_HEADER, true);
                curl_setopt($getch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($getch, CURLINFO_HEADER_OUT, true);
                curl_setopt($getch, CURLOPT_HTTPHEADER, $gethttpHeaders);
                curl_setopt($getch, CURLOPT_MAXREDIRS, 3);

                if(TRUE){
                    $getresponseBody = curl_exec($getch);
                    $zresponse = HttpResponse::fromString($getresponseBody);
                }
                
                if(!$getresponseBody){
                    echo $getresponseBody->getStatus() . "\n";
                    echo $getresponseBody->getBody() . "\n";
                    die("Error getting item\n\n");
                }
                else {
                    $itemresponse = json_decode($zresponse->getRawBody(), true);
                    $itemBody = $itemresponse['data'];
                    $creators = $itemBody['creators'];
                    $dynamicAuthors = "";
                    $j = 0;
                    foreach($creators as $creator)
                    {
                      if($creator['creatorType']=="author")
                      {
                        $dynamicAuthors .= "
                        <input type='hidden' name='creators[".$j."][creatorType]' value='author' type='text' />
                        Author first name: <input name='creators[".$j."][firstName]' value='".$creator['firstName']."' type='text' />
                        Author last name: <input name='creators[".$j."][lastName]' value='".$creator['lastName']."' type='text' />
                        <br>";
                        $j++;
                      }
                    }
                    $itemType = $itemBody['itemType'];
                    $dynamicForm = "Title: <input name='title' value='".$itemBody['title']."' type='text' />".$dynamicAuthors;
                    if($itemType == "book")
                    {
                        $dynamicForm .= "
                        Place: <input name='place' value='".$itemBody['place']."' type='text' />
                        Publisher: <input name='publisher' value='".$itemBody['publisher']."' type='text' />
                        Date: <input name='date' value='".$itemBody['date']."' type='text' />
                        No. of Pages: <input name='numPages' value='".$itemBody['numPages']."' type='text' />
                        URL: <input name='url' value='".$itemBody['url']."' type='text' />
                        ";
                    }
                    elseif($itemType=="conferencePaper")
                    {
                      $dynamicForm .= "
                        Abstract: <input name='abstractNote' value='".$itemBody['abstractNote']."' type='text' />
                        Proceedings Title: <input name='proceedingsTitle' value='".$itemBody['proceedingsTitle']."' type='text' />
                        Place: <input name='place' value='".$itemBody['place']."' type='text' />
                        Publisher: <input name='publisher' value='".$itemBody['publisher']."' type='text' />
                        Pages: <input name='pages' value='".$itemBody['pages']."' type='text' />
                        Date: <input name='date' value='".$itemBody['date']."' type='text' />
                        URL: <input name='url' value='".$itemBody['url']."' type='text' />
                        ";
                    }
                    elseif($itemType == "journalArticle")
                    {
                      $dynamicForm .= "
                        Abstract: <input name='abstractNote' value='".$itemBody['abstractNote']."' type='text' />
                        Volume: <input name='volume' value='".$itemBody['volume']."' type='text' />
                        Issue: <input name='issue' value='".$itemBody['issue']."' type='text' />
                        Pages: <input name='pages' value='".$itemBody['pages']."' type='text' />
                        Publication Title: <input name='publicationTitle' value='".$itemBody['publicationTitle']."' type='text' />
                        Date: <input name='date' value='".$itemBody['date']."' type='text' />
                        DOI: <input name='DOI' value='".$itemBody['DOI']."' type='text' />
                        URL: <input name='url' value='".$itemBody['url']."' type='text' />
                        ";
                    }
                    elseif($itemType == "manuscript")
                    {
                      $dynamicForm .= "
                        Place: <input name='place' value='".$itemBody['place']."' type='text' />
                        Date: <input name='date' value='".$itemBody['date']."' type='text' />
                        No. of pages: <input name='numPages' value='".$itemBody['numPages']."' type='text' />
                        URL: <input name='url' value='".$itemBody['url']."' type='text' />
                        ";

                    }
                    elseif($itemType == "report")
                    {
                      $dynamicForm .= "
                        Report Number: <input name='reportNumber' value='".$itemBody['reportNumber']."' type='text' />  
                        Place: <input name='place' value='".$itemBody['place']."' type='text' />
                        Institution: <input name='institution' value='".$itemBody['institution']."' type='text' />
                        Date: <input name='date' value='".$itemBody['date']."' type='text' />
                        Pages: <input name='pages' value='".$itemBody['pages']."' type='text' />
                        URL: <input name='url' value='".$itemBody['url']."' type='text' />
                        ";
                    }


                    $edit_url = ZOTPRESS_PLUGIN_URL."lib/request/request.edit.php?api_user_id=".$zp_api_user_id."&amp;key=".$itemBody['key']."&amp;version=".$itemBody['version'];
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
               }    
              }

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
          $i++;
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