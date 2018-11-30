<?php if (!isset( $_GET['setupstep'] )) { ?>

    <div id="zp-Setup">
        
        <div id="zp-Zotpress-Navigation">
        
            <div id="zp-Icon" title="Zotero + WordPress = Zotpress"><br /></div>
            
            <div class="nav">
                <div id="step-1" class="nav-item nav-tab-active"><strong>1.</strong> Validate Account</div>
                <div id="step-2" class="nav-item"><strong>2.</strong> Default Options</div>
            </div>
        
        </div><!-- #zp-Zotpress-Navigation -->
        
        <div id="zp-Setup-Step">
            
            <?php
            
            $zp_check_curl = intval( function_exists('curl_version') );
            $zp_check_streams = intval( function_exists('stream_get_contents') );
            $zp_check_fsock = intval( function_exists('fsockopen') );
            
            if ( ($zp_check_curl + $zp_check_streams + $zp_check_fsock) <= 1 ) { ?>
            <div id="zp-Setup-Check" class="error">
                <p><strong>Warning.</strong> Zotpress requires at least one of the following to work: cURL, fopen with Streams (PHP 5), or fsockopen. You will not be able to use Zotpress until your administrator or tech support has set up one of these options. cURL is recommended.</p>
            </div>
            <?php } ?>
            
            <div id="zp-AddAccount-Form" class="visible">
                <?php include('admin.accounts.addform.php'); ?>
            </div>
            
        </div>
        
    </div>
    
    
    
<?php } else if (isset($_GET['setupstep']) && $_GET['setupstep'] == "two") { ?>

    <div id="zp-Setup">
        
        <div id="zp-Zotpress-Navigation">
        
            <div id="zp-Icon" title="Zotero + WordPress = Zotpress"><br /></div>
            
            <div class="nav">
                <div id="step-1" class="nav-item"><strong>1.</strong> Validate Account</div>
                <div id="step-2" class="nav-item nav-tab-active"><strong>2.</strong> Default Options</div>
            </div>
        
        </div><!-- #zp-Zotpress-Navigation -->
        
        <div id="zp-Setup-Step">
            
            <h3>Set Default Options</h3>
            
            <?php include("admin.options.form.php"); ?>
            
            <div id="zp-Zotpress-Setup-Buttons" class="proceed">
                <input type="button" id="zp-Zotpress-Setup-Options-Complete" class="button-primary" value="Finish" />
            </div>
            
        </div>
        
    </div>
    
<?php } ?>