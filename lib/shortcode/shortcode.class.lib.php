<?php
 
class zotpressLib
{
	/**
	 * Creates a HTML-formatted library for the selected account.
	 *
	 * @return     string         	the HTML-formatted subcollections
	 */
	
	private $account = "";
	private $type = false;
	private $filters = false;
	private $minlength = false;
	private $maxresults = false;
	private $maxperpage = false;
	private $maxtags = false;
	private $style = false;
	private $sortby = false;
	private $order = false;
	private $citeable = false;
	private $downloadable = false;
	private $showimage = false;
	private $is_admin = false;
	private $urlwrap = false;
	private $target = false;
	
	public function __construct()
	{
		// Called automatically when an instance is instantiated
	}
	
	public function setAccount($account)
	{
		$this->account = $account;
	}
	
	public function getAccount()
	{
		return $this->account;
	}
	
	public function setType($type)
	{
		$this->type = $type;
	}
	
	public function setFilters($filters)
	{
		$this->filters = $filters;
	}
	
	public function setMinLength($minlength)
	{
		$this->minlength = $minlength;
	}
	
	public function getMinLength()
	{
		return $this->minlength;
	}
	
	public function setMaxResults($maxresults)
	{
		$this->maxresults = $maxresults;
	}
	
	public function getMaxResults()
	{
		return $this->maxresults;
	}
	
	public function setMaxPerPage($maxperpage)
	{
		$this->maxperpage = $maxperpage;
	}
	
	public function getMaxPerPage()
	{
		return $this->maxperpage;
	}
	
	public function setMaxTags($maxtags)
	{
		$this->maxtags = $maxtags;
	}
	
	public function setCiteable($citeable)
	{
		$this->citeable = $citeable;
	}
	
	public function setStyle($style)
	{
		$this->style = strtolower( $style );
	}
	
	public function setSortBy($sortby)
	{
		$this->sortby = strtolower( $sortby );
	}
	
	public function setOrder($order)
	{
		$this->order = strtolower( $order );
	}
	
	public function setDownloadable($download)
	{
		$this->downloadable = $download;
	}
	
	public function setShowImage($showimage)
	{
		if ( $showimage == "yes" || $showimage == "true" || $showimage == true ) $showimage = true;
		else $showimage = false;
		
		$this->showimage = $showimage;
	}
	
	public function setAdmin($setAdmin)
	{
		$this->is_admin = $setAdmin;
	}
	
	public function setURLWrap($urlwrap)
	{
		$this->urlwrap = $urlwrap;
	}
	
	public function setTarget($target)
	{
		$this->target = $target;
	}
	
	
	
