<?php

/*
 
    Plugin Name: Zotpress
    Plugin URI: http://katieseaborn.com/plugins
    Description: Bringing Zotero and scholarly blogging to your WordPress website.
    Author: Katie Seaborn
    Version: 6.2.4
    Author URI: http://katieseaborn.com
    
*/

/*
 
    Copyright 2017 Katie Seaborn
    
    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at
    
        http://www.apache.org/licenses/LICENSE-2.0
    
    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
    
*/



// GLOBAL VARS ----------------------------------------------------------------------------------
    
    define('ZOTPRESS_PLUGIN_FILE',  __FILE__ );
    define('ZOTPRESS_PLUGIN_URL', plugin_dir_url( ZOTPRESS_PLUGIN_FILE ));
    define('ZOTPRESS_PLUGIN_DIR', dirname( __FILE__ ));
    define('ZOTPRESS_EXPERIMENTAL_EDITOR', FALSE); // Whether experimental editor feature is active or not
    define('ZOTPRESS_VERSION', '6.2.4' );
    
    $GLOBALS['zp_is_shortcode_displayed'] = false;
    $GLOBALS['zp_shortcode_instances'] = array();
    
    $GLOBALS['Zotpress_update_db_by_version'] = '6.2'; // Only change this if the db needs updating - 5.2.6

// GLOBAL VARS ----------------------------------------------------------------------------------
    


// INSTALL -----------------------------------------------------------------------------------------

    include( dirname(__FILE__) . '/lib/admin/admin.install.php' );

// INSTALL -----------------------------------------------------------------------------------------



// ADMIN -------------------------------------------------------------------------------------------
    
    include( dirname(__FILE__) . '/lib/admin/admin.php' );

// END ADMIN --------------------------------------------------------------------------------------



// SHORTCODE -------------------------------------------------------------------------------------

    include( dirname(__FILE__) . '/lib/shortcode/shortcode.php' );
    include( dirname(__FILE__) . '/lib/shortcode/shortcode.intext.php' );
    include( dirname(__FILE__) . '/lib/shortcode/shortcode.intextbib.php' );
    include( dirname(__FILE__) . '/lib/shortcode/shortcode.lib.php' );
    
// SHORTCODE -------------------------------------------------------------------------------------



// WIDGETS -----------------------------------------------------------------------------------------
    
    include( dirname(__FILE__) . '/lib/widget/widget.sidebar.php' );
	include( dirname(__FILE__) . '/lib/widget/widget.php' );
    
// WIDGETS -----------------------------------------------------------------------------------------



