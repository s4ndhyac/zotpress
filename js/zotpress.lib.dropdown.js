jQuery(document).ready(function()
{
	///////////////////////////////////////////////
	//
	//   ZOTPRESS LIBRARY DROPDOWN
	//
	///////////////////////////////////////////////

	// TO-DO: notes, abstract, target, showtags

	var zpCollectionId = false; if ( jQuery("#ZP_COLLECTION_ID").length > 0 ) zpCollectionId = jQuery("#ZP_COLLECTION_ID").text();
	var zpTagId = false; if ( jQuery("#ZP_TAG_ID").length > 0 ) zpTagId = jQuery("#ZP_TAG_ID").text();
	var zpShowImages = false; if ( jQuery("#ZP_SHOWIMAGE").length > 0 &&  ( jQuery("#ZP_SHOWIMAGE").text() == "yes" || jQuery("#ZP_SHOWIMAGE").text() == "true" ||  jQuery("#ZP_SHOWIMAGE").text() == "1" ) ) zpShowImages = true;
	var zpIsAdmin = false; if ( jQuery("#ZP_ISADMIN").length > 0 ) zpIsAdmin = true;
	var zpTarget = false; if ( jQuery("#ZP_TARGET").length > 0 ) zpTarget = true;
	var zpURLWrap = false; if ( jQuery("#ZP_URLWRAP").length > 0 ) zpURLWrap = jQuery("#ZP_URLWRAP").text();
	var zpItemsFlag = true;

	if ( jQuery("#zp-Browse-Collections-Select").length > 0 )
	{
		zp_get_collections ( 0, 0, false );
		zp_get_tags ( 0, 0, false );
		zp_get_items ( 0, 0, false );

	} // Zotpress DropDown Library


	// Corrects numeric citations.
	function zp_relabel_numbers()
	{
		if ( jQuery("div.zp-List .csl-left-margin").length != 0
		    && /\d/.test( jQuery("div.zp-List .csl-left-margin").text() ) )
		{
		  var count = 1;

		  jQuery("div.zp-List .csl-left-margin").each(function()
          {
            jQuery(this).text( jQuery(this).text().replace(/(\d+)/, count) );
            count++;
          });
		}
	}

  	// Get list of collections
	function zp_get_collections ( request_start, request_last, update )
	{
		// Set parameter defaults
		if ( typeof(request_start) === "undefined" || request_start == "false" || request_start == "" )
			request_start = 0;

		if ( typeof(request_last) === "undefined" || request_last == "false" || request_last == "" )
			request_last = 0;

		jQuery.ajax(
		{
			url: zpShortcodeAJAX.ajaxurl,
			ifModified: true,
			data: {
				'action': 'zpRetrieveViaShortcode',
				'api_user_id': jQuery("#ZP_API_USER_ID").text(),
				'item_type': 'collections',
				'collection_id': zpCollectionId,
				'request_start': request_start,
				'request_last': request_last,
				'sort_by': "title",
				'get_top': true,
				'update': update,
				'zpShortcode_nonce': zpShortcodeAJAX.zpShortcode_nonce
			},
			xhrFields: {
				withCredentials: true
			},
			success: function(data)
			{
				var zp_collections = jQuery.parseJSON( data );
				var zp_collection_options = "";


				// Remove cached bib before adding updates
				// REVIEW: Is adding used_cache necessary?
				if ( update === false ) jQuery("select#zp-Browse-Collections-Select").addClass("used_cache");
				if ( update === true && ! jQuery("select#zp-Browse-Collections-Select").hasClass("updating") )
				{
					jQuery("select#zp-Browse-Collections-Select").empty().addClass("updating");

					if ( zpTagId ) jQuery("select#zp-Browse-Collections-Select").append( "<option value='blank'>--"+zpShortcodeAJAX.txt_nocollsel+"--</option>" );
					if ( ! zpTagId && ! zpCollectionId ) jQuery("select#zp-Browse-Collections-Select").append( "<option value='toplevel'>"+zpShortcodeAJAX.txt_toplevel+"</option>" );
				}


				if ( zpCollectionId && jQuery("#zp-Browse-Collections-Select option.toplevel").length == 0 )
				{
					jQuery("select#zp-Browse-Collections-Select")
						.append( "<option value='blank' class='blank'>"+jQuery("#ZP_COLLECTION_NAME").text()+"</option>\n" );
				}

				if ( zp_collections != "0" && zp_collections.data.length > 0 )
				{
					jQuery.each(zp_collections.data, function( index, collection )
					{
						var temp = "<option value='"+collection.key+"'";
						if ( zpCollectionId == collection.key ) temp += " selected='selected'";
						temp += ">";
						if ( zpCollectionId ) temp += "- "; // For subcollection dropdown indent
						temp += collection.data.name+" (";
						if ( collection.meta.numCollections > 0 ) temp += collection.meta.numCollections+" "+zpShortcodeAJAX.txt_subcoll+", ";
						temp += collection.meta.numItems+" "+zpShortcodeAJAX.txt_items+")</option>\n";

						zp_collection_options += temp;
					});
					jQuery("select#zp-Browse-Collections-Select").append( zp_collection_options );

					// Then, continue with other requests, if they exist
					if ( zp_collections.meta.request_next != false && zp_collections.meta.request_next != "false" )
						zp_get_collections ( zp_collections.meta.request_next, zp_collections.meta.request_last, update );
					else
						if ( ! jQuery("select#zp-Browse-Collections-Select").hasClass("updating") )
							zp_get_collections ( 0, 0, true );
				}

				if ( zpCollectionId && jQuery("#zp-Browse-Collections-Select option.toplevel").length == 0 )
				{
					jQuery("select#zp-Browse-Collections-Select").append( "<option value='toplevel' class='toplevel'>"+zpShortcodeAJAX.txt_backtotop+"</option>\n" );
				}

				// Remove loading indicator
				jQuery("select#zp-Browse-Collections-Select").removeClass("loading").find(".loading").remove();
			},
			error: function(jqXHR)
			{
				console.log("Error for zp_get_collections(): ", jqXHR.statusText);
			},
			complete: function( jqXHRr, textStatus )　{}
		});
	}


	// Get list of tags
	function zp_get_tags ( request_start, request_last, update )
	{
		// Set parameter defaults
		if ( typeof(request_start) === "undefined" || request_start == "false" || request_start == "" )
			request_start = 0;

		if ( typeof(request_last) === "undefined" || request_last == "false" || request_last == "" )
			request_last = 0;

		jQuery.ajax(
		{
			url: zpShortcodeAJAX.ajaxurl,
			ifModified: true,
			data: {
				'action': 'zpRetrieveViaShortcode',
				'api_user_id': jQuery("#ZP_API_USER_ID").text(),
				'item_type': 'tags',
				'is_dropdown': true,
				'maxtags': jQuery("#ZP_MAXTAGS").text(),
				'request_start': request_start,
				'request_last': request_last,
				'update': update,
				'zpShortcode_nonce': zpShortcodeAJAX.zpShortcode_nonce
			},
			xhrFields: {
				withCredentials: true
			},
			success: function(data)
			{
				var zp_tags = jQuery.parseJSON( data );

				var zp_tag_options = "<option id='zp-List-Tags-Select' name='zp-List-Tags-Select'>--"+zpShortcodeAJAX.txt_notagsel+"--</option>\n";
				if ( zpTagId ) zp_tag_options = "<option value='toplevel' class='toplevel'>--"+zpShortcodeAJAX.txt_backtotop+"--</option>\n";



				// Remove cached bib before adding updates
				if ( update === false ) jQuery("select#zp-List-Tags").addClass("used_cache");
				if ( update === true && ! jQuery("select#zp-List-Tags").hasClass("updating") )
					jQuery("select#zp-List-Tags").empty().addClass("updating");

				if ( zp_tags !== 0 && zp_tags.data.length > 0 )
				{
					jQuery.each(zp_tags.data, function( index, tag )
					{
						var temp = "<option class='zp-List-Tag' value='"+tag.tag.replace(/ /g, "+")+"'";

						if ( jQuery("#ZP_TAG_ID").length > 0
								&& jQuery("#ZP_TAG_ID").text() == tag.tag )
						{
							temp += " selected='selected'";
						}
						temp += ">"+tag.tag+" ("+tag.meta.numItems+" "+zpShortcodeAJAX.txt_items+")</option>\n";

						zp_tag_options += temp;
					});
					jQuery("select#zp-List-Tags").append( zp_tag_options );

					// Then, continue with other requests, if they exist
					if ( zp_tags.meta.request_next != false && zp_tags.meta.request_next != "false" )
						zp_get_tags ( zp_tags.meta.request_next, zp_tags.meta.request_last, update );
					else
						if ( ! jQuery("select#zp-List-Tags").hasClass("updating") )
							zp_get_tags ( 0, 0, true );

					// Remove loading indicator
					jQuery("select#zp-List-Tags").removeClass("loading").find(".loading").remove();
				}
				else // Feedback
				{
					// Remove loading indicator
					jQuery("select#zp-List-Tags").removeClass("loading").find(".loading").remove();

					jQuery("select#zp-List-Tags").append(
						"<option rel='empty' value='empty'>"+zpShortcodeAJAX.txt_notags+"</option>"
						);
				}
			},
			error: function(jqXHR)
			{
				console.log("Error for zp_get_tags(): ", jqXHR.statusText);
			},
			complete: function( jqXHRr, textStatus ) {}
		});
	}


	// Get list items
	function zp_get_items ( request_start, request_last, update )
	{
		// Set parameter defaults
		if ( typeof(request_start) === "undefined" || request_start == "false" || request_start == "" )
			request_start = 0;

		if ( typeof(request_last) === "undefined" || request_last == "false" || request_last == "" )
			request_last = 0;

		// Feedback on where in item chunking we're at
		if ( jQuery(".zp-List").hasClass("loading")
			 && jQuery(".zp-List").find(".zp_display_progress").text() == "" )
		{
			jQuery(".zp-List").append(
				"<div class='zp_display_progress'>"+zpShortcodeAJAX.txt_loading+" ...</div>");
		}

		jQuery.ajax(
		{
            async: true,
			url: zpShortcodeAJAX.ajaxurl,
			ifModified: true,
			data: {
				'action': 'zpRetrieveViaShortcode',
				'api_user_id': jQuery("#ZP_API_USER_ID").text(),
				'is_dropdown': true,
				'item_type': 'items',

				'citeable': jQuery("#ZP_CITEABLE").text(),
				'downloadable': jQuery("#ZP_DOWNLOADABLE").text(),
				'showimage': jQuery("#ZP_SHOWIMAGE").text(),

				'target': zpTarget,
				'urlwrap': zpURLWrap,

				'collection_id': zpCollectionId,
				'tag_id': zpTagId,
				'get_top': true,

				'sort_by': jQuery("#ZP_SORTBY").text(),
				'order': jQuery("#ZP_ORDER").text(),

				'update': update,
				'request_start': request_start,
				'request_last': request_last,
				'zpShortcode_nonce': zpShortcodeAJAX.zpShortcode_nonce
			},
			xhrFields: {
				withCredentials: true
			},
			success: function(data)
			{
				var zp_items = jQuery.parseJSON( data );

				// Remove cached bib before adding updates
				if ( update === false )
					jQuery(".zp-List").addClass("used_cache");
				else if ( update === true )
					if ( ! jQuery(".zp-List").hasClass("updating") )
						jQuery(".zp-List").addClass("updating");


				// First, display the items from this request, if any
				if ( typeof zp_items != 'undefined'
						&& zp_items != null
						&& zp_items != 0
						&& zp_items.data.length > 0 )
				{
					var tempItems = "";

					// Feedback on where in item chunking we're at
					if ( ! jQuery(".zp-List").hasClass("updating")
							&& ( zp_items.meta.request_last !== false && zp_items.meta.request_last != "false" )
							&& ( zp_items.meta.request_last !== 0 ) )
					{
						jQuery(".zp-List").find(".zp_display_progress").html(
							"Loading "
							+ (zp_items.meta.request_next) + "-" + (zp_items.meta.request_next+50)
							+ " out of " + (parseInt(zp_items.meta.request_last)+50) + "..." );
					}

					jQuery.each(zp_items.data, function( index, item )
					{
						var tempItem = "";

						// Determine item reference
						var $item_ref = jQuery("div.zp-List #zp-ID-"+item.library.id+"-"+item.key);

						// Year
						var tempItemYear = "0000"; if ( item.meta.hasOwnProperty('parsedDate') ) tempItemYear = item.meta.parsedDate.substring(0, 4);

						// Author
						var tempAuthor = item.data.title;
						if ( item.meta.hasOwnProperty('creatorSummary') )
							tempAuthor = item.meta.creatorSummary.replace( / /g, "-" );

						tempItem += "<div id='zp-ID-"+item.library.id+"-"+item.key+"' class='zp-Entry zpSearchResultsItem hidden";

						// Add update class to item
						if ( update === true ) tempItem += " zp_updated";

						tempItem += "' data-zp-author-year='"+tempAuthor+"-"+tempItemYear+"'";
						tempItem += "' data-zp-year-author='"+tempItemYear+"-"+tempAuthor+"'";
						tempItem += ">\n";

						if ( zpIsAdmin || ( zpShowImages && item.hasOwnProperty('image') ) )
						{
							tempItem += "<div id='zp-Citation-"+item.key+"' class='zp-Entry-Image";
							if ( item.hasOwnProperty('image') ) tempItem += " hasImage";
							tempItem += "' rel='"+item.key+"'>\n";

							if ( item.hasOwnProperty('image') ) tempItem += "<img class='thumb' src='"+item.image[0]+"' alt='image' />\n";
							if ( zpIsAdmin )
                                if ( item.hasOwnProperty('image') ) tempItem += "<a title='Change Image' class='upload' rel='"+item.key+"' href='#'>"+zpShortcodeAJAX.txt_changeimg+"</a>\n";
                                else tempItem += "<a title='Set Image' class='upload' rel='"+item.key+"' href='#'>"+zpShortcodeAJAX.txt_setimg+"</a>\n";
							if ( zpIsAdmin && item.hasOwnProperty('image') ) tempItem += "<a title='Remove Image' class='delete' rel='"+item.key+"' href='#'>&times;</a>\n";

							tempItem += "</div><!-- .zp-Entry-Image -->\n";
						}

						tempItem += item.bib;

						// Show item key if admin
						if ( zpIsAdmin )
                            tempItem += "<label for='item_key'>"+zpShortcodeAJAX.txt_itemkey+":</label><input type='text' name='item_key' class='item_key' value='"+item.key+"'>\n";

						tempItem += "</div><!-- .zp-Entry -->\n";


						// Add this item to the list
						// Replace or skip duplicates
						if ( $item_ref.length > 0 && update === true ) {
							$item_ref.replaceWith( jQuery( tempItem ) );
						}
						else {
							tempItems += tempItem;
						}

					});


					if ( update === false ) jQuery("#zpSearchResultsContainer").append( tempItems );


					// Then, continue with other requests, if they exist
					if ( zp_items.meta.request_next != false && zp_items.meta.request_next != "false" )
					{
						if ( zpItemsFlag == true ) window.zpACPagination(zpItemsFlag, false);
						else window.zpACPagination(zpItemsFlag, true);
						zpItemsFlag = false;

                        // If numeric, update numbers
                        zp_relabel_numbers();

						zp_get_items ( zp_items.meta.request_next, zp_items.meta.request_last, update );
					}
					else
					{
						window.zpACPagination(zpItemsFlag);
						zpItemsFlag = false;

						// Remove loading and feedback
						jQuery(".zp-List").removeClass("loading");
						jQuery(".zp-List").find(".zp_display_progress").remove();

						// Check for updates
						if ( ! jQuery("div.zp-List").hasClass("updating") )
						{
							zp_get_items ( 0, 0, true );
						}
						else
						{
							var sortby = jQuery("#ZP_SORTBY").text();
							var orderby = jQuery("#ZP_ORDER").text();

							// Re-sort if not numbered and sorting by author or date
							if ( ["author","date"].indexOf(sortby) !== -1
									&& jQuery("div.zp-List .csl-left-margin").length == 0 )
							{
								var sortOrder = "data-zp-author-year";
								if ( sortby == "date") sortOrder = "data-zp-year-author";

								jQuery("#"+zp_items.instance+" .zp-List div.zp-Entry").sort(function(a,b)
								{
									var an = a.getAttribute(sortOrder).toLowerCase(),
										bn = b.getAttribute(sortOrder).toLowerCase();

									if (an > bn)
										if ( orderby == "asc" )
											return 1;
										else
											return -1;
									else if (an < bn)
										if ( orderby == "asc" )
											return -1;
										else
											return 1;
									else
										return 0;

								}).detach().appendTo("#"+zp_items.instance+" .zp-List");
							}

                            // If numerical, update numbers
                            zp_relabel_numbers();
						}
					}
				}

				// Message that there's no items
				else
				{
					//if ( update === true )
					//{
						jQuery(".zp-List").removeClass("loading");
						jQuery(".zp-List").find(".zp_display_progress").remove();

						jQuery("#zpSearchResultsContainer").append("<p>"+zpShortcodeAJAX.txt_nocitations+"</p>\n");
					//}
				}
			},
			error: function(jqXHR)
			{
				console.log("Error for zp_get_items(): ", jqXHR.statusText);
			}
		});
	}

});