	public function getLib()
	{
		global $wpdb;
        $content = "";
		
		
		// Enqueue scripts
		
		if ( $this->type == "dropdown" )
		{
			wp_enqueue_script( 'zotpress.lib.js' );
			wp_enqueue_script( 'zotpress.lib.dropdown.js' );
		}
		else
		{
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script( 'zotpress.lib.js' );
			wp_enqueue_script( 'zotpress.lib.searchbar.js' );
		}
		
		
		// API User ID
		
		global $api_user_id;
		
		if ( isset($_GET['account_id']) && preg_match("/^[0-9]+$/", $_GET['account_id']) )
			$api_user_id = $wpdb->get_var("SELECT nickname FROM ".$wpdb->prefix."zotpress WHERE id='".$_GET['account_id']."'", OBJECT);
		else
			$api_user_id = $this->getAccount()->api_user_id;
		
		
		// Collection ID
		
		global $collection_id;
		
		if (isset($_GET['collection_id']) && preg_match("/^[0-9a-zA-Z]+$/", $_GET['collection_id']))
			$collection_id = trim($_GET['collection_id']);
		else
			$collection_id = false;
		
		
		// Collection Name
		
		global $collection_name;
		
		if (isset($_GET['collection_name']))
			if ( strpos( $_GET['collection_name'], "- " ) == 0 )
				$collection_name = preg_replace( "/- /", "", $_GET['collection_name'], 1 );
			else
				$collection_name = trim($_GET['collection_name']);
		else
			$collection_name = false;
		
		
		// Tag Name
		
		global $tag_id;
		
		if (isset($_GET['tag_id']) && strlen(trim($_GET['tag_id'])) > 0 )
			$tag_id = htmlentities(trim($_GET['tag_id']));
		else
			$tag_id = false;
		
		
        $content .= "<div id=\"zp-Browse\">\n";
        $content .= '<span id="ZP_API_USER_ID" style="display: none;">' .$api_user_id . '</span>';
		if ( $collection_id ) $content .= '<span id="ZP_COLLECTION_ID" style="display: none;">'.$collection_id.'</span>';
		if ( $collection_name ) $content .= '<span id="ZP_COLLECTION_NAME" style="display: none;">'.$collection_name.'</span>';
		if ( $tag_id ) $content .= '<span id="ZP_TAG_ID" style="display: none;">'.$tag_id.'</span>';
		$content .= '<span id="ZP_MAXTAGS" style="display: none;">'.$this->maxtags.'</span>';
		$content .= '<span id="ZP_STYLE" style="display: none;">'.$this->style.'</span>';
		$content .= '<span id="ZP_SORTBY" style="display: none;">'.$this->sortby.'</span>';
		$content .= '<span id="ZP_ORDER" style="display: none;">'.$this->order.'</span>';
		$content .= '<span id="ZP_CITEABLE" style="display: none;">'.$this->citeable.'</span>';
		$content .= '<span id="ZP_DOWNLOADABLE" style="display: none;">'.$this->downloadable.'</span>';
		$content .= '<span id="ZP_SHOWIMAGE" style="display: none;">'.$this->showimage.'</span>';
		$content .= '<span id="ZP_TARGET" style="display: none;">'.$this->target.'</span>';
		$content .= '<span id="ZP_URLWRAP" style="display: none;">'.$this->urlwrap.'</span>';
		if ( $this->is_admin ) $content .= '<span id="ZP_ISADMIN" style="display: none;">'.$this->is_admin.'</span>';
        $content .= "\n";
            
            $content .= '<div id="zp-Browse-Bar">';
                
                if ( $this->type == "dropdown" ):
                
                    $content .= '<div id="zp-Browse-Collections">';
                        $content .= "<div class='zp-Browse-Select'>\n";
                        $content .= "<select id='zp-Browse-Collections-Select' class='loading'>\n";
                        
                        // Set default option
                        $content .= "<option class='loading' value='loading'>Loading ...</option>";
                        if ( $tag_id ) $content .= "<option value='blank'>--No Collection Selected--</option>";
                        if ( ! $tag_id && ! $collection_id ) $content .= "<option value='toplevel'>Top level</option>";
                        
                        $content .= "</select>\n";
                        $content .= "</div>\n\n";
                    $content .= '</div><!-- #zp-Browse-Collections -->';
                    $content .= "\n";
                    
                    $content .= '<div id="zp-Browse-Tags">';
                        $content .= "<div class='zp-Browse-Select'>\n";
                        $content .= '<select id="zp-List-Tags" name="zp-List-Tags" class="loading">';
                        $content .= "\n<option class='loading' value='loading'>Loading ...</option>\n";
                        $content .= "</select>\n";
                        $content .= "</div>\n\n";
                    $content .= '</div><!-- #zp-Browse-Tags -->';
                    $content .= "\n";
                
                else: 
                
                    $content .= '<div id="zp-Zotpress-SearchBox">';
                        $content .= '<input id="zp-Zotpress-SearchBox-Input" class="help" type="text" value="Type to search" />';
                        
                        if ( $this->filters ):
                        
                        $content .= "<div class='zp-SearchBy-Container'>";
                        $content .= "<span class=\"zp-SearchBy\">Search by:</span>";
                        
                        // Turn filter string into array
                        $filters = explode( ",", $this->filters );
                        
                        foreach ( $filters as $id => $filter )
                        {
                            // Account for singular words
                            if ( $filter == "tags" ) $filter = "tag";
                            else $filter = "item";
                            
                            $content .= '<div class="zpSearchFilterContainer">';
                            $content .= '<input type="radio" name="zpSearchFilters" id="'.$filter.'" value="'.$filter.'"';
                            if ( $id == 0 || count($filters) == 1 ) $content .= ' checked="checked"';
                            $content .= '><label for="'.$filter.'">'.$filter.'</label>';
                            $content .= '</div>';
                            $content .= "\n";
                        }
                        $content .= "</div>\n\n";
                        
                        endif; // Filters
                        
                        
                        // Min Length
                        $minlength = 3; if ( $this->getMinLength() ) $minlength = intval($this->getMinLength());
                        $content .= '<input type="hidden" id="ZOTPRESS_AC_MINLENGTH" name="ZOTPRESS_AC_MINLENGTH" value="'.$minlength.'" />';
                        
                        // Max Results
                        $maxresults = 50; if ( $this->getMaxResults() ) $maxresults = intval($this->getMaxResults());
                        $content .= '<input type="hidden" id="ZOTPRESS_AC_MAXRESULTS" name="ZOTPRESS_AC_MAXRESULTS" value="'.$maxresults.'" />';
                        
                        // Max Per Page
                        $maxperpage = 10; if ( $this->getMaxPerPage() ) $maxperpage = intval($this->getMaxPerPage());
                        $content .= '<input type="hidden" id="ZOTPRESS_AC_MAXPERPAGE" name="ZOTPRESS_AC_MAXPERPAGE" value="'.$maxperpage.'" />';
                        
                        // Downloadable, Citeable, Showimages
                        $downloadable = false; if ( $this->downloadable ) $downloadable = $this->downloadable;
                        $citeable = false; if ( $this->citeable ) $citeable = $this->citeable;
                        $showimages = false; if ( $this->showimage ) $showimages = $this->showimage;
                        
                        $content .= '<input type="hidden" id="ZOTPRESS_AC_DOWNLOAD" name="ZOTPRESS_AC_DOWNLOAD" value="'.$downloadable.'" />';
                        $content .= '<input type="hidden" id="ZOTPRESS_AC_CITE" name="ZOTPRESS_AC_CITE" value="'.$citeable.'" />';
                        if ( $showimages ) $content .= '<input type="hidden" id="ZOTPRESS_AC_IMAGES" name="ZOTPRESS_AC_IMAGES" value="true" />';
                        
                        $content .= '<input type="hidden" id="ZOTPRESS_PLUGIN_URL" name="ZOTPRESS_PLUGIN_URL" value="'. ZOTPRESS_PLUGIN_URL.'" />';
                        $content .= '<input type="hidden" id="ZOTPRESS_USER" name="ZOTPRESS_USER" value="'.$this->getAccount()->api_user_id.'" />';
                    $content .= '</div>';
                    $content .= "\n";
                
                endif; // Type 
                
            $content .= '</div><!-- #zp-Browse-Bar -->';
            $content .= "\n\n";
            
            
            $content .= '<div class="zp-List';
            
            if ( $this->type == "dropdown" )
            {
                $content .= ' loading">';
                
                // Display title on dropdown version
                if ( $collection_id )
                {
                    $content .= "<div class='zp-Collection-Title'>";
                        $content .= "<span class='name'>";
                        if ( $collection_name )
                            $content .= htmlspecialchars( $collection_name, ENT_QUOTES );
                        else
                            $content .= "Collection items:";
                        $content .= "</span>";
                        if ( is_admin() )
                            $content .= "<label for='item_key'>Collection Key:</label><input type='text' name='item_key' class='item_key' value='".$collection_id."'>\n";
                    $content .= "</div>\n";
                }
                else if ( $tag_id ) // Top Level
                {
                    $content .= "<div class='zp-Collection-Title'>Viewing items tagged \"<strong>".str_replace("+", " ", $tag_id)."</strong>\"</div>\n";
                }
                else
                {
                    $content .= "<div class='zp-Collection-Title'>Top Level Items</div>\n";
                }
            }
            
            // Searchbar
            else
            {
                $content .= "\">";
                
                // Autocomplete will fill this up
                $content .= '<img class="zpSearchLoading" src="'.ZOTPRESS_PLUGIN_URL.'/images/loading_default.gif" alt="thinking" />';
            }
            
            // Container for results
            $content .= '<div id="zpSearchResultsContainer"></div>';
            
            // Pagination
            $content .= '<div id="zpSearchResultsPaging"></div>';
            
            $content .= '</div><!-- .zp-List -->';
            $content .= "\n";
            
        $content .= '</div><!-- #zp-Browse -->';
        $content .= "\n\n";
        
        return $content;
	}
}
 
?>