jQuery(document).ready(function()
{


    /****************************************************************************************
	*
	*     ZOTPRESS METABOX
	*
	****************************************************************************************/

    if ( jQuery("#zp-ZotpressMetaBox").length > 0 )
	{
		jQuery("#zp-ZotpressMetaBox").tabs(
		{
			activate: function( event, ui )
			{
				if ( ui.newPanel.attr('id') == "zp-ZotpressMetaBox-InText" )
					jQuery("#zp-ZotpressMetaBox-List").addClass("intext");
				else
					jQuery("#zp-ZotpressMetaBox-List").removeClass("intext");
			}
		});
	}



    /****************************************************************************************
	*
	*     ZOTPRESS BIBLIO CREATOR
	*
	****************************************************************************************/

    var zpBiblio = {
		"author": false, "year": false, "style": false, "sortby": false, "sort": false, "image": false,
		"download": false, "notes": false, "zpabstract": false, "cite": false, "title": false, "limit": false
		};
    var zpInText = {
		"format": false, "etal": false, "and": false, "separator": false, "style": false, "sortby": false,
		"sort": false, "image": false, "download": false, "notes": false, "zpabstract": false, "cite": false, "title": false
		};
	var zpRefItems = [];


    jQuery("input#zp-ZotpressMetaBox-Search-Input")
        .bind( "keydown", function( event ) {
            // Don't navigate away from the field on tab when selecting an item
            if ( event.keyCode === jQuery.ui.keyCode.TAB &&
                    jQuery( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
            // Don't submit the form when pressing enter
            if ( event.keyCode === 13 ) {
                event.preventDefault();
            }
        })
        .bind( "focus", function( event ) {
            // Set the account, in case it's changed
            jQuery(this).autocomplete( 'option', 'source', zpWidgetMetabox.ajaxurl + "?action=zpWidgetMetabox-submit&api_user_id=" + jQuery("#zp-ZotpressMetaBox-Acccount-Select").val() );

            // Remove help text on focus
            if (jQuery(this).val() == zpWidgetMetabox.txt_typetosearch) {
                jQuery(this).val("");
                jQuery(this).removeClass("help");
            }
            // Hide the shortcode, if shown
            jQuery("#zp-ZotpressMetaBox-Biblio-Generate-Inner").hide('fast');
			jQuery("#zp-ZotpressMetaBox-InText-Generate-Inner").hide('fast');
        })
        .bind( "blur", function( event ) {
            // Add help text on blur, if nothing there
            if (jQuery.trim(jQuery(this).val()) == "") {
                jQuery(this).val(zpWidgetMetabox.txt_typetosearch);
                jQuery(this).addClass("help");
            }
        })
        .autocomplete({
			source: zpWidgetMetabox.ajaxurl + "?action=zpWidgetMetabox-submit&api_user_id=" + jQuery("#zp-ZotpressMetaBox-Acccount-Select").val(),
            minLength: 3,
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
			create: function () {
				jQuery(this).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
					return jQuery( "<li data-api_user_id='"+item.api_user_id+"'>" )
						.append( "<a><strong>" + item.author + "</strong> " + item.label + "</a>" )
						.appendTo( ul );
				}
			},
            open: function () {
				var widget = jQuery(this).data('ui-autocomplete'),
						menu = widget.menu,
						$ul = menu.element;

				menu.element.addClass("zp-autocomplete");
                jQuery(".zp-autocomplete .ui-menu-item:first").addClass("first");

                // Change width of autocomplete dropdown based on input size
                if ( jQuery("#ZotpressMetaBox").parent().attr("id") == "normal-sortables" )
                    menu.element.addClass("zp-autocomplete-wide");
                else
                    menu.element.removeClass("zp-autocomplete-wide");
            },
            select: function( event, ui )
            {
                // Check if item is already in the list
                var check = false;
                jQuery.each(zpRefItems, function(index, item) {
                    if (item.itemkey == ui.item.value)
                        check = true;
                });

                if (check === false)
				{
                    console.log(ui.item);
                    // Add to list, if not already there
					zpRefItems.push({ "api_user_id": ui.item.api_user_id, "itemkey": ui.item.value, "pages": false});

                    // Add visual indicator
                    var uilabel = (ui.item.label).split(")",1) + ")";

                    var content = "<div class='item' rel='"+ui.item.value+"' data-api_user_id='"+ui.item.api_user_id+"'";
                    if ( ui.item.nickname )
                        content += " data-nickname='"+ui.item.nickname+"'";
                    content += ">";
                    content += "<span class='label'>"+ ui.item.author + ui.item.label +"</span>";
                    content += "<div class='options'>";
                    content += "<label for='zp-Item-"+ui.item.value+"'>"+zpWidgetMetabox.txt_pages+":</label><input id='zp-Item-"+ui.item.value+"' type='text'>";
                    content += "</div>";
                    content += "<div class='item_key'>&rsaquo; "+zpWidgetMetabox.txt_itemkey+": <strong>" + ui.item.value + "</strong></div>";
                    content += "<div class='account'";
                    if ( ui.item.nickname )
                        content += " data-nickname='"+ui.item.nickname+"'";
                    content += "data-api_user_id='"+ui.item.api_user_id+"'>&rsaquo; "+zpWidgetMetabox.txt_account+": <strong>";
                    if ( ui.item.nickname )
                        content += ui.item.nickname+" - ";
                    content += ui.item.api_user_id+"</strong></div>";
                    content += "<div class='delete'>&times;</div>";
                    content += "</div>\n";

                    jQuery("#zp-ZotpressMetaBox-List-Inner")
						.append(content);

                    // Remove text from input
                    jQuery("input#zp-ZotpressMetaBox-Search-Input").val("").focus();
                }
                return false;
            }
        });


    // HIDE SHORTCODE ON ITEM CHANGE
    jQuery("#zp-ZotpressMetaBox-List div.item")
        .livequery('click', function(event)
        {
            // Hide the shortcode, if shown
            jQuery("#zp-ZotpressMetaBox-Biblio-Generate-Inner").hide('fast');
        });



    // ITEM CLOSE BUTTON
    jQuery("#zp-ZotpressMetaBox-List div.item .delete")
        .livequery('click', function(event)
        {
            var $parent = jQuery(this).parent();

            // Make sure toggle is closed
            if (jQuery(".toggle", $parent).hasClass("active")) {
                jQuery(this).toggleClass("active");
                jQuery(".options", $parent).slideToggle('fast');
            }

            // Remove item from JSON
            jQuery.each(zpRefItems, function(index, item) {
                if (item.itemkey == $parent.attr("rel"))
                    zpRefItems.splice(index, 1);
            });

            // Remove visual indicator
            $parent.remove();

            // Hide the shortcode, if shown
            jQuery("#zp-ZotpressMetaBox-Biblio-Generate-Inner").hide('fast');
			jQuery("#zp-ZotpressMetaBox-InText-Generate-Inner").hide('fast');
        });


    // HIDE SHORTCODE ON BIBLIOGRAPHY OPTIONS PANEL CHANGE
    jQuery("#zp-ZotpressMetaBox-Biblio-Options")
        .click(function(event)
        {
            // Hide the shortcode, if shown
            jQuery("#zp-ZotpressMetaBox-Biblio-Generate-Inner").hide('fast');
        });


    // BIBLIOGRAPHY OPTIONS TOGGLE
    jQuery("#zp-ZotpressMetaBox-Biblio-Options h4 .toggle")
        .click(function(event)
        {
            jQuery(this).toggleClass("active");
            jQuery(".toggle-button", jQuery(this)).toggleClass("dashicons-arrow-down-alt2 dashicons-arrow-up-alt2");
            jQuery("#zp-ZotpressMetaBox-Biblio-Options-Inner").slideToggle('fast');
        });


    // GENERATE BIBLIOGRAPHY SHORTCODE BUTTON
    jQuery("#zp-ZotpressMetaBox-Biblio-Generate-Button")
        .click(function(event)
        {
            // Grab the author, year, style, sortby options
            zpBiblio.author = jQuery.trim(jQuery("#zp-ZotpressMetaBox-Biblio-Options-Author").val());
            zpBiblio.year = jQuery.trim(jQuery("#zp-ZotpressMetaBox-Biblio-Options-Year").val());
            zpBiblio.style = jQuery.trim(jQuery("#zp-ZotpressMetaBox-Biblio-Options-Style").val());
            zpBiblio.sortby = jQuery.trim(jQuery("#zp-ZotpressMetaBox-Biblio-Options-SortBy").val());
            zpBiblio.limit = jQuery.trim(jQuery("#zp-ZotpressMetaBox-Biblio-Options-Limit").val());

            // Grab the sort order option
            if (jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Sort-ASC").is(':checked') === true)
                zpBiblio.sort = "ASC";
            if (jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Sort-DESC").is(':checked') === true)
                zpBiblio.sort = "DESC";

            // Grab the image option
            if (jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Image-Yes").is(':checked') === true)
                zpBiblio.image = "yes";
            else
                zpBiblio.image = "";

            // Grab the title option
            if (jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Title-Yes").is(':checked') === true)
                zpBiblio.title = "yes";
            else
                zpBiblio.title = "";

            // Grab the download option
            if (jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Download-Yes").is(':checked') === true)
                zpBiblio.download = "yes";
            else
                zpBiblio.download = "";

            // Grab the abstract option
            if (jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Abstract-Yes").is(':checked') === true)
                zpBiblio.zpabstract = "yes";
            else
                zpBiblio.zpabstract = "";

            // Grab the notes option
            if (jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Notes-Yes").is(':checked') === true)
                zpBiblio.notes = "yes";
            else
                zpBiblio.notes = "";

            // Grab the cite option
            if (jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Cite-Yes").is(':checked') === true)
                zpBiblio.cite = "yes";
            else
                zpBiblio.cite = "";

            // Generate bibliography shortcode
            var zpBiblioShortcode = "[zotpress";

            // Determine if single account or multiple
            // var lastAccount = "";
            // var diffAccountsUsed = false;
            // jQuery.each( jQuery("#zp-ZotpressMetaBox-List .item"), function() {
            //     if ( lastAccount == "" ) lastAccount = jQuery(this).data("api_user_id");
            //     if ( lastAccount != jQuery(this).data("api_user_id") ) diffAccountsUsed = true;
            // });
            // if (jQuery("#zp-ZotpressMetaBox-Account").length > 0) zpBiblioShortcode += " userid=\"" + jQuery("#zp-ZotpressMetaBox-Account").attr("rel") + "\"";

			if ( zpRefItems.length > 0 )
			{
				var tempItems = "";
				jQuery.each(zpRefItems, function(index, item) {
					if ( index != "0") tempItems = tempItems + ","; // comma separator
					tempItems = tempItems + "{" + item.api_user_id + ":" + item.itemkey + "}";
				});
				zpBiblioShortcode += " items=\"" + tempItems + "\"";
			}

			if (zpBiblio.author != "") zpBiblioShortcode += " author=\"" + zpBiblio.author + "\"";
            if (zpBiblio.year != "") zpBiblioShortcode += " year=\"" + zpBiblio.year + "\"";
            if (zpBiblio.style != "") zpBiblioShortcode += " style=\"" + zpBiblio.style + "\"";
            if (zpBiblio.sortby != "" && zpBiblio.sortby != "default") zpBiblioShortcode += " sortby=\"" + zpBiblio.sortby + "\"";
            if (zpBiblio.sort != "") zpBiblioShortcode += " sort=\"" + zpBiblio.sort + "\"";
            if (zpBiblio.image != "") zpBiblioShortcode += " showimage=\"" + zpBiblio.image + "\"";
            if (zpBiblio.download != "") zpBiblioShortcode += " download=\"" + zpBiblio.download + "\"";
            if (zpBiblio.zpabstract != "") zpBiblioShortcode += " abstract=\"" + zpBiblio.zpabstract + "\"";
            if (zpBiblio.notes != "") zpBiblioShortcode += " notes=\"" + zpBiblio.notes + "\"";
            if (zpBiblio.cite != "") zpBiblioShortcode += " cite=\"" + zpBiblio.cite + "\"";
            if (zpBiblio.title != "") zpBiblioShortcode += " title=\"" + zpBiblio.title + "\"";
            if (zpBiblio.limit != "") zpBiblioShortcode += " limit=\"" + zpBiblio.limit + "\"";

            zpBiblioShortcode += "]";

            jQuery("#zp-ZotpressMetaBox-Biblio-Generate-Text").text(zpBiblioShortcode);

            // Reveal shortcode
            jQuery("#zp-ZotpressMetaBox-Biblio-Generate-Inner").slideDown('fast');
        });


    // CLEAR BIBLIOGRAPHY SHORTCODE BUTTON
    jQuery("#zp-ZotpressMetaBox-Biblio-Clear-Button")
        .click(function(event)
        {
            // Clear zpBiblio
            zpBiblio.author = false;
            zpBiblio.year = false;
            zpBiblio.style = false;
            zpBiblio.sortby = false;
            zpBiblio.sort = false;
            zpBiblio.image = false;
            zpBiblio.download = false;
            zpBiblio.notes = false;
            zpBiblio.zpabstract = false;
            zpBiblio.cite = false;
            zpBiblio.title = false;
            zpBiblio.limit = false;
            jQuery.each(zpRefItems, function(index, item) {
                zpRefItems.splice(index, 1);
            });

            // Hide options and shortcode
            jQuery("#zp-ZotpressMetaBox-Biblio-Options-Inner").slideUp('fast');
            jQuery("#zp-ZotpressMetaBox-Biblio-Options h4 .toggle").removeClass("active");
            jQuery("#zp-ZotpressMetaBox-Biblio-Generate-Inner").slideUp('fast');

            // Reset form inputs
            jQuery("#zp-ZotpressMetaBox-Biblio-Options-Author").val("");
            jQuery("#zp-ZotpressMetaBox-Biblio-Options-Year").val("");
            jQuery("#zp-ZotpressMetaBox-Biblio-Options-Limit").val("");

            jQuery("#zp-ZotpressMetaBox-Biblio-Options-Style option").removeAttr('checked');
            jQuery("#zp-ZotpressMetaBox-Biblio-Options-Style").val(jQuery("#zp-ZotpressMetaBox-Biblio-Options-Style option[rel='default']").val());

            jQuery("#zp-ZotpressMetaBox-Biblio-Options-SortBy option").removeAttr('checked');
            jQuery("#zp-ZotpressMetaBox-Biblio-Options-SortBy").val(jQuery("#zp-ZotpressMetaBox-Biblio-Options-SortBy option[rel='default']").val());

            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Sort-DESC").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Sort-ASC").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Image-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Image-No").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Title-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Title-No").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Download-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Download-No").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Abstract-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Abstract-No").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Notes-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Notes-No").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Cite-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-Biblio-Options-Cite-No").attr('checked', 'checked');

            // Remove visual indicators
            jQuery("div#zp-ZotpressMetaBox-List div.item").remove();
        });





    // HIDE SHORTCODE ON IN-TEXT OPTIONS PANEL CHANGE
    jQuery("#zp-ZotpressMetaBox-InText-Options")
        .click(function(event)
        {
            // Hide the shortcode, if shown
            jQuery("#zp-ZotpressMetaBox-InText-Generate-Inner").hide('fast');
        });


    // IN-TEXT OPTIONS TOGGLE
    jQuery("#zp-ZotpressMetaBox-InText-Options h4 .toggle")
        .click(function(event)
        {
            jQuery(this).toggleClass("active");
            jQuery("#zp-ZotpressMetaBox-InText-Options-Inner").slideToggle('fast');
        });


    // GENERATE IN-TEXT SHORTCODE BUTTON
    jQuery("#zp-ZotpressMetaBox-InText-Generate-Button")
        .click(function(event)
        {
            // Update page parameters for all citations
            jQuery("#zp-ZotpressMetaBox-List .item").each(function(vindex, vitem) {
				//alert(vitem);
                if (jQuery.trim(jQuery("input", vitem).val()).length > 0)
                {
                    jQuery.each(zpRefItems, function(index, item) {
						//alert(item);
                        if (item.itemkey == jQuery(vitem).attr("rel")) {
                            item.pages = jQuery.trim(jQuery("input", vitem).val());
                        }
                    });
                }
            });

            // Grab the format option
            zpInText.format = jQuery.trim(jQuery("#zp-ZotpressMetaBox-InText-Options-Format").val());

            // Grab the et al option
            zpInText.etal = jQuery.trim(jQuery("#zp-ZotpressMetaBox-InText-Options-Etal").val());

            // Grab the and option
            zpInText.and = jQuery.trim(jQuery("#zp-ZotpressMetaBox-InText-Options-And").val());

            // Grab the separator option
            zpInText.separator = jQuery.trim(jQuery("#zp-ZotpressMetaBox-InText-Options-Separator").val());

            // Grab the style option
            zpInText.style = jQuery.trim(jQuery("#zp-ZotpressMetaBox-InText-Options-Style").val());

            // Grab the sortby option
            zpInText.sortby = jQuery.trim(jQuery("#zp-ZotpressMetaBox-InText-Options-SortBy").val());

            // Grab the sort order option
            if (jQuery("input#zp-ZotpressMetaBox-InText-Options-Sort-ASC").is(':checked') === true)
                zpInText.sort = "ASC";
            if (jQuery("input#zp-ZotpressMetaBox-InText-Options-Sort-DESC").is(':checked') === true)
                zpInText.sort = "DESC";

            // Grab the image option
            if (jQuery("input#zp-ZotpressMetaBox-InText-Options-Image-Yes").is(':checked') === true)
                zpInText.image = "yes";
            else
                zpInText.image = "";

            // Grab the title option
            if (jQuery("input#zp-ZotpressMetaBox-InText-Options-Title-Yes").is(':checked') === true)
                zpInText.title = "yes";
            else
                zpInText.title = "";

            // Grab the download option
            if (jQuery("input#zp-ZotpressMetaBox-InText-Options-Download-Yes").is(':checked') === true)
                zpInText.download = "yes";
            else
                zpInText.download = "";

            // Grab the abstract option
            if (jQuery("input#zp-ZotpressMetaBox-InText-Options-Abstract-Yes").is(':checked') === true)
                zpInText.zpabstract = "yes";
            else
                zpInText.zpabstract = "";

            // Grab the notes option
            if (jQuery("input#zp-ZotpressMetaBox-InText-Options-Notes-Yes").is(':checked') === true)
                zpInText.notes = "yes";
            else
                zpInText.notes = "";

            // Grab the cite option
            if (jQuery("input#zp-ZotpressMetaBox-InText-Options-Cite-Yes").is(':checked') === true)
                zpInText.cite = "yes";
            else
                zpInText.cite = "";

            // Generate in-text shortcode
            var zpIntTextVal = "[zotpressInText item=\"";
            jQuery.each(zpRefItems, function(index, item) {
                zpIntTextVal += "{" + item.itemkey;
                if (item.pages !== false) zpIntTextVal += "," + item.pages;
                zpIntTextVal += "},";
            });
            zpIntTextVal = zpIntTextVal.substring(0, zpIntTextVal.length - 1) + "\""; // get rid of last comma

            if (jQuery("#zp-ZotpressMetaBox-Account").length > 0)
				zpIntTextVal += " userid=\"" + jQuery("#zp-ZotpressMetaBox-Account").attr("rel") + "\"";

            if (zpInText.format != "" && zpInText.format != "(%a%, %d%, %p%)")
                zpIntTextVal += " format=\"" + zpInText.format + "\"";

			if (zpInText.etal != "" && zpInText.etal != "default")
				zpIntTextVal += " etal=\"" + zpInText.etal + "\"";

			if (zpInText.and != "" && zpInText.and != "default")
				zpIntTextVal += " and=\"" + zpInText.and + "\"";

			if (zpInText.separator != "" && zpInText.separator != "default")
				zpIntTextVal += " separator=\"" + zpInText.separator + "\"";

            zpIntTextVal += "]";
            jQuery("#zp-ZotpressMetaBox-InText-InText").val(zpIntTextVal);

            // Generate in-text bibliography shortcode
            var zpInTextShortcode = "[zotpressInTextBib";

            if (zpInText.style != "") zpInTextShortcode += " style=\"" + zpInText.style + "\"";
            if (zpInText.sortby != "" && zpInText.sortby != "default") zpInTextShortcode += " sortby=\"" + zpInText.sortby + "\"";
            if (zpInText.sort != "") zpInTextShortcode += " sort=\"" + zpInText.sort + "\"";
            if (zpInText.image != "") zpInTextShortcode += " showimage=\"" + zpInText.image + "\"";
            if (zpInText.download != "") zpInTextShortcode += " download=\"" + zpInText.download + "\"";
            if (zpInText.zpabstract != "") zpInTextShortcode += " abstract=\"" + zpInText.zpabstract + "\"";
            if (zpInText.notes != "") zpInTextShortcode += " notes=\"" + zpInText.notes + "\"";
            if (zpInText.cite != "") zpInTextShortcode += " cite=\"" + zpInText.cite + "\"";
            if (zpInText.title != "") zpInTextShortcode += " title=\"" + zpInText.title + "\"";

            zpInTextShortcode += "]";

            jQuery("#zp-ZotpressMetaBox-InText-Text-Bib").val(zpInTextShortcode);

            // Reveal shortcode
            jQuery("#zp-ZotpressMetaBox-InText-Generate-Inner").slideDown('fast');

            //alert(JSON.stringify(zpInText));
        });


    // CLEAR IN-TEXTSHORTCODE BUTTON
    jQuery("#zp-ZotpressMetaBox-InText-Clear-Button")
        .click(function(event)
        {
            // Clear zpInText
            zpInText.format = false;
            zpInText.etal = false;
            zpInText.and = false;
            zpInText.separator = false;
            zpInText.style = false;
            zpInText.sortby = false;
            zpInText.sort = false;
            zpInText.image = false;
            zpInText.download = false;
            zpInText.zpabstract = false;
            zpInText.notes = false;
            zpInText.cite = false;
            zpInText.title = false;
            jQuery.each(zpRefItems, function(index, item) {
                zpRefItems.splice(index, 1);
            });

            // Hide options and shortcode
            jQuery("#zp-ZotpressMetaBox-InText-Options-Inner").slideUp('fast');
            jQuery("#zp-ZotpressMetaBox-InText-Options h4 .toggle").removeClass("active");
            jQuery("#zp-ZotpressMetaBox-InText-Generate-Inner").slideUp('fast');

            // Reset form inputs
            jQuery("#zp-ZotpressMetaBox-InText-Options-Format").val("(%a%, %d%, %p%)");
            jQuery("#zp-ZotpressMetaBox-InText-Options-Etal").val("default");
            jQuery("#zp-ZotpressMetaBox-InText-Options-And").val("default");
            jQuery("#zp-ZotpressMetaBox-InText-Options-Separator").val("default");

            jQuery("#zp-ZotpressMetaBox-InText-Options-Style option").removeAttr('checked');
            jQuery("#zp-ZotpressMetaBox-InText-Options-Style").val(jQuery("#zp-ZotpressMetaBox-InText-Options-Style option[rel='default']").val());

            jQuery("#zp-ZotpressMetaBox-InText-Options-SortBy option").removeAttr('checked');
            jQuery("#zp-ZotpressMetaBox-InText-Options-SortBy").val(jQuery("#zp-ZotpressMetaBox-InText-Options-SortBy option[rel='default']").val());

            jQuery("input#zp-ZotpressMetaBox-InText-Options-Sort-DESC").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-InText-Options-Sort-ASC").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-InText-Options-Image-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-InText-Options-Image-No").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-InText-Options-Title-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-InText-Options-Title-No").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-InText-Options-Download-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-InText-Options-Download-No").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-InText-Options-Abstract-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-InText-Options-Abstract-No").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-InText-Options-Notes-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-InText-Options-Notes-No").attr('checked', 'checked');

            jQuery("input#zp-ZotpressMetaBox-InText-Options-Cite-Yes").removeAttr('checked');
            jQuery("input#zp-ZotpressMetaBox-InText-Options-Cite-No").attr('checked', 'checked');

            // Remove visual indicators
            jQuery("div#zp-ZotpressMetaBox-List div.item").remove();
        });


});
