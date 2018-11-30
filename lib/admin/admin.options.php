<?php

// Restrict to Editors
if ( current_user_can('edit_others_posts') )
{
	
?>

		<div id="zp-Zotpress" class="wrap">
            
            <?php include( dirname(__FILE__) . '/admin.menu.php' ); ?>
			
			<div id="zp-Options-Wrapper">
				
				<h3>Options</h3>
				
				<?php include('admin.options.form.php'); ?>
				
				
				<hr />
				
				
				<!-- START OF CPT -->
				<div class="zp-Column-1">
					<div class="zp-Column-Inner">
						
						<h4>Set Reference Widget</h4>
						
						<p class="note">Enable or disable the Zotpress Reference widget for specific post types.</p>
						
						<div id="zp-Zotpress-Options-CPT" class="zp-Zotpress-Options">
							
							<div class="zp-CPT-Checkbox-Container"><?php
							
							// See if default exists
                            $zp_default_cpt = "post,page";
                            if (get_option("Zotpress_DefaultCPT"))
                                $zp_default_cpt = get_option("Zotpress_DefaultCPT");
							$zp_default_cpt = explode(",",$zp_default_cpt);
							
							$post_types = get_post_types( '', 'names' ); 
							
							foreach ( $post_types as $post_type )
							{
								echo "<div class='zp-CPT-Checkbox'>";
								echo "<input type=\"checkbox\" name=\"zp-CTP\" id=\"".$post_type."\" value=\"".$post_type."\" ";
								//if ( in_array( $post_type, $zp_default_cpt ) ) echo "disabled=\"disabled\" checked ";
								if ( in_array( $post_type, $zp_default_cpt ) ) echo "checked ";
								echo "/>";
								echo "<label ";
								//if ( in_array( $post_type, $zp_default_cpt ) )  echo "class=\"dis\" ";
								echo "for=\"".$post_type."\">".$post_type."</label>";
								echo "</div>\n";
							}
							
							?></div><!-- .zp-CPT-Checkbox-Container -->
							
							<input type="button" id="zp-Zotpress-Options-CPT-Button" class="button-secondary" value="Set Reference Widget" />
							<div class="zp-Loading">loading</div>
							<div class="zp-Success">Success!</div>
							<div class="zp-Errors">Errors!</div>
							
						</div>
					</div>
				</div><!-- END OF EDITOR -->
				
				
				
				<!-- START OF RESET -->
				<div class="zp-Column-1">
					<div class="zp-Column-Inner">
						
						<h4>Reset Zotpress</h4>
						
						<p class="note">Note: This action will clear all database entries associated with Zotpress, including account information and citations&#8212;it <strong>cannot be undone</strong>. Proceed with caution.</p>
						
						<div id="zp-Zotpress-Options-Reset" class="zp-Zotpress-Options">
							
							<input type="button" id="zp-Zotpress-Options-Reset-Button" class="button-secondary" value="Reset Zotpress" />
							<div class="zp-Loading">loading</div>
							<div class="zp-Success">Success!</div>
							<div class="zp-Errors">Errors!</div>
							
						</div>
					</div>
				</div><!-- END OF RESET -->
				
			</div><!-- zp-Browse-Wrapper -->
		
		</div>
	
<?php

} // !current_user_can('edit_others_posts')

else
{
	echo "<p>Sorry, you don't have permission to access this page.</p>";
}

?>