// REGISTER ACTIONS -----------------------------------------------------------------------------
    
    /**
    * Admin scripts and styles
    */
    function Zotpress_admin_scripts_css($hook)
    {
		if ( isset($_GET['page']) && ($_GET['page'] == 'Zotpress') )
		{
			wp_enqueue_script( 'jquery' );
			wp_enqueue_media();
			wp_enqueue_script( 'jquery.dotimeout.min.js', ZOTPRESS_PLUGIN_URL . 'js/jquery.dotimeout.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'zotpress.default.js', ZOTPRESS_PLUGIN_URL . 'js/zotpress.default.js', array( 'jquery' ) );
			
			if ( in_array( $hook, array('post.php', 'post-new.php') ) !== true )
			{
				wp_enqueue_script( 'jquery.livequery.js', ZOTPRESS_PLUGIN_URL . 'js/jquery.livequery.js', array( 'jquery' ) );
			}
			
			if ( isset($_GET['help']) && ($_GET['help'] == 'true') )
			{
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-ui-tabs');
				wp_enqueue_style( 'zotpress.help.css', ZOTPRESS_PLUGIN_URL . 'css/zotpress.help.css' );
			}
			
			wp_enqueue_style( 'zotpress.css', ZOTPRESS_PLUGIN_URL . 'css/zotpress.css' );
		}
    }
    add_action( 'admin_enqueue_scripts', 'Zotpress_admin_scripts_css' );
	
	
	function Zotpress_enqueue_admin_ajax( $hook )
	{
		if ( strpos( strtolower($hook), "zotpress" ) !== false )
		{
			wp_enqueue_script( 'zotpress.admin.js', plugin_dir_url( __FILE__ ) . 'js/zotpress.admin.js', array( 'jquery','media-upload','thickbox' ) );
			wp_localize_script( 
				'zotpress.admin.js',
				'zpAccountsAJAX', 
				array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'zpAccountsAJAX_nonce' => wp_create_nonce( 'zpAccountsAJAX_nonce_val' ),
					'action' => 'zpAccountsViaAJAX'
				)
			);
			wp_enqueue_script( 'zotpress.admin.notices.js', plugin_dir_url( __FILE__ ) . 'js/zotpress.admin.notices.js', array( 'jquery' ) );
			wp_localize_script( 
				'zotpress.admin.notices.js',
				'zpNoticesAJAX', 
				array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'zpNoticesAJAX_nonce' => wp_create_nonce( 'zpNoticesAJAX_nonce_val' ),
					'action' => 'zpNoticesViaAJAX'
				)
			);
		}
	}
    add_action( 'admin_enqueue_scripts', 'Zotpress_enqueue_admin_ajax' );
    
    
    /**
    * Add Zotpress to admin menu
    */
    function Zotpress_admin_menu()
    {
        add_menu_page( "Zotpress", "Zotpress", "edit_posts", "Zotpress", "Zotpress_options", ZOTPRESS_PLUGIN_URL."images/icon-admin-sm.png" );
		add_submenu_page( "Zotpress", "Zotpress", "Browse", "edit_posts", "Zotpress" );
		add_submenu_page( "Zotpress", "Accounts", "Accounts", "edit_posts", "admin.php?page=Zotpress&accounts=true" );
		add_submenu_page( "Zotpress", "Options", "Options", "edit_posts", "admin.php?page=Zotpress&options=true" );
		add_submenu_page( "Zotpress", "Help", "Help", "edit_posts", "admin.php?page=Zotpress&help=true" );
    }
    add_action( 'admin_menu', 'Zotpress_admin_menu' );
	
	function Zotpress_admin_menu_submenu($parent_file)
	{
		global $submenu_file;
		
		if ( isset($_GET['accounts']) || isset($_GET['selective'])  || isset($_GET['import']) ) $submenu_file = 'admin.php?page=Zotpress&accounts=true';
		if ( isset($_GET['options']) ) $submenu_file = 'admin.php?page=Zotpress&options=true';
		if ( isset($_GET['help']) ) $submenu_file = 'admin.php?page=Zotpress&help=true';
		
		return $parent_file;
	}
	add_filter('parent_file', 'Zotpress_admin_menu_submenu');
    
    
    /**
    * Add shortcode styles to user's theme
    * Note that this always displays: There's no way to conditionally include it,
    * because the existence of shortcodes is checked after CSS is included.
    */
    function Zotpress_theme_includes()
    {
        wp_register_style('zotpress.shortcode.css', ZOTPRESS_PLUGIN_URL . 'css/zotpress.shortcode.css');
        wp_enqueue_style('zotpress.shortcode.css');
    }
    add_action('wp_print_styles', 'Zotpress_theme_includes');
    
    
    /**
    * Change HTTP request timeout
    */
    function Zotpress_change_timeout($time) { return 60; /* second */ }
    add_filter('http_request_timeout', 'Zotpress_change_timeout');
    
    
    /**
    * TinyMCE word-processor-like features
    */
    function zotpress_tinymce_buttonhooks()
    {
        // Determine default editor features status
        $zp_default_editor = "editor_enable";
        if (get_option("Zotpress_DefaultEditor")) $zp_default_editor = get_option("Zotpress_DefaultEditor");
        
        if ( ( 'post.php' != $hook || 'page.php' != $hook ) && $zp_default_editor != 'editor_enable' )
            return;
        
        // Only add hooks when the current user has permissions AND is in Rich Text editor mode
        if ( ( current_user_can('edit_posts') || current_user_can('edit_pages') ) && get_user_option('rich_editing') )
        {
            add_filter("mce_external_plugins", "zotpress_register_tinymce_javascript");
            add_filter("mce_buttons", "zotpress_register_tinymce_buttons");
        }
    }
   if ( ZOTPRESS_EXPERIMENTAL_EDITOR ) add_action('init', 'zotpress_tinymce_buttonhooks');
    
    // Load the TinyMCE plugin : editor_plugin.js (wp2.5)
    function zotpress_register_tinymce_javascript($plugin_array)
    {
        $plugin_array['zotpress'] = plugins_url('/lib/tinymce-plugin/zotpress-tinymce-plugin.js', __FILE__);
        return $plugin_array;
    }
    
    function zotpress_register_tinymce_buttons($buttons)
    {
        array_push($buttons, "zotpress-cite", "zotpress-list", "zotpress-bib" );
        return $buttons;
    }
    
    
    // Enqueue jQuery in theme if it isn't already enqueued
    // Thanks to WordPress user "eceleste"
    function Zotpress_enqueue_scripts()
    {
        if ( ! isset( $GLOBALS['wp_scripts']->registered[ "jquery" ] ) ) wp_enqueue_script("jquery");
    }
    add_action( 'wp_enqueue_scripts' , 'Zotpress_enqueue_scripts' );

    // Add shortcodes and sidebar widget
    add_shortcode( 'zotpress', 'Zotpress_func' );
    add_shortcode( 'zotpressInText', 'Zotpress_zotpressInText' );
    add_shortcode( 'zotpressInTextBib', 'Zotpress_zotpressInTextBib' );
    add_shortcode( 'zotpressLib', 'Zotpress_zotpressLib' );
    add_action( 'widgets_init', 'ZotpressSidebarWidgetInit' );
    
    // Conditionally serve shortcode scripts
    function Zotpress_theme_conditional_scripts_footer()
    {
        if ( $GLOBALS['zp_is_shortcode_displayed'] === true )
        {
            if ( !is_admin() ) wp_enqueue_script('jquery');
            wp_register_script('jquery.livequery.js', ZOTPRESS_PLUGIN_URL . 'js/jquery.livequery.js', array('jquery'));
            wp_enqueue_script('jquery.livequery.js');
			
			wp_enqueue_script("jquery-effects-core");
			wp_enqueue_script("jquery-effects-highlight");
        }
    }
    add_action('wp_footer', 'Zotpress_theme_conditional_scripts_footer');
	
	
	function Zotpress_enqueue_shortcode_bib()
	{
		wp_register_script( 'zotpress.shortcode.bib.js', plugin_dir_url( __FILE__ ) . 'js/zotpress.shortcode.bib.js', array( 'jquery' ), false, true );
		wp_localize_script( 
			'zotpress.shortcode.bib.js',
			'zpShortcodeAJAX', 
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'zpShortcode_nonce' => wp_create_nonce( 'zpShortcode_nonce_val' ),
				'action' => 'zpRetrieveViaShortcode'
			)
		);
	}
	add_action( 'wp_enqueue_scripts', 'Zotpress_enqueue_shortcode_bib' );
	
	
	function Zotpress_enqueue_shortcode_intext()
	{
		wp_register_script( 'zotpress.shortcode.intext.js', plugin_dir_url( __FILE__ ) . 'js/zotpress.shortcode.intext.js', array( 'jquery' ), false, true );
		wp_localize_script( 
			'zotpress.shortcode.intext.js',
			'zpShortcodeAJAX', 
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'zpShortcode_nonce' => wp_create_nonce( 'zpShortcode_nonce_val' ),
				'action' => 'zpRetrieveViaShortcode'
			)
		);
	}
	add_action( 'wp_enqueue_scripts', 'Zotpress_enqueue_shortcode_intext' );
	
	
	function Zotpress_enqueue_lib_dropdown()
	{
		wp_register_script( 'zotpress.lib.js', plugin_dir_url( __FILE__ ) . 'js/zotpress.lib.js', array( 'jquery' ), false, true );
		wp_register_script( 'zotpress.lib.dropdown.js', plugin_dir_url( __FILE__ ) . 'js/zotpress.lib.dropdown.js', array( 'jquery' ), false, true );
		wp_localize_script( 
			'zotpress.lib.dropdown.js',
			'zpShortcodeAJAX', 
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'zpShortcode_nonce' => wp_create_nonce( 'zpShortcode_nonce_val' ),
				'action' => 'zpRetrieveViaShortcode'
			)
		);
	}
	add_action( 'wp_enqueue_scripts', 'Zotpress_enqueue_lib_dropdown' );
	add_action( 'admin_enqueue_scripts', 'Zotpress_enqueue_lib_dropdown' );
	
	
	function Zotpress_enqueue_lib_searchbar()
	{
		wp_register_script( 'zotpress.lib.js', plugin_dir_url( __FILE__ ) . 'js/zotpress.lib.js', array( 'jquery' ), false, true );
		wp_register_script( 'zotpress.lib.searchbar.js', plugin_dir_url( __FILE__ ) . 'js/zotpress.lib.searchbar.js', array( 'jquery' ), false, true );
		wp_localize_script( 
			'zotpress.lib.searchbar.js',
			'zpShortcodeAJAX', 
			array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'zpShortcode_nonce' => wp_create_nonce( 'zpShortcode_nonce_val' ),
				'action' => 'zpRetrieveViaShortcode'
			)
		);
	}
	add_action( 'wp_enqueue_scripts', 'Zotpress_enqueue_lib_searchbar' );
	add_action( 'admin_enqueue_scripts', 'Zotpress_enqueue_lib_searchbar' );
	
