<?php
/*
Plugin Name: Postmark
Plugin URI: http://www.andydev.co.uk
Description: Overwrites wp_mail to send emails through Postmark
Author: Andrew Yates
Version: 1.0.0
Author URI: http://www.andydev.co.uk
Created: 2011-07-05
Modified: 2011-07-05
*/

// Define
define('POSTMARK_ENDPOINT', 'http://api.postmarkapp.com/email');



// Admin Functionality
add_action('admin_menu', 'pm_admin_menu'); // Add Postmark to Settings 

function pm_admin_menu() {
	add_options_page('Postmark', 'Postmark', 'manage_options', 'pm_admin', 'pm_admin_options');
}

function pm_admin_options() {
	if($_POST['submit']) {
		$pm_enabled = $_POST['pm_enabled'];
		if($pm_enabled):
			$pm_enabled = 1;
		else:
			$pm_enabled = 0;
		endif;
		
		$api_key = $_POST['pm_api_key'];
		$sender_email = $_POST['pm_sender_address'];
		
		update_option('postmark_enabled', $pm_enabled);
		update_option('postmark_api_key', $api_key);
		update_option('postmark_sender_address', $sender_email);
		
		$msg_updated = "Postmark settings have been saved.";
	}
	?>
	
	<script type="text/javascript" >
	jQuery(document).ready(function($) {
		
		$(".pm_admin_test").click(function(e){
			e.preventDefault();
			var $this = $(this).text("Loading...");
			$.post(ajaxurl, {action:$this.attr("rel")}, function(data){
				$this.text(data);
			});
		});
		
	});
	</script>
	
	<div class="wrap">
	
		<?php if($msg_updated): ?><div class="updated"><p><?php echo $msg_updated; ?></p></div><?php endif; ?>
		<?php if($msg_error): ?><div class="error"><p><?php echo $msg_error; ?></p></div><?php endif; ?>
	
		<div id="icon-tools" class="icon32"><br /></div>
		<h2>Postmark</h2>
		<p>Complete the form below to send mail from WordPress using Postmark. You will need a <a href="http://www.postmarkapp.com">Postmark</a> account to do this.</p>
		
		<h3>Postmark Settings</h3>
		<form method="post" action="options-general.php?page=pm_admin">
			<table class="form-table">
			<tbody>
				<tr>
					<th><label for="pm_enabled">Enabled? <input name="pm_enabled" id="" type="checkbox" value="1"<?php if(get_option('postmark_enabled') == 1): echo ' checked="checked"'; endif; ?>/></label></th>
				</tr>
				<tr>
					<th><label for="pm_api_key">Postmark API Key</label></th>
					<td><input name="pm_api_key" id="" type="text" value="<?php echo get_option('postmark_api_key'); ?>" class="regular-text"/></td>
				</tr>
				<tr>
					<th><label for="pm_sender_address">Sender Email Address</label></th>
					<td> <input name="pm_sender_address" id="" type="text" value="<?php echo get_option('postmark_sender_address'); ?>" class="regular-text"/></td>
				</tr>
			</tbody>
			</table>
			<div class="submit">
				<input type="submit" name="submit" value="Save" class="button-primary" />
			</div>
		</form>
		
		
		<h3>Test Postmark</h3>
		<p><a href="?page=pm_admin" class="button-primary pm_admin_test" rel="pm_admin_test">Send Test Email</a></p>
		
		
		<p style="margin-top:40px; padding-top:10px; border-top:1px solid #ddd;">This plugin is brought to you by <a href="http://www.postmarkapp.com">Postmark</a> &amp; <a href="http://www.andydev.co.uk/">Andrew Yates</a>.</p>
		
	</div>
	
<?php
}

add_action('wp_ajax_pm_admin_test', 'pm_admin_test_ajax');
function pm_admin_test_ajax() {
	$response = pm_send_test();

	echo $response;
	
	die();
}

// End Admin Functionality




// Override wp_mail() if postmark enabled
if(get_option('postmark_enabled') == 1){
	if (!function_exists("wp_mail")){
		function wp_mail( $to, $subject, $message, $headers = '', $attachments = array()) {
			// Define Headers
			$postmark_headers = array(
				'Accept: application/json',
                'Content-Type: application/json',
                'X-Postmark-Server-Token: ' . get_option('postmark_api_key')
			);
			
			// Add wp_mail headers
			if($headers){
					
			}
			
			// Send Email
			$recipients = explode(",", $to);
			
			foreach($recipients as $recipient){
				// Construct Message
				$email = array();
				$email['To'] = $to;
				$email['From'] = get_option('postmark_sender_address');
			    $email['Subject'] = $subject;
			    $email['TextBody'] = $message;
	            
	            $response = pm_send_mail($postmark_headers, $email);
			}
			return $response;
		}
	}
}


function pm_send_test(){
	// Define Headers
	$postmark_headers = array(
		'Accept: application/json',
        'Content-Type: application/json',
        'X-Postmark-Server-Token: ' . get_option('postmark_api_key')
	);
	
	$email = array();
	$email['To'] = get_option('postmark_sender_address');
	$email['From'] = get_option('postmark_sender_address');
    $email['Subject'] = get_bloginfo('name').' Postmark Test';
    $email['TextBody'] = 'This is a test email sent via Postmark from '.get_bloginfo('name').'.';
    
    $response = pm_send_mail($postmark_headers, $email);      
    
    if ($response === false){
    	return "Test Failed with Error ".curl_error($curl);
    } else {
    	return "Test Sent";
   	}
   	
    die();
}


function pm_send_mail($headers, $email){
	$curl = curl_init();
    curl_setopt_array($curl, array(
            CURLOPT_URL => POSTMARK_ENDPOINT,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($email),
            CURLOPT_RETURNTRANSFER => true
    ));
    
    $response = curl_exec($curl);
    
    if ($response === false){
    	return false;
    } else {
    	return true;
    }
}

?>