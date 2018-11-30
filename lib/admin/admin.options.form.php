<?php

// Restrict to Editors
if ( current_user_can('edit_others_posts') )
{
	
?>
<!-- START OF ACCOUNT -->
				<div class="zp-Column-1">
					<div class="zp-Column-Inner">
						
						<h4>Set Default Account</h4>
						
						<p class="note">Note: Only applicable if you have multiple synced Zotero accounts.</p>
						
						<div id="zp-Zotpress-Options-Account" class="zp-Zotpress-Options">
							
							<label for="zp-Zotpress-Options-Account">Choose Account:</label>
							<select id="zp-Zotpress-Options-Account">
								<?php
								
								global $wpdb;
								$zp_accounts = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."zotpress ORDER BY account_type DESC");
								$zp_accounts_total = $wpdb->num_rows;
								
								// See if default exists
								$zp_default_account = "";
								if (get_option("Zotpress_DefaultAccount"))
									$zp_default_account = get_option("Zotpress_DefaultAccount");
								
								foreach ($zp_accounts as $zp_account)
									if ($zp_account->api_user_id == $zp_default_account)
										echo "<option id=\"".$zp_account->api_user_id."\" value=\"".$zp_account->api_user_id."\" selected='selected'>".$zp_account->api_user_id." (".$zp_account->nickname.") [".substr($zp_account->account_type, 0, strlen($zp_account->account_type)-1)."]</option>\n";
									else
										echo "<option id=\"".$zp_account->api_user_id."\" value=\"".$zp_account->api_user_id."\">".$zp_account->api_user_id." (".$zp_account->nickname.") [".substr($zp_account->account_type, 0, strlen($zp_account->account_type)-1)."]</option>\n";
								
								?>
							</select>
							
							<input type="button" id="zp-Zotpress-Options-Account-Button" class="zp-Accounts-Default button-secondary" value="Set Default Account" />
							<div class="zp-Loading">loading</div>
							<div class="zp-Success">Success!</div>
							<div class="zp-Errors">Errors!</div>
							
							<h4 class="clear" />
							
						</div>
						<!-- END OF ACCOUNT -->
						
					</div>
				</div>
				
				<div class="zp-Column-2">
					<div class="zp-Column-Inner">
						
						<!-- START OF STYLE -->
						<h4>Set Default Citation Style for Importing</h4>
						
						<p class="note">Note: Styles must be listed <a title="Zotero Styles" href="http://www.zotero.org/styles">here</a>. Use the name found in the style's URL, e.g. modern-language-association.</p>
						
						<div id="zp-Zotpress-Options-Style-Container" class="zp-Zotpress-Options">
							
							<label for="zp-Zotpress-Options-Style">Choose Style:</label>
							<select id="zp-Zotpress-Options-Style">
								<?php
								
								if (!get_option("Zotpress_StyleList"))
									add_option( "Zotpress_StyleList", "apa, apsa, asa, chicago-author-date, chicago-fullnote-bibliography, harvard1, modern-language-association, nature, vancouver");
								
								$zp_styles = explode(", ", get_option("Zotpress_StyleList"));
								sort($zp_styles);
								
								// See if default exists
								$zp_default_style = "apa";
								if (get_option("Zotpress_DefaultStyle"))
									$zp_default_style = get_option("Zotpress_DefaultStyle");
								
								foreach($zp_styles as $zp_style)
									if ($zp_style == $zp_default_style)
										echo "<option id=\"".$zp_style."\" value=\"".$zp_style."\" selected='selected'>".$zp_style."</option>\n";
									else
										echo "<option id=\"".$zp_style."\" value=\"".$zp_style."\">".$zp_style."</option>\n";
								
								?>
								<option id="new" value="new-style">Add another style ...</option>
							</select>
							
							<div id="zp-Zotpress-Options-Style-New-Container">
								<label for="zp-Zotpress-Options-Style-New">Add Style:</label>
								<input id="zp-Zotpress-Options-Style-New" type="text" />
							</div>
							
							<input type="button" id="zp-Zotpress-Options-Style-Button" class="button-secondary" value="Set Default Style" />
							<div class="zp-Loading">loading</div>
							<div class="zp-Success">Success!</div>
							<div class="zp-Errors">Errors!</div>
							
							<hr class="clear" />
							
						</div>
						<!-- END OF STYLE -->
						
					</div>
				</div>
<?php

} // !current_user_can('edit_others_posts')

else
{
	echo "<p>Sorry, you don't have permission to access this page.</p>";
}

?>