// REGISTER ACTIONS 	---------------------------------------------------------------------------------


// SECURITY 	----------------------------------------------------------------------------------------------
	
	function zp_nonce_life() {
		return 24 * HOUR_IN_SECONDS;
	}
	add_filter( 'nonce_life', 'zp_nonce_life' );

// SECURITY 	----------------------------------------------------------------------------------------------


// ZOTPRESS 6.2.1 NOTIFICATION 	------------------------------------------------------------------------

    if ( in_array( ZOTPRESS_VERSION, array( "6.2.1", "6.2.2") ) )
    {
        if ( ! get_option( 'Zotpress_update_notice_dismissed' ) )
            add_action( 'admin_notices', 'Zotpress_update_notice' );
        
        function Zotpress_update_notice()
        {
        ?>
            <div class="notice update-nag Zotpress_update_notice is-dismissible" >
                <p>Warning: Due to major updates in Zotpress 6.2, you may need to clear the cache of each synced Zotero account.</p>
            </div>
        <?php
        }
        
        function Zotpress_dismiss_update_notice()
        {
            if ( ! get_option( 'Zotpress_update_notice_dismissed' ) 
                    || get_option( 'Zotpress_update_notice_dismissed' ) == 0 )
                update_option( 'Zotpress_update_notice_dismissed', 1 );
        }
        add_action( 'wp_ajax_zpNoticesViaAJAX', 'Zotpress_dismiss_update_notice' );
    }

// ZOTPRESS 6.2.1 NOTIFICATION 	------------------------------------------------------------------------


?>