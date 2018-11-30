jQuery(document).ready(function()
{
	if ( jQuery("#zp-Zotpress-SearchBox").length > 0
			|| jQuery("#zp-Browse-Collections-Select").length > 0 )
	{
		
		// Set max per page (pagination)
		window.zpPage = 1;
		window.zpMaxPerPage = 10;
		if ( jQuery("#ZOTPRESS_AC_MAXPERPAGE").length > 0
				&& jQuery("#ZOTPRESS_AC_MAXPERPAGE").val().length > 0 )
			window.zpMaxPerPage = jQuery("#ZOTPRESS_AC_MAXPERPAGE").val();
		
		
		// Set up pagination
		window.zpACPagination = function zpACPagination(is_new_query, do_append)
		{
			// e.g.
			// window.zpMaxPerPage = 10
			// window.zpPage = 3
			// 0-9, 10-19, 20-29 ...
			
			// Set parameter defaults
			if ( typeof(do_append) === "undefined" || do_append == "false" || do_append == "" ) do_append = false;
			else do_append = true;
			
			if ( is_new_query == true ) window.zpPage = 1;
			
			// Show the results given the current pagination page
			jQuery("#zpSearchResultsContainer")
				.children()
				.addClass("hidden")
				.slice( (window.zpPage-1)*window.zpMaxPerPage, (window.zpPage*window.zpMaxPerPage) )
				.removeClass("hidden");
			
			// Generate paging menu
			if ( do_append || is_new_query == true || jQuery("#zpSearchResultsPaging").children().length == 0 )
			{
				jQuery("#zpSearchResultsPaging").empty();
				
				for (i = 1; i < (Math.ceil(jQuery("#zpSearchResultsContainer").children().length/window.zpMaxPerPage)+1); i++)
				{
					if ( i == 1 )
					{
						jQuery("#zpSearchResultsPaging").append("<span class='title'>Page</span>");
						jQuery("#zpSearchResultsPaging").append("<a class='selected' href='javascript:void(0)'>"+i+"</a>");
					}
					else
					{
						jQuery("#zpSearchResultsPaging").append("<a href='javascript:void(0)'>"+i+"</a>");
					}
				}
			}
		};
		
		jQuery('body').on("click", "#zpSearchResultsPaging a", function()
		{
			// Highlight this link
			jQuery("#zpSearchResultsPaging a").removeClass("selected");
			jQuery(this).addClass("selected");
			
			// Update pagination page
			window.zpPage = jQuery(this).text();
			
			// Update
			zpACPagination(false);
		});
		
		
		// NAVIGATE BY COLLECTION
		
		jQuery('div#zp-Browse-Bar').delegate("select#zp-Browse-Collections-Select", "change", function()
		{
			var zpHref = window.location.href.split("?");
			
			// Add extra params, if they exist
			var zp_extra_params = ""; if  ( typeof zpHref[1] !== 'undefined' && zpHref[1].indexOf("page=Zotpress") != -1 ) zp_extra_params += "page=Zotpress";
			
			if ( jQuery(this).val() != "blank" )
			{
				if ( jQuery(this).val() != "toplevel" )
				{
					if ( zp_extra_params.length > 0 ) zp_extra_params = "&"+zp_extra_params;
					var api_user_id = ""; if ( jQuery("#zp-FilterByAccount").length > 0 )
						api_user_id = "&api_user_id="+jQuery("option:selected", jQuery("#zp-FilterByAccount")).val();
					var temp = jQuery("option:selected", this).text().split(" (");
					
					var new_location =
						zpHref[0]
						+ "?collection_id=" + jQuery("option:selected", this).val()
						+ "&collection_name=" + temp[0].replace( / /g, "+" )
						+ zp_extra_params;
					
					if ( api_user_id.length > 0 ) new_location = new_location + api_user_id;
					
					window.location = new_location;
				}
				else // Toplevel
				{
					if ( zp_extra_params.length > 0 ) zp_extra_params = "?"+zp_extra_params;
					var api_user_id = ""; if ( jQuery("#zp-FilterByAccount").length > 0 )
						api_user_id = "&api_user_id="+jQuery("option:selected", jQuery("#zp-FilterByAccount")).val();
					
					var new_location = zpHref[0] + zp_extra_params;
					if ( api_user_id.length > 0 ) new_location = new_location + api_user_id;
					
					window.location = new_location;
				}
			}
		});
		
		
		// NAVIGATE BY TAG
		
		jQuery('div#zp-Browse-Bar').delegate("select#zp-List-Tags", "change", function()
		{
			var zpHref = window.location.href.split("?");
			
			// Add extra params, if they exist
			var zp_extra_params = ""; if  ( typeof zpHref[1] !== 'undefined' && zpHref[1].indexOf("page=Zotpress") != -1 ) zp_extra_params += "page=Zotpress";
			
			if ( jQuery(this).val() != "--No Tag Selected--" )
			{
				var api_user_id = ""; if ( jQuery("#zp-FilterByAccount").length > 0 )
					api_user_id = "&api_user_id="+jQuery("option:selected", jQuery("#zp-FilterByAccount")).val();
				
				if ( jQuery(this).val() != "toplevel" )
				{
					if ( zp_extra_params.length > 0 ) zp_extra_params = "&"+zp_extra_params;
					
					var new_location =
						zpHref[0]
						+ "?tag_id="+jQuery("option:selected", this).val()
						+ zp_extra_params;
				}
				else // Toplevel
				{
					if ( zp_extra_params.length > 0 ) zp_extra_params = "?"+zp_extra_params;
					var new_location = zpHref[0] + zp_extra_params;
				}
				
				if ( api_user_id.length > 0 ) new_location = new_location + api_user_id;
				
				window.location = new_location;
			}
			//else
			//{
			//	if ( zp_extra_params.length > 0 ) zp_extra_params = "?"+zp_extra_params;
			//	
			//	window.location = zpHref[0]+zp_extra_params;
			//}
		});
		
	} // Zotpress Library
	
});