jQuery(document).ready(function() {


	/*
		
		NAVIGATION STYLES
		
	*/
	
    jQuery("div#zp-Zotpress div#zp-Zotpress-Navigation a.nav-item").click( function() {
        jQuery(this).addClass("active");
    });
	
	
	
	/*
		
		COPYING ITEM KEYS ON CLICK
		
	*/
	
	jQuery('.zp-Entry-ID-Text span').click( function() {
		jQuery(this).parent().find('input').show().select();
		jQuery(this).hide();
	});
	jQuery('.zp-Entry-ID-Text input').blur( function() {
		jQuery(this).hide();
		jQuery(this).parent().find('span').show();
	});
	
	
	
	/*
		
		FILTER CITATIONS
		
	*/
	
	// FILTER BY ACCOUNT
	
	jQuery('div#zp-Browse-Accounts').delegate("select#zp-FilterByAccount", "change", function()
	{
		var id = jQuery(this).val();
		
		jQuery(this).addClass("loading");
		jQuery("#zp-Browse-Account-Options a").addClass("disabled").unbind("click",
			function (e) {
				e.preventDefault();
				return false;
			}
		);
		
		window.location = "admin.php?page=Zotpress&api_user_id="+id;
	});
	
	
	// FILTER BY TAG
	
	jQuery('div#zp-Browse-Bar').delegate("select#zp-List-Tags", "change", function()
	{
		if ( jQuery(this).val() != "--No Tag Selected--" )
			window.location = "admin.php?page=Zotpress&api_user_id="+jQuery('select#zp-FilterByAccount option:selected').val()+"&tag_id="+jQuery("option:selected", this).attr("rel");
	});
	
	
	
	/*
		
		CITATION IMAGE HOVER
		
	*/
	
	jQuery('div.zp-List').delegate("div.zp-Entry-Image", "mouseenter mouseleave", function () {
		jQuery(this).toggleClass("hover");
	});
	
    
});