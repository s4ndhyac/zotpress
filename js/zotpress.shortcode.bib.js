jQuery(document).ready(function()
{

    /****************************************************************************************
     *
     *     ZOTPRESS BIBLIOGRAPHY
     *
     *
     ****************************************************************************************/
	
	
	if ( jQuery(".zp-Zotpress-Bib").length > 0 )
	{
		var zp_all_items = new Array();
		
		// Get list items:
		function zp_get_items ( request_start, request_last, $instance, params, update )
		{
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
					'instance_id': $instance.attr("id"),
					'api_user_id': jQuery(".ZP_API_USER_ID", $instance).text(),
					'item_type': jQuery(".ZP_DATATYPE", $instance).text(),
					
					'item_key': params.zpItemkey,
					'collection_id': params.zpCollectionId,
					'tag_id': params.zpTagId,
					
					'author': params.zpAuthor,
					'year': params.zpYear,
					'style': params.zpStyle,
					'limit': params.zpLimit,
					'title': params.zpTitle,
					
					'showimage': params.zpShowImages,
					'showtags': params.zpShowTags,
					'downloadable': params.zpDownloadable,
					'inclusive': params.zpInclusive,
					'shownotes': params.zpShowNotes,
					'showabstracts': params.zpShowAbstracts,
					'citeable': params.zpCiteable,
					
					'target': params.zpTarget,
					'urlwrap': params.zpURLWrap,
					'highlight': params.zpHighlight,
					
					'sort_by': jQuery(".ZP_SORTBY", $instance).text(),
					'order': jQuery(".ZP_ORDER", $instance).text(),
					
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
					
					// First, display the items from this request, if any:
					if ( typeof zp_items != 'undefined' && zp_items != null && zp_items != 0 && zp_items.data.length > 0 )
					{
						var tempItems = "";
						if ( params.zpShowNotes == true ) var tempNotes = "";
						if ( params.zpTitle == true ) var tempTitle = "";
						
						
						// Indicate whether cache has been used:
						if ( update === false )
						{
							jQuery("#"+zp_items.instance+" .zp-List").addClass("used_cache");
						}
						else if ( update === true )
						{
							// Remove existing notes temporarily:
							if ( ! jQuery("#"+zp_items.instance+" .zp-List").hasClass("updating")
									&& jQuery("#"+zp_items.instance+" .zp-Citation-Notes").length > 0 )
								jQuery("#"+zp_items.instance+" .zp-Citation-Notes").remove();
							
							if ( ! jQuery("#"+zp_items.instance+" .zp-List").hasClass("updating") )
								jQuery("#"+zp_items.instance+" .zp-List").addClass("updating");
							
							params.zpForceNumsCount = 1;
						}
						
						
						jQuery.each(zp_items.data, function( index, item )
						{
							var tempItem = "";
							
							// Determine item reference
							var $item_ref = jQuery("#"+zp_items.instance+" .zp-List #zp-ID-"+jQuery(".ZP_API_USER_ID", $instance).text()+"-"+item.key);
							
							// Replace or skip duplicates
							if ( $item_ref.length > 0 )
							{
								if ( update === false && ! jQuery("#"+zp_items.instance+" .zp-List").hasClass("used_cache") )
									return false;
								
								//if ( update === true && jQuery("#"+zp_items.instance+" .zp-List").hasClass("used_cache") )
								//	$item_ref.remove();
								//else
								//	return true;
							}
							
							// Year
							var tempItemYear = "0000";
							if ( item.meta.hasOwnProperty('parsedDate') ) tempItemYear = item.meta.parsedDate.substring(0, 4);
							
							// Author
							var tempAuthor = item.data.title;
							if ( item.meta.hasOwnProperty('creatorSummary') ) tempAuthor = item.meta.creatorSummary.replace( / /g, "-" );
							
							// Title
							if ( params.zpTitle == true )
							{
								// Update title and display
								if ( tempTitle != tempItemYear )
								{
									tempTitle = tempItemYear;
									tempItems += "<h3>"+tempTitle+"</h3>\n";
								}
							}
							
							tempItem += "<div id='zp-ID-"+jQuery(".ZP_API_USER_ID", $instance).text()+"-"+item.key+"'";
							tempItem += " data-zp-author-year='"+tempAuthor+"-"+tempItemYear+"' class='zp-Entry zpSearchResultsItem";
							
							// Add update class to item
							if ( update === true ) tempItem += " zp_updated";
							
							// Image
							if ( jQuery("#"+zp_items.instance+" .ZP_SHOWIMAGE").text().trim().length > 0
									&& item.hasOwnProperty('image') )
							{
								tempItem += " zp-HasImage'>\n";
								tempItem += "<div id='zp-Citation-"+item.key+"' class='zp-Entry-Image hasImage' rel='"+item.key+"'>\n";
								
								// URL wrap image if applicable
								if ( params.zpURLWrap == "image" && item.data.url != "" )
								{
									tempItem += "<a href='"+item.data.url+"'";
									if ( params.zpTarget ) tempItem += " target='_blank'";
									tempItem += ">";
								}
								tempItem += "<img class='thumb' src='"+item.image[0]+"' alt='image' />\n";
								if ( params.zpURLWrap == "image" && item.data.url != "" ) tempItem += "</a>";
								tempItem += "</div><!-- .zp-Entry-Image -->\n";
							}
							else
							{
								tempItem += "'>\n";
							}
							
							// Force numbers
							if ( jQuery("#"+zp_items.instance+" .ZP_FORCENUM").text().length > 0
									&& jQuery("#"+zp_items.instance+" .ZP_FORCENUM").text() == "1" )
							{
								if ( ! /csl-left-margin/i.test(item.bib) ) // if existing style numbering not found
								{
									item.bib = item.bib.replace( '<div class="csl-entry">', '<div class="csl-entry">'+params.zpForceNumsCount+". " );
									params.zpForceNumsCount++;
								}
							}
							
							tempItem += item.bib;
							
							// Add abstracts, if any
							if ( params.zpShowAbstracts == true &&
									( item.data.hasOwnProperty('abstractNote') && item.data.abstractNote.length > 0 ) )
								tempItem +="<p class='zp-Abstract'><span class='zp-Abstract-Title'>Abstract:</span> " +item.data.abstractNote+ "</p>\n";
							
							// Add tags, if any
							if ( params.zpShowTags == true &&
									( item.data.hasOwnProperty('tags') && item.data.tags.length > 0 ) )
							{
								tempItem += "<p class='zp-Zotpress-ShowTags'><span class='title'>Tags:</span> ";
								
								jQuery.each(item.data.tags, function ( tindex, tag )
								{
									tempItem += "<span class='tag'>" + tag.tag + "</span>";
									if ( tindex != (item.data.tags.length-1) ) tempItem += "<span class='separator'>,</span> ";
								});
								tempItem += "</p>\n";
							}
							
							tempItem += "</div><!-- .zp-Entry -->\n";
							
							// Add notes, if any
							if ( params.zpShowNotes == true && item.hasOwnProperty('notes') )
								tempNotes += item.notes;
							
							
							
							
							// Add this item to the list
							// Replace or skip duplicates
							if ( $item_ref.length > 0 && update === true )
							{
								$item_ref.replaceWith( jQuery( tempItem ) );
							}
							else
							{
								tempItems += tempItem;
							}
							
						}); // each item
						
						
						
						// Append cached/initial items to list:
						if ( update === false ) jQuery("#"+zp_items.instance+" .zp-List").append( tempItems );
						
						
						// Append notes to container:
						if ( params.zpShowNotes == true && tempNotes.length > 0 )
						{
							tempNotes = "<div class='zp-Citation-Notes'>\n<h4>Notes</h4>\n<ol>\n" + tempNotes;
							tempNotes = tempNotes + "</ol>\n</div><!-- .zp-Citation-Notes -->\n\n";
							
							jQuery("#"+zp_items.instance).append( tempNotes );
						}
						
						
						// Fix incorrect numbering in existing numbered style
						if ( jQuery("#"+zp_items.instance+" .zp-List .csl-left-margin").length > 0 ) 
						{
							params.zpForceNumsCount = 1; // UNSURE: 0?
							
							jQuery("#"+zp_items.instance+" .zp-List .csl-left-margin").each(function ( index, item )
							{
								var item_content = jQuery(item).text();
								item_content = item_content.replace( item_content.match(/\d+/)[0], params.zpForceNumsCount );
								jQuery(item).text( item_content );
								
								params.zpForceNumsCount++;
							});
						}
						
						// Then, continue with other requests, if they exist
						if ( zp_items.meta.request_next != false && zp_items.meta.request_next != "false" )
						{
							zp_get_items ( zp_items.meta.request_next, zp_items.meta.request_last, $instance, params, update );
						}
						else
						{
							// Remove loading
							jQuery("#"+zp_items.instance+" .zp-List").removeClass("loading");
							
							// Check for updates
							if ( ! jQuery("#"+zp_items.instance+" .zp-List").hasClass("updating") )
							{
								zp_get_items ( 0, 0, $instance, params, true );
							}
							else // Re-sort if not numbered and sorting by author
							{
								if ( jQuery(".ZP_SORTBY", $instance).text() == "author"
										&& jQuery("#"+zp_items.instance+" .zp-List .csl-left-margin").length == 0 )
								{
									jQuery("#"+zp_items.instance+" .zp-List div.zp-Entry").sort(function(a,b)
									{
										// Sort based on Trent's: http://trentrichardson.com/2013/12/16/sort-dom-elements-jquery/
										var an = a.getAttribute("data-zp-author-year").toLowerCase(),
											  bn = b.getAttribute("data-zp-author-year").toLowerCase();
										
										if (an > bn)
											return 1;
										else if (an < bn)
											return -1;
										else
											return 0;
										
									}).detach().appendTo("#"+zp_items.instance+" .zp-List");
								}
							}
						}
					}
					
					// Message that there's no items
					else
					{
						if ( update === true )
						{
							jQuery("#"+$instance.attr("id")+" .zp-List").removeClass("loading");
							jQuery("#"+$instance.attr("id")+" .zp-List").append("<p>There are no citations to display.</p>\n");
						}
					}
				},
				error: function(errorThrown)
				{
					console.log(errorThrown);
				}
			});
			
		} // function zp_get_items
		
		jQuery(".zp-Zotpress-Bib").each( function( index, instance )
		{
			var $instance = jQuery(instance);
			var zp_params = new Object();
			
			zp_params.zpItemkey = false; if ( jQuery(".ZP_ITEM_KEY", $instance).text().trim().length > 0 ) zp_params.zpItemkey = jQuery(".ZP_ITEM_KEY", $instance).text();
			zp_params.zpCollectionId = false; if ( jQuery(".ZP_COLLECTION_ID", $instance).text().trim().length > 0 ) zp_params.zpCollectionId = jQuery(".ZP_COLLECTION_ID", $instance).text();
			zp_params.zpTagId = false; if ( jQuery(".ZP_TAG_ID", $instance).text().trim().length > 0 ) zp_params.zpTagId = jQuery(".ZP_TAG_ID", $instance).text();
			
			zp_params.zpAuthor = false; if ( jQuery(".ZP_AUTHOR", $instance).text().trim().length > 0 ) zp_params.zpAuthor = jQuery(".ZP_AUTHOR", $instance).text();
			zp_params.zpYear = false; if ( jQuery(".ZP_YEAR", $instance).text().trim().length > 0 ) zp_params.zpYear = jQuery(".ZP_YEAR", $instance).text();
			zp_params.zpStyle = false; if ( jQuery(".ZP_STYLE", $instance).text().trim().length > 0 ) zp_params.zpStyle = jQuery(".ZP_STYLE", $instance).text();
			zp_params.zpLimit = false; if ( jQuery(".ZP_LIMIT", $instance).text().trim().length > 0 ) zp_params.zpLimit = jQuery(".ZP_LIMIT", $instance).text();
			zp_params.zpTitle = false; if ( jQuery(".ZP_TITLE", $instance).text().trim().length > 0 ) zp_params. zpTitle = jQuery(".ZP_TITLE", $instance).text();
			
			zp_params.zpShowImages = false; if ( jQuery(".ZP_SHOWIMAGE", $instance).text().trim().length > 0 ) zp_params.zpShowImages = jQuery(".ZP_SHOWIMAGE", $instance).text().trim();
			zp_params.zpShowTags = false; if ( jQuery(".ZP_SHOWTAGS", $instance).text().trim().length > 0 ) zp_params.zpShowTags = true;
			zp_params.zpDownloadable = false; if ( jQuery(".ZP_DOWNLOADABLE", $instance).text().trim().length > 0 ) zp_params.zpDownloadable = true;
			zp_params.zpInclusive = false; if ( jQuery(".ZP_INCLUSIVE", $instance).text().trim().length > 0 ) zp_params.zpInclusive = true;
			zp_params.zpShowNotes = false; if ( jQuery(".ZP_NOTES", $instance).text().trim().length > 0 ) zp_params.zpShowNotes = true;
			zp_params.zpShowAbstracts = false; if ( jQuery(".ZP_ABSTRACT", $instance).text().trim().length > 0 ) zp_params.zpShowAbstracts = true;
			zp_params.zpCiteable = false; if ( jQuery(".ZP_CITEABLE", $instance).text().trim().length > 0 ) zp_params.zpCiteable = true;
			zp_params.zpTarget = false; if ( jQuery(".ZP_TARGET", $instance).text().trim().length > 0 ) zp_params.zpTarget = true;
			zp_params.zpURLWrap = false; if ( jQuery(".ZP_URLWRAP", $instance).text().trim().length > 0 ) zp_params.zpURLWrap = jQuery(".ZP_URLWRAP", $instance).text();
			zp_params.zpHighlight = false; if ( jQuery(".ZP_HIGHLIGHT", $instance).text().trim().length > 0 ) zp_params.zpHighlight = jQuery(".ZP_HIGHLIGHT", $instance).text();
			
			zp_params.zpForceNumsCount = 1;
			
			// Deal with multiples
			// Order of priority: collections, tags, authors, years
			// Filters (dealt with on shortcode.ajax.php): tags?, authors, years
			if ( zp_params.zpCollectionId && zp_params.zpCollectionId.indexOf(",") != -1 )
			{
				var tempCollections = zp_params.zpCollectionId.split(",");
				
				jQuery.each( tempCollections, function (i, collection)
				{
					zp_params.zpCollectionId = collection;
					zp_get_items ( 0, 0, $instance, zp_params, false ); // Get cached items first
					//zp_get_items ( 0, 0, $instance, zp_params, true );
				});
			}
			else
			{
				// Inclusive tags (treat exclusive normally)
				if ( zp_params.zpTagId && zp_params.zpInclusive == true && zp_params.zpTagId.indexOf(",") != -1 )
				{
					var tempTags = zp_params.zpTagId.split(",");
					
					jQuery.each( tempTags, function (i, tag)
					{
						zp_params.zpTagId = tag;
						zp_get_items ( 0, 0, $instance, zp_params, false ); // Get cached items first
						//zp_get_items ( 0, 0, $instance, zp_params, true );
					});
				}
				else
				{
					if ( zp_params.zpAuthor && zp_params.zpAuthor.indexOf(",") != -1 )
					{
						var tempAuthors = zp_params.zpAuthor.split(",");
						
						if ( zp_params.zpInclusive == true )
						{
							jQuery.each( tempAuthors, function (i, author)
							{
								zp_params.zpAuthor = author;
								zp_get_items ( 0, 0, $instance, zp_params, false );
								//zp_get_items ( 0, 0, $instance, zp_params, true );
							});
						}
						else // exclusive
						{
							zp_get_items ( 0, 0, $instance, zp_params, false );
							//zp_get_items ( 0, 0, $instance, zp_params, true );
						}
					}
					else
					{
						if ( zp_params.zpYear && zp_params.zpYear.indexOf(",") != -1 )
						{
							var tempYears = zp_params.zpYear.split(",");
							
							jQuery.each( tempYears, function (i, year)
							{
								zp_params.zpYear = year;
								zp_get_items ( 0, 0, $instance, zp_params, false );
								//zp_get_items ( 0, 0, $instance, zp_params, true );
							});
						}
						else // NORMAL, no multiples
						{
							zp_get_items ( 0, 0, $instance, zp_params, false );
							//zp_get_items ( 0, 0, $instance, zp_params, true );
						}
					}
				}
			}
		});
		
	} // Zotpress Bibliography
    
});