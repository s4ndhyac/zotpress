jQuery(document).ready(function()
{
	
    /****************************************************************************************
     *
     *     ZOTPRESS IN-TEXT
     *
     ****************************************************************************************/
	
	if ( jQuery(".zp-Zotpress-InTextBib").length > 0 )
	{
		// Create global array for citations per post
		window.zpIntextCitations = {};
		window.zpIntextCitationCount = 0;
		
		// Get list items
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
					'type': "intext",
					
					'item_key': params.zpItemkey,
					
					'style': params.zpStyle,
					'title': params.zpTitle,
					
					'showimage': params.zpShowImages,
					'showtags': params.zpShowTags,
					'downloadable': params.zpDownloadable,
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
					
					// First, display the items from this request, if any
					if ( typeof zp_items != 'undefined' && zp_items != null && parseInt(zp_items) != 0 && zp_items.data.length > 0 )
					{
						var tempItems = "";
						if ( params.zpShowNotes == true ) var tempNotes = "";
						if ( params.zpTitle == true ) var tempTitle = "";
						var $postRef = jQuery($instance).parent();
						
						
						
						// Indicate whether cache has been used
						if ( update === false )
						{
							jQuery("#"+zp_items.instance+" .zp-List").addClass("used_cache");
						}
						else if ( update === true )
						{
							// Remove existing notes temporarily
							if ( ! jQuery("#"+zp_items.instance+" .zp-List").hasClass("updating")
									&& jQuery("#"+zp_items.instance+" .zp-Citation-Notes").length > 0 )
								jQuery("#"+zp_items.instance+" .zp-Citation-Notes").remove();
							
							if ( ! jQuery("#"+zp_items.instance+" .zp-List").hasClass("updating") )
								jQuery("#"+zp_items.instance+" .zp-List").addClass("updating");
							
							//params.zpForceNumsCount = 1;
						}
						
						
						
						// Format in-text citations
						zp_format_intext_citations( $instance, params.zpItemkey, zp_items.data, params, update );
						
						// Format in-text bibliography
						tempItems = zp_format_intextbib ( $instance, zp_items, params.zpItemkey, params, update );
						
						
						
						// Append cached OR initial request items (first 50) to list
						if ( update === false ) jQuery("#"+zp_items.instance+" .zp-List").append( tempItems );
						
						
						// Append notes to container
						if ( params.zpShowNotes == true && tempNotes.length > 0 )
						{
							tempNotes = "<div class='zp-Citation-Notes'>\n<h4>Notes</h4>\n<ol>\n" + tempNotes;
							tempNotes = tempNotes + "</ol>\n</div><!-- .zp-Citation-Notes -->\n\n";
							
							jQuery("#"+zp_items.instance).append( tempNotes );
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
									
									//jQuery("#"+zp_items.instance+" .zp-List div.zp-Entry").sort(function(a,b) {
									//	return jQuery(a).getAttribute("zp-author-year") > jQuery(b).getAttribute("zp-author-year");
									//}).appendTo("#"+zp_items.instance+" .zp-List");
								}
							}
						}
					}
					
					// Message that there's no items
					else
					{
							var tempPost = $instance.attr("class");
							tempPost = tempPost.replace("zp-Zotpress zp-Zotpress-InTextBib zp-Post-", "");
							
							// Removes loading icon and in-text data; accounts for post-ID and non-standard themes
							if ( jQuery("#post-"+tempPost).length > 0 )
								jQuery("#post-"+tempPost+" .zp-InText-Citation").removeClass("loading").remove();
							else
								jQuery("#"+$instance.attr("id")).parent().find(".zp-InText-Citation").removeClass("loading").remove();
							
							jQuery("#"+$instance.attr("id")+" .zp-List").removeClass("loading");
							jQuery("#"+$instance.attr("id")+" .zp-List").append("<p>There are no citations to display.</p>\n");
					}
				},
				error: function(errorThrown)
				{
					console.log(errorThrown);
				}
			});
			
		} // function zp_get_items
		
		
		
		
		jQuery(".zp-Zotpress-InTextBib").each( function( index, instance )
		{
			var $instance = jQuery(instance);
			var zp_params = new Object();
			window.zpIntextCitations["post-"+jQuery(".ZP_POSTID", $instance).text()] = {};
			
			zp_params.zpItemkey = false; if ( jQuery(".ZP_ITEM_KEY", $instance).text().trim().length > 0 ) zp_params.zpItemkey = jQuery(".ZP_ITEM_KEY", $instance).text();
			
			zp_params.zpStyle = false; if ( jQuery(".ZP_STYLE", $instance).text().trim().length > 0 ) zp_params.zpStyle = jQuery(".ZP_STYLE", $instance).text();
			zp_params.zpTitle = false; if ( jQuery(".ZP_TITLE", $instance).text().trim().length > 0 ) zp_params. zpTitle = jQuery(".ZP_TITLE", $instance).text();
			
			zp_params.zpShowImages = false; if ( jQuery(".ZP_SHOWIMAGE", $instance).text().trim().length > 0 ) zp_params.zpShowImages = jQuery(".ZP_SHOWIMAGE", $instance).text().trim();
			zp_params.zpShowTags = false; if ( jQuery(".ZP_SHOWTAGS", $instance).text().trim().length > 0 ) zp_params.zpShowTags = true;
			zp_params.zpDownloadable = false; if ( jQuery(".ZP_DOWNLOADABLE", $instance).text().trim().length > 0 ) zp_params.zpDownloadable = true;
			zp_params.zpShowNotes = false; if ( jQuery(".ZP_NOTES", $instance).text().trim().length > 0 ) zp_params.zpShowNotes = true;
			zp_params.zpShowAbstracts = false; if ( jQuery(".ZP_ABSTRACT", $instance).text().trim().length > 0 ) zp_params.zpShowAbstracts = true;
			zp_params.zpCiteable = false; if ( jQuery(".ZP_CITEABLE", $instance).text().trim().length > 0 ) zp_params.zpCiteable = true;
			zp_params.zpTarget = false; if ( jQuery(".ZP_TARGET", $instance).text().trim().length > 0 ) zp_params.zpTarget = true;
			zp_params.zpURLWrap = false; if ( jQuery(".ZP_URLWRAP", $instance).text().trim().length > 0 ) zp_params.zpURLWrap = jQuery(".ZP_URLWRAP", $instance).text();
			zp_params.zpHighlight = false; if ( jQuery(".ZP_HIGHLIGHT", $instance).text().trim().length > 0 ) zp_params.zpHighlight = jQuery(".ZP_HIGHLIGHT", $instance).text();
			
			//zp_params.zpForceNumsCount = 1;
			
			zp_get_items ( 0, 0, $instance, zp_params, false ); // Get cached items first
		});
		
		
		
		
		function zp_format_intext_citations ( $instance, item_keys, item_data, params, update )
		{
			// Tested formats:
			// KEY
			// {KEY}
			// {KEY,3-9}
			// KEY,{KEY,8}
			var intext_citations = new Array();
			
			// Create array for multiple in-text citations -- semicolon
			if ( item_keys.indexOf(";") != -1 ) intext_citations = item_keys.split( ";" );
			else intext_citations.push( item_keys );
			
			
			// Re-structure item_data
			var tempItem_data = {};
			jQuery.each( item_data, function (index, value )
			{
				if ( ! tempItem_data.hasOwnProperty(value.key) )
					tempItem_data[value.key] = value;
			});
			item_data = tempItem_data;
			
			
			jQuery.each( intext_citations, function (index, intext_citation)
			{
				var $postRef = jQuery($instance).parent();
				
				var tempId = intext_citation.replace( /{/g, "-" ).replace( /}/g, "-" ).replace( /,/g, "_" ).replace( /\//g, "_" ).replace( /\+/g, "_" ).replace( /&/g, "_" ).replace( / /g, "_" );
				var intext_citation_id = "zp-InText-zp-ID-"+jQuery(".ZP_API_USER_ID", $instance).text()+"-"+tempId+"-"+jQuery(".ZP_POSTID", $instance).text()+"-"+(index+1);
				var intext_citation_params = JSON.parse( jQuery("#"+intext_citation_id, $postRef ).attr("rel").replace( /'/g, '"') );
				
				
				var intext_citation_output = "";
				
				// Create array from item keys
				if ( intext_citation.indexOf("{") != -1 ) // bracket
				{
					if ( intext_citation.indexOf("},") != -1 ) // multiple items
					{
						intext_citation = intext_citation.split( "}," );
						
						// Get rid of brackets, format pages
						jQuery.each ( intext_citation, function ( id, item )
						{
							// Check for pages
							if ( item.indexOf( "," ) != -1 )
							{
								item = item.split( "," );
								intext_citation[id] = { "key": item[0].replace( "}", "" ).replace( "{", "" ), "api_user_id": jQuery(".ZP_API_USER_ID", $instance).text(), "post_id": jQuery(".ZP_POSTID", $instance).text(), "pages": item[1].replace( "}", "" ), "bib": "", "citation_ids": "" };
							}
							else // No pages
							{
								intext_citation[id] = { "key": item.replace( "}", "" ).replace( "{", "" ), "api_user_id": jQuery(".ZP_API_USER_ID", $instance).text(), "post_id": jQuery(".ZP_POSTID", $instance).text(), "pages": false, "bib": "", "citation_ids": "" };
							}
						});
					}
					else // single bracket
					{
						if ( intext_citation.indexOf( "," ) != -1 ) // Pages
						{
							var item = intext_citation.split( "," );
							intext_citation = [{ "key": item[0].replace( "}", "" ).replace( "{", "" ), "api_user_id": jQuery(".ZP_API_USER_ID", $instance).text(), "post_id": jQuery(".ZP_POSTID", $instance).text(), "pages": item[1].replace( "}", "" ), "bib": "", "citation_ids": "" }];
						}
						else // no pages
						{
							intext_citation = [{ "key": intext_citation.replace( "}", "" ).replace( "{", "" ), "api_user_id": jQuery(".ZP_API_USER_ID", $instance).text(), "post_id": jQuery(".ZP_POSTID", $instance).text(), "pages": false, "bib": "", "citation_ids": "" }];
						}
					}
				}
				else // no bracket, no pages
				{
					intext_citation = [{ "key": intext_citation, "api_user_id": jQuery(".ZP_API_USER_ID", $instance).text(), "post_id": jQuery(".ZP_POSTID", $instance).text(), "pages": false, "bib": "", "citation_ids": "" }];
				}
				// Now we have an array in intext_citation
				// e.g.  [{ key="3NNACKP2",  pages=false,  citation=""}, { key="S74KCIJR",  pages=false,  citation=""}]
				
				
				// Go through each item in the citation; can be one or more items
				var group_authors = new Array();
				
				jQuery.each( intext_citation, function( cindex, item )
				{
					var item_citation = "";
					var item_authors = "";
					var item_year ="";
					
					// Add to global array, if not already there
					if ( ! window.zpIntextCitations["post-"+item.post_id].hasOwnProperty(item.key) )
					{
						window.zpIntextCitations["post-"+item.post_id][item.key] = item;
						
						window.zpIntextCitationCount++;
						window.zpIntextCitations["post-"+item.post_id][item.key]["num"] = window.zpIntextCitationCount;
					}
					//else // If already there, add to item keys -- does this make sense? Just repeats the html id ...
					//{
					//	window.zpIntextCitations["post-"+item.post_id][item.key]["citation_ids"] += intext_citation_id + " ";
					//}
					
					// Deal with authors and etal
					////jQuery.each( item_data, function ( kindex, response_item )
					////{
					//	if ( response_item.data.key != item.key ) return true;
					
					if ( item_data.hasOwnProperty(item.key) )
					{
						if ( item_data[item.key].data.hasOwnProperty("creators") )
						{
							var tempAuthorCount = 0;
							
							// Deal with authors
							jQuery.each ( item_data[item.key].data.creators, function ( ai, author )
							{
								//var tempEditorContentTypes = [ "bookSection", "encyclopediaArticle" ];
								
								if ( [ "bookSection", "encyclopediaArticle" ].indexOf( item_data[item.key].data.itemType ) !== false
										&& author.creatorType == "editor" )
									return true;
								
								tempAuthorCount++;
								
								if ( ai != 0 && tempAuthorCount > 1 ) item_authors += ", ";
								if ( author.hasOwnProperty("name") ) item_authors += author.name;
								else if ( author.hasOwnProperty("lastName") ) item_authors += author.lastName;
							});
							
							// Deal with duplicates in the group
							if ( group_authors.indexOf(item_authors) == -1 )
								group_authors[group_authors.length] = item_authors;
							else
								item_authors = "";
							
							// Create authors array (easier to deal with)
							item_authors = item_authors.split(", ");
							
							// Deal with et al for more than two authors
							//if ( item_authors.length > 2 )
							if ( jQuery.isArray(item_authors) && item_authors.length > 2 )
							{
								if ( intext_citation_params.etal == ""
										|| intext_citation_params.etal == "default" )
								{
									if ( update == false
											&& window.zpIntextCitations["post-"+item.post_id][item.key]["citation_ids"].length > 1 ) 
										item_authors = item_authors[0] + " <em>et al.</em>";
								}
								else if ( intext_citation_params.etal == "yes" )
								{
									item_authors = item_authors[0] + " <em>et al.</em>";
								}
							}
							
							// Deal with "and" for multiples that are not using "etal"
							if ( jQuery.isArray(item_authors) && item_authors.length > 1 )
							{
								if ( item_authors.indexOf("et al") == -1 )
								{
									var temp_and = ", ";
									
									if ( intext_citation_params.and == ""
											|| intext_citation_params.and == "and"
											|| intext_citation_params.and == "comma-and" )
									{
										if ( intext_citation_params.and == "" ) temp_and = " and ";
										else if ( intext_citation_params.and == "and" ) temp_and = " and ";
										else if ( intext_citation_params.and == "comma-and" ) temp_and = ", and ";
										
										var temp = item_authors.join().replace( /,/g, ", " );
										item_authors = temp.substring( 0, temp.lastIndexOf(", ") ) + temp_and +  item_authors[item_authors.length-1];
									}
								}
							}
						}
						else // Use title instead
						{
							item_authors += item_data[item.key].data.title;
						}
						
						// Get year or n.d.
						if ( item_data[item.key].meta.hasOwnProperty("parsedDate") ) 
							item_year = item_data[item.key].meta.parsedDate.substring(0, 4);
						else
							item_year = "n.d.";
						
						// Format anchor title attribute
						window.zpIntextCitations["post-"+item.post_id][item.key]["intexttitle"] = "title='"+JSON.stringify(item_authors).replace( "<em>et al.</em>", "et al." ).replace( /\"/g, "" ).replace( "[", "" ).replace( "]", "" ) + " (" + item_year + "). " + item_data[item.key].data.title + ".' ";
						//item_title_attr = JSON.stringify(item_authors).replace( "<em>et al.</em>", "et al." ).replace( /\"/g, "" ).replace( "[", "" ).replace( "]", "" ) + " (" + item_year + "). " + item_data[item.key].data.title + ".";
						
					} // if item_data.hasOwnProperty(item.key) 
					//}); // each request data item
					
					// Display with numbers
					if ( intext_citation_params.format.indexOf("%num%") != -1 )
					{
						//item_citation = Object.keys(window.zpIntextCitations["post-"+item.post_id]).indexOf( item.key) + 1;
						item_citation = window.zpIntextCitations["post-"+item.post_id][item.key]["num"];
						
						// If using parenthesis format:
						if ( intext_citation_params.format == "(%num%)" )
							item_citation = "("+item_citation+")";
					}
					
					// Display regularly, e.g. author and year and pages
					else
					{
						var default_format = intext_citation_params.format;
						
						// Add in author
						item_citation = intext_citation_params.format.replace( "%a%" , item_authors );
						
						// Add in year
						item_citation = item_citation.replace( "%d%" , item_year );
						
						// Deal with pages
						if ( item.pages == false )
						{
							item_citation = item_citation.replace( ", %p%" , "" );
							item_citation = item_citation.replace( "%p%" , "" );
						}
						else // pages exist
						{
							item_citation = item_citation.replace( "%p%" , item.pages );
						}
						
						// If more than one item in group, remove ), (
						if ( default_format == "(%a%, %d%, %p%)" && intext_citation.length > 1 )
						{
							if ( cindex != intext_citation.length - 1 )
								item_citation = item_citation.replace( ")", "" );
							
							if ( cindex != 0 )
								if ( item_authors == "" )
									item_citation = item_citation.replace( "(, ", "" );
								else
									item_citation = item_citation.replace( "(", "" );
						}
						
					} // non-numerical display
					
					// Add anchor title and anchors
					if ( ! window.zpIntextCitations["post-"+item.post_id][item.key].hasOwnProperty("intexttitle"))
						window.zpIntextCitations["post-"+item.post_id][item.key]["intexttitle"] = "";
					
					item_citation = "<a "+window.zpIntextCitations["post-"+item.post_id][item.key]["intexttitle"]+"class='zp-ZotpressInText' href='#zp-ID-"+item.post_id+"-"+item.api_user_id+"-"+item.key+"'>" + item_citation + "</a>";
					
					// Deal with <sup>
					if ( intext_citation_params.format.indexOf("sup") != "-1" ) item_citation = "<sup>"+item_citation+"</sup>";
					
					// Add to intext_citation array
					intext_citation[cindex]["intext"] = item_citation;
					
				}); // format each item
				
				
				
				// Format citation group
				var intext_citation_pre = ""; if ( intext_citation_params.brackets ) intext_citation_pre = "["; // &#91;
				var intext_citation_post = ""; if ( intext_citation_params.brackets ) intext_citation_post = "]"; // &#93;
				
				intext_citation_output = intext_citation_pre;
				
				jQuery.each( intext_citation, function(cindex, item)
				{
					// Determine separator
					if ( cindex != 0 )
					{
						if ( intext_citation_params.separator == "comma" )
							intext_citation_output += "; ";
						else
							intext_citation_output += ", ";
					}
					intext_citation_output += item.intext;
					
				}); // display each item
				
				intext_citation_output += intext_citation_post;
				
				// Add to placeholder
				jQuery("#"+intext_citation_id).removeClass("loading").html( intext_citation_output );
				
			}); // each intext_citation
			
		} // zp_format_intext_citations
		
		
		
		
		function zp_format_intextbib ( $instance, zp_items, zp_itemkeys, params, update )
		{
			var tempItemsArr = new Object; // Format: ["itemkey", "data"]
			var tempHasNum = false;
			var zpPostID = jQuery(".ZP_POSTID", $instance).text();
			
			jQuery.each( zp_items.data, function( index, item )
			{
				var tempItem = "";
				
				// Determine item reference
				var $item_ref = jQuery("#"+zp_items.instance+" .zp-List #zp-ID-"+jQuery(".ZP_API_USER_ID", $instance).text()+"-"+item.key);
				
				// Skip duplicates
				if ( jQuery("#"+zp_items.instance+" .zp-List #zp-ID-"+jQuery(".ZP_API_USER_ID", $instance).text()+"-"+item.key).length > 0 )
					return true;
				
				// Year
				var tempItemYear = "0000";
				if ( item.meta.hasOwnProperty('parsedDate') ) tempItemYear = item.meta.parsedDate.substring(0, 4);
				
				// Author
				var tempAuthor = item.data.title;
				if ( item.meta.hasOwnProperty('creatorSummary') ) tempAuthor = item.meta.creatorSummary.replace( / /g, "-" );
				
				// Title
				if ( params.zpTitle == true && tempTitle != tempItemYear )
				{
					tempTitle = tempItemYear;
					tempItem += "<h3>"+tempTitle+"</h3>\n";
				}
				
				tempItem += "<div id='zp-ID-"+jQuery(".ZP_POSTID", $instance).text()+"-"+jQuery(".ZP_API_USER_ID", $instance).text()+"-"+item.key+"'";
				tempItem += " data-zp-author-year='"+tempAuthor+"-"+tempItemYear+"' class='zp-Entry zpSearchResultsItem zp-Num-"+window.zpIntextCitations["post-"+zpPostID][item.key]["num"];
				
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
						//item.bib = item.bib.replace( '<div class="csl-entry">', '<div class="csl-entry"><div class="csl-left-margin" style="display: inline;">'+params.zpForceNumsCount+'. </div>' );
						item.bib = item.bib.replace( '<div class="csl-entry">', '<div class="csl-entry"><div class="csl-left-margin" style="display: inline;">'+window.zpIntextCitations["post-"+zpPostID][item.key]["num"]+'. </div>' );
						//params.zpForceNumsCount++;
					}
				}
				
				if ( /csl-left-margin/i.test(item.bib) ||
						( jQuery("#"+zp_items.instance+" .ZP_FORCENUM").text().length > 0
							&& jQuery("#"+zp_items.instance+" .ZP_FORCENUM").text() == "1" ) )
				{
					tempHasNum = true;
					
					var $item_content = jQuery.parseHTML(item.bib);
					var item_num_content = jQuery(".csl-left-margin", $item_content).text();
					item_num_content = item_num_content.replace( item_num_content.match(/\d+/)[0], window.zpIntextCitations["post-"+zpPostID][item.key]["num"] );
					
					jQuery(".csl-left-margin", $item_content).text(item_num_content);
					
					item.bib = jQuery('<div>').append( $item_content ).html(); 
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
					$item_ref.replaceWith( jQuery( tempItem ) );
				else
					tempItemsArr[item.key] = tempItem;
				
			}); // each item
			
			
			var tempItemsOrdered = "";
			
			// If in-text formatted as number (i.e. %num%), re-order
			if ( tempHasNum && update === false )
			{
				// If first request (first 50)
				if ( jQuery("#"+zp_items.instance+" .zp-List").children().length == 0 )
				{
					jQuery.each( window.zpIntextCitations["post-"+zpPostID],
						function ( index, value)
						{
							if ( typeof tempItemsArr[index] !== 'undefined' )
								tempItemsOrdered += tempItemsArr[index];
						}
					);
					
				}
				else // Subsequent requests for this bib
				{
					jQuery.each( tempItemsArr, function ( itemKey, itemBib)
					{
						// Get position number
						var tempNum = window.zpIntextCitations["post-"+zpPostID][itemKey]["num"];
						
						// Insert into proper place
						jQuery("#"+zp_items.instance+" .zp-List .zp-Entry.zp-Num-"+(tempNum-1)).after(itemBib);
					});
				}
			}
			else
			{
				jQuery.each( tempItemsArr, function ( index, value) { tempItemsOrdered += value; });
			}
			
			return tempItemsOrdered;
		
		} // function zp_format_intextbib
		
	} // Zotpress In-Text
	
	
    
    /*
     
        HIGHLIGHT ENTRY ON JUMP
        
    */
    
    jQuery(".zp-InText-Citation").on( "click", ".zp-ZotpressInText", function()
	{
		jQuery(jQuery(this).attr("href")).effect("highlight", { color: "#C5EFF7", easing: "easeInExpo" }, 1200);
	});


});