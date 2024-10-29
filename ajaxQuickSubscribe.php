<?php
/*
Plugin Name: AJAX Quick Subscribe
Plugin URI: http://noveld.com/wp-plugins/quick-subscribe
Description: Allows visitor to quickly register to your blog using only an email address: based on 'Quick Subscribe' by Leo Germani, with added AJAX functionality for smoother, more modern operation.
Author: Dylan Wood
Version: 2.0
Author URI: http://noveld.com

    AJAX Quick Subscribe is released under the GNU General Public License (GPL)
    http://www.gnu.org/licenses/gpl.txt

    This is a WordPress plugin (http://wordpress.org) 
*/


   // embed the javascript file that makes the AJAX request
   wp_enqueue_script( 'my-ajax-request', plugin_dir_url( __FILE__ ) . 'ajax.js', array( 'jquery' ) );
    
    // declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
    wp_localize_script( 'my-ajax-request', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

//Load default options: these will be overwritten when the user customizes their widget.
//Keep in mind that there are several means of implementing the quick-subscribe toolkit in your WP pages:
// 1 - add the Ajax Quick Subscribe Widget to a sidebar via the Appearance->widgets toolbar. The user may specify some options specifically for this implementation of the tool in the same menu. 
// 2 - add the tool to any text by including the tag '[quicksubscribe]' within any text in a post or page.
// 3 - add the code '<?php quick_subscribe_form \?\> anywhere in your theme php code(minus the backslashes)
// Options 2 and three utilize the settings which are defined via the settings->quick subscribe tool in the admin toolset.
//Some options (like thanks_message, invalid_message, and already_subscribed message are only definable via the settings->quick subscribe menu. 

//Add default options for the widget implementation of the tool.
if(get_option("quicksubscribe_widget_button") == null){
	update_option("quicksubscribe_widget_button", true);
}
if(get_option("quicksubscribe_widget_button_hide") == null){
   update_option("quicksubscribe_widget_button_hide", false);
}
//if(get_option("quicksubscribe_widget_input_redisplay") == null){
//   update_option("quicksubscribe_widget_input_redisplay", false);
//}
if(!get_option("quicksubscribe_widget_button_label")){
	update_option("quicksubscribe_widget_button_label", "subscribe!");
}
if(!get_option("quicksubscribe_widget_title")){
	update_option("quicksubscribe_widget_title", 'Subscribe');
}
if(!get_option("quicksubscribe_widget_description")){
	update_option("quicksubscribe_widget_description", "Enter your email to subscribe to future updates");
}

   //Add default options for the in-text and in-theme implementations
   //it is assumed that the user will add thier own desctiption and title for these implementations

if(get_option("quicksubscribe_button") == null){
	update_option("quicksubscribe_button", true);
}
if(get_option("quicksubscribe_button_hide") == null){
	update_option("quicksubscribe_button_hide", true);
}
//if(get_option("quicksubscribe_input_redisplay") == null){
//	update_option("quicksubscribe_input_redisplay", true);
//}
if(!get_option("quicksubscribe_button_label")){
	update_option("quicksubscribe_button_label", "subscribe!");
}
 // now addany options which apply to both the widget and in-text, in-theme implementations
if(!get_option("quicksubscribe_tks")){
	update_option("quicksubscribe_tks", "Thanks for subscribing. Now add a friend:");
}
if(!get_option("quicksubscribe_invld")){
	update_option("quicksubscribe_invld", "Invalid email address");
}

if(!get_option("quicksubscribe_alrdysub")){
	update_option("quicksubscribe_alrdysub", "This email is already subscribed");
}

/*+-----------------------------------------------------------------+
  | Now define functions
  +-----------------------------------------------------------------+
*/
// This function actually does the subscribing 
function quick_subscribe_register($source){

	require_once( ABSPATH . WPINC . '/registration.php');

	
	$user_email = apply_filters( 'user_registration_email', $source );
	$user_login = sanitize_user( str_replace('@','', $source) );

	// Check the e-mail address
	if ($user_email == '') {
		$errors = get_option('quicksubscribe_invld');
	} elseif ( !is_email( $user_email ) ) {
		$errors = get_option('quicksubscribe_invld');
	} elseif ( email_exists( $user_email ) )
		$errors = get_option('quicksubscribe_alrdysub');

	//do_action('register_post');

   if(isset($errors)){
      $errors = apply_filters( 'registration_errors', $errors );
      $message = $errors;
   }

	if ( empty( $errors ) ) {
		$user_pass = substr( md5( uniqid( microtime() ) ), 0, 7);

		$user_id = wp_create_user( $user_login, $user_pass, $user_email );
		
		$user = new WP_User($user_id);
		$user->set_role('subscriber');
		
		
		$message = get_option('quicksubscribe_tks');
		
		
		if ( !$user_id )
			$errors['registerfail'] = sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !'), get_option('admin_email'));
		
	}
	return $message;
}

// This function adds functions for the quick subscribe widget
function widget_quick_subscribe_init(){
	
	
	// Check to see required Widget API functions are defined...
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
        return; // ...and if not, exit gracefully from the script.
			
      //This function prints the general quick_subscribe widget
		function widget_quick_subscribe($args){
			
			extract($args);
         $message = '';
			
			$title = get_option('quicksubscribe_widget_title');
			
			echo $before_widget;
   
         if($title != ''){
            echo $before_title . $title . $after_title;
         }
//add widget description.
         echo get_option('quicksubscribe_widget_description');

         echo '<div id="QSWidgetDiv" class="QSWidgetDiv">';

         echo quick_subscribe_get_form($message, 'QS_user_email_widget', 'QSWidgetDiv');
         echo '</div>';
			?>
			
			<?
			echo $after_widget;
      }//end function widget_quick_subscribe($args)
		
      //I think this function creates the widget-customization form. 
		function widget_quick_subscribe_control() {
		
         $submitName = 'qs_SubmitName';

			// This is for handing the control form submission.
			if ( isset($_POST[$submitName] )) {
				// Clean up control form submission options
            $newButton = isset($_POST['quicksubscribe_widget_button']) ? $_POST['quicksubscribe_widget_button'] : null;
            //since button is a checkbox, we'll need to make sure to set it properly.
            if($newButton != 1) $newButton = 0;
				$newTitle = isset($_POST['quicksubscribe_widget_title'])?$_POST['quicksubscribe_widget_title']: null;
				$newDescription = isset($_POST['quicksubscribe_widget_description'])?$_POST['quicksubscribe_widget_description']:null;
				$newButtonLabel = isset($_POST['quicksubscribe_widget_button_label'])?$_POST['quicksubscribe_widget_button_label']:null;
				$newButtonHide =  isset($_POST['quicksubscribe_widget_button_hide'])?$_POST['quicksubscribe_widget_button_hide']:null;
            //since button is a checkbox, we'll need to make sure to set it properly.
            if($newButtonHide != 1) $newButtonHide = 0;
				//$newInputRedisplay =  isset($_POST['quicksubscribe_widget_input_redisplay'])?$_POST['quicksubscribe_widget_input_redisplay']:null;
            //since button is a checkbox, we'll need to make sure to set it properly.
            //if($newInputRedisplay != 1) $newInputRedisplay = 0;
				
            update_option('quicksubscribe_widget_title', $newTitle);
            update_option('quicksubscribe_widget_description', $newDescription);
            update_option('quicksubscribe_widget_button_label', $newButtonLabel);
            update_option('quicksubscribe_widget_button', $newButton);
            update_option('quicksubscribe_widget_button_hide', $newButtonHide);
           // update_option('quicksubscribe_widget_input_redisplay', $newInputRedisplay);
			}

         $title = get_option('quicksubscribe_widget_title');
         $description = get_option('quicksubscribe_widget_description');
         $button = get_option('quicksubscribe_widget_button');
         $button_label = get_option('quicksubscribe_widget_button_label');
         $button_hide = get_option('quicksubscribe_widget_button_hide');
         //$input_redisplay = get_option('quicksubscribe_widget_input_redisplay');
			
			
			// The HTML below is the control form for editing options.
			?>
			<div>
			<label for="quick_subscribe_widget_title" style="line-height:35px;display:block;">
			Title: (this gets diplayed first) <input type="text" id="quicksubscribe_widget_title" name="quicksubscribe_widget_title" value="<?php echo $title; ?>" /></label>
			
			<label for="quick_subscribe_widget_description" style="line-height:35px;display:block;">
			Description: (this gets displayed next) <input type="text" id="quicksubscribe_widget_description" name="quicksubscribe_widget_description" value="<?php echo $description; ?>" /></label>
			
			<label for="quicksubscribe_widget_button" style="line-height:35px;display:block;">
			<input type="checkbox" id="quicksubscribe_widget_button" name="quicksubscribe_widget_button" value="1" <?php if ($button==1) echo "checked"; ?> /> Display submit button after text box</label>
			<label for="quicksubscribe_widget_button_hide" style="line-height:35px;display:block;">
			<input type="checkbox" id="quicksubscribe_widget_button_hide" name="quicksubscribe_widget_button_hide" value="1" <?php if ($button_hide==1) echo "checked"; ?> /> Hide submit button until email is entered</label>
			
			<?/*<label for="quicksubscribe_widget_input_redisplay" style="line-height:35px;display:block;">
			<input type="checkbox" id="quicksubscribe_widget_input_redisplay" name="quicksubscribe_widget_input_redisplay" value="1" <?php if ($input_redisplay==1) echo "checked"; ?> /> Still show email input after successful subscribe</label>
			*/?>
         <label for="quicksubscribe_widget_button_label" style="line-height:35px;display:block;">
			Submit button label: <input type="text" id="quicksubscribe_widget_button_label" name="quicksubscribe_widget_button_label" value="<?php echo $button_label; ?>" /></label>
			
			<input type="hidden" name="<? echo $submitName ?>" id="<? echo $submitName ?>" value="1" />
			</div>
			<?php
		}// end function widget_quick_subscribe_control() 

	//PHP to register the widget and control forms.

	    register_sidebar_widget('AJAX Quick Subscribe ', 'widget_quick_subscribe');
	    register_widget_control('AJAX Quick Subscribe ', 'widget_quick_subscribe_control');
	    
}// end function widget_quick_subscribe_init()

//this function adds the admin options to the Settings->Quick Subscribe page
function quicksubscribe_admin() {
	if (function_exists('add_options_page')) {
		add_options_page('AJAX Quick Subscribe Options', 'AJAX Quick Subscribe', 8, basename(__FILE__), 'quicksubscribe_admin_page');
	}
}

//this function does the lifting for the function above
function quicksubscribe_admin_page() {
	

	if (isset($_POST['submit_qs'])) {

		echo "<div class=\"updated\"><p><strong> AJAX Quick Subscribe Options Updated!";
		$button = isset($_POST['button_qs']);
      if($button != 1) $button = 0;
		$button_hide = isset($_POST['button_hide_qs']);
      if($button_hide != 1) $button_hide = 0;
		//$input_redisplay = isset($_POST['input_redisplay_qs']);
      //if($input_redisplay != 1) $input_redisplay = 0;
		
		update_option("quicksubscribe_button", $button);
		update_option("quicksubscribe_label", $_POST['label_qs']);
		update_option("quicksubscribe_tks", $_POST['tks_qs']);
		update_option("quicksubscribe_invld", $_POST['invld_qs']);
		update_option("quicksubscribe_alrdysub", $_POST['alrdysub_qs']);
      update_option("quicksubscribe_button_hide", $button_hide);
      //update_option("quicksubscribe_input_redisplay", $input_redisplay);
		
		echo "</strong></p></div>";
	}
	
	$op_button = (get_option("quicksubscribe_button"));
	$op_label = get_option("quicksubscribe_label");
	$op_tks = get_option("quicksubscribe_tks");
	$op_alrdysub = get_option("quicksubscribe_alrdysub");
	$op_invld = get_option("quicksubscribe_invld");
   $op_hide_button = get_option("quicksubscribe_button_hide");
   //$op_input_redisplay = get_option("quicksubscribe_input_redisplay");
	?>

	<div class=wrap>
	  <form name="qsoptions" method="post">
	    <h2>AJAX Quick Subscribe Page/Post form options</h2>

		
			<input type="checkbox" name="button_qs" value="1" <?php if($op_button) echo " checked ";?>> Display submit button after text box (otherwise user must press enter to subscribe)<BR /><BR />
			<input type="checkbox" name="button_hide_qs" value="1" <?php if($op_hide_button) echo " checked ";?>> Hide submit button until user enters text box <BR /><BR />
			<?/*<input type="checkbox" name="input_redisplay_qs" value="1" <?php if($op_input_redisplay) echo " checked ";?>> Display email input after successful subscribe for additional subscriptions<BR /><BR />*/?>
			Submit button label:<BR />
			<input type="text" name="label_qs" value="<?= $op_label ?>"> <BR /><BR />
			Thanks Message:<BR />
			<input type="text" name="tks_qs" value="<?= $op_tks ?>"> <BR /><BR />
		   Already Subscribed Message:<BR />
			<input type="text" name="alrdysub_qs" value="<?= $op_alrdysub ?>"> <BR /><BR />
			Invalid Email Message:<BR />
			<input type="text" name="invld_qs" value="<?= $op_invld ?>"> <BR /><BR />
	
	<div class="submit">
	<input type="submit" name="submit_qs" value="<?php _e('Update Settings', '') ?> &raquo;">
	
	  </form>
	</div>

	<?php

}


//this function generates the form for all implentations of quick_subscribe

function quick_subscribe_get_form($message, $source, $containerDiv, $userEmail = ''){

	$caixa = 'email@email.com'; 
   //if the implementation is in a widget, get widget-specific options
   if(strpos($source, 'widget')){
      $op_button = get_option("quicksubscribe_widget_button"); 
      $op_hide = get_option("quicksubscribe_widget_button_hide");
      $op_label = get_option("quicksubscribe_widget_button_label");
      $input_prefix = 'widget';
   }
   //otherwise, get general options
   else{
      $op_button = get_option("quicksubscribe_button");
      $op_hide = get_option("quicksubscribe_button_hide");
      $op_label = get_option("quicksubscribe_label");
      $input_prefix = 'tt';
   }
   //initialize output
   $output = '';
   //add message
   if($message){
      $output .= "<div id='".$input_prefix."_quick_subscribe_messages'>". $message ."</div>";
   }
	
	$output .= "<form name='".$input_prefix."_quick_subscribe_form' id='".$input_prefix."_quick_subscribe_form'>";
	$output .= "<input type='email' name='". $source ."' id='". $source ."' placeholder='".$caixa."'";
   if ($op_hide) $output .= " onFocus='fadeSubmitIn(\"".$input_prefix."_qsSubmit\");' onBlur='fadeSubmitOut(this, \"".$input_prefix."_qsSubmit\");' ";
   $output .= " onkeypress='return checkForEnter(event, \"$source\", \"$containerDiv\"); return false;'";
   if($userEmail != '') $output .= " value='$userEmail' ";
   $output .= ">";
	if ($op_button & $op_hide){
   //display button, but make it hidden
      $output .= "<input style='display:none;' type='button' value='$op_label' id='".$input_prefix."_qsSubmit' class='".$input_prefix."_qsSubmit' onClick='submitQuickSubscribe(\"$source\", \"$containerDiv\");'>";
   }
   else if($op_button){
      //display button
      $output .= "<input type='button' value='$op_label' id='".$inputPrefix."_qsSubmit' class='".$input_prefix."_qsSubmit' onClick='submitQuickSubscribe(\"$source\", \"$containerDiv\");'>";
   }
	$output .= "</form>";

	return $output;
}


//this function implements the form if it is included in a tag
function quick_subscribe_tag_form($content){
	
   if ( strpos($content, '[quicksubscribe]')){
      
      $output = '<div style="display:inline" "id=qsInlineContainer" class="qsInlineContainer">';
      $output .= quick_subscribe_get_form(0, "QS_user_email_tag", 'qsInlineContainer');
      $output .= '</div>';
      
      $content = str_replace("[quicksubscribe]", $output, $content);
   }
   return $content;
}

//this function implents the form for the first time when added via tag or theme
function quick_subscribe_form(){

   echo '<div style="display:inline" id="qsInlineContainer" class="qsInlineContainer">';
	echo quick_subscribe_get_form(0, "QS_user_email_theme", 'qsInlineContainer');
	echo '</div>';
}

//this function handles the ajax request
function handle_qs_ajax_request(){
   $userEmail = $_REQUEST['userEmail'];
   $source = $_REQUEST['source'];
   $containerDiv = $_REQUEST['containerDiv'];

   $message = quick_subscribe_register($userEmail);
   $output = quick_subscribe_get_form($message, $source, $containerDiv, $userEmail);
   echo $output;
   die();
}

// Delays plugin execution until Dynamic Sidebar has loaded first.
add_action('plugins_loaded', 'widget_quick_subscribe_init');
add_filter('the_content', 'quick_subscribe_tag_form');
add_action('admin_menu','quicksubscribe_admin');
add_action('wp_ajax_nopriv_quicksubscribe_submit', 'handle_qs_ajax_request');
add_action('wp_ajax_quicksubscribe_submit', 'handle_qs_ajax_request');
