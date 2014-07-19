<?php

/*
Plugin Name: Authorize.net - Simple Donations
Author: Aman Verma | Wuxxa Design Studios
Author URI: http://www.wuxxa.com/product-support/?ref=auth-simple-donations
Plugin URL: http://www.wuxxa.com/products/authorizenet-simple-donations  /?ref=auth-simple-donation
Description: Accept donations simply with Authorize.net. Easy to use and configure.
Version: 1.1
License: GPLv2 or later
*/



add_shortcode('wds_donate', 'fn_wds_donate');
function fn_wds_donate()
{
	
	$valid	  = true;
	$msg  	  = '';
	$donation  = false;
	$html 	  = '';
	
	if( isset( $_POST['wds_donate'])):
	
		if( $_POST['donor_firstname'] != ''){
			$donor_firstname = $_POST['donor_firstname'];
		}
		else{
			$valid = false;
			$msg.= 'First Name is required </br>';
		}

		if( $_POST['donor_lastname'] != ''){
			$donor_lastname = $_POST['donor_lastname'];
		}
		else{
			$valid = false;
			$msg.= 'Last Name is required </br>';
		}

		if( $_POST['donor_email'] != ''){
			$donor_email = $_POST['donor_email'];
			if( preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/" , $donor_email)){}
			else{
				$valid = false;
				$msg.= 'Invalid email format </br>';
			}
		}
		else{
			$valid = false;
			$msg.= 'E-mail is required </br>';
		}

		if( $_POST['donation_amount'] != ''){
			$donation_amount = $_POST['donation_amount'];
			if( (is_numeric($donation_amount)) && ( (strlen($donation_amount) > '1') || (strlen($donation_amount) == '1')) ){}
			else{
				$valid = false;
				$msg.= 'Amount cannot be less then $1</br>';
			}
		}
		else{
			$valid = false;
			$msg.= 'Amount is required </br>';
		}


		if( $_POST['donor_card_number'] != ''){
			$donor_card_number = $_POST['donor_card_number'];
			if( (is_numeric($donor_card_number)) && (strlen($donor_card_number) > '15') ){}
			else{
				$valid = false;
				$msg.= 'Please enter valid Card Number</br>';
			}
		}
		else{
			$valid = false;
			$msg.= 'Card Number is required </br>';
		}
		


		if( $_POST['donor_cvv'] != ''){
			$donor_cvv = $_POST['donor_cvv'];
			if( (is_numeric($donor_cvv)) && (strlen($donor_cvv) == '3') ){}
			else{
				$valid = false;
				$msg.= 'Please enter valid CVV </br>';
			}
		}
		else{
			$valid = false;
			$msg.= 'CVV is required </br>';
		}


		if( $_POST['donor_card_expiry'] != ''){
			$donor_card_expiry = $_POST['donor_card_expiry'];
			if( (is_numeric($donor_card_expiry)) && (strlen($donor_card_expiry) == '4') ){}
			else{
				$valid = false;
				$msg.= 'Please enter valid Card Expiry Date</br>';
			}
		}
		else{
			$valid = false;
			$msg.= 'Card Expiry Date is required </br>';
		}


		if( $valid ){
			$donor_firstname;
			$donor_lastname;
			$donor_email;
			$donation_amount;
			$donor_card_number;
			$donor_cvv;
			$donor_card_expiry;
			$auth_login_id        = get_option('wds_donation_login_id');
			$auth_transaction_key = get_option('wds_donation_transaction_key');
			$auth_mode            = get_option('wds_donation_mode');
			//$flag_admin 		  = get_option('wds_admin_notification');
			//$flag_donor 		  = get_option('wds_donor_notification');
			$processor_description = get_option('wds_processor_description');
			
			if( get_option('wds_donation_mode') == "live" ){
				$post_url = "https://secure.authorize.net/gateway/transact.dll";
			}
			else{
				$post_url = "https://test.authorize.net/gateway/transact.dll";
			}

			$post_values = array(
				
				"x_login"			=> $auth_login_id,
				"x_tran_key"		=> $auth_transaction_key,

				"x_version"			=> "3.1",
				"x_delim_data"		=> "TRUE",
				"x_delim_char"		=> "|",
				"x_relay_response"	=> "FALSE",

				"x_type"			=> "AUTH_CAPTURE",
				"x_method"			=> "CC",
				"x_card_num"		=> $donor_card_number,
				"x_card_code"		=> $donor_cvv,
				"x_exp_date"		=> $donor_card_expiry,

				"x_amount"			=> $donation_amount,
				"x_description"		=> $processor_description,

				"x_first_name"		=> $donor_firstname,
				"x_last_name"		=> $donor_lastname,
				"x_email"			=> $donor_email,
				"x_address"			=> "",
				"x_state"			=> "",
				"x_zip"				=> ""
			);

			$post_string = "";
			foreach( $post_values as $key => $value )
				{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
			$post_string = rtrim( $post_string, "& " );


			$request = curl_init($post_url); 
				curl_setopt($request, CURLOPT_HEADER, 0); 
				curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); 
				curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
				$post_response = curl_exec($request); 
				// Connection to Authorize.net			
			curl_close ($request); // close curl object

				
			$response_array = explode($post_values["x_delim_char"],$post_response);
			$transaction_id = $response_array[7];
			$last_4         = $response_array[50];
			$approval_code  = $response_array[4];

			if($response_array[0] =='1'){
				
				$post = array(
				'post_type'    => 'wds_donation',
				'post_title'   => 'Donation - '. sanitize_text_field($_POST['donor_firstname']). ' '. sanitize_text_field($_POST['donor_lastname']),
				'post_status'  => 'publish',
				'post_author'  => 1,
				);
				$post_id = wp_insert_post( $post );

				add_post_meta($post_id, 'donor_firstname', sanitize_text_field($_POST['donor_firstname']), true);
				add_post_meta($post_id, 'donor_lastname', sanitize_text_field($_POST['donor_lastname']), true);
				add_post_meta($post_id, 'donor_email', sanitize_text_field($_POST['donor_email']), true);
				add_post_meta($post_id, 'donation_amount', sanitize_text_field($_POST['donation_amount']), true);
				add_post_meta($post_id, 'transaction_id', sanitize_text_field($transaction_id), true);
				add_post_meta($post_id, 'last_4', sanitize_text_field($last_4), true);
				add_post_meta($post_id, 'approval_code', sanitize_text_field($approval_code), true);
				$donation = true;
			}
			
			else if($response_array[0] =='2'){
			 	$msg .= $response_array[3] ;

			}
			else{
				$msg .= $response_array[3];
			}
			
			
		}
		else{	
		
		}
	endif;
	
	if($msg != ''){
		$html .= '<div style="border:double 3px #731D1D; padding:5px;">
				'.$msg.'
				</div>';
	}
	
	if($donation){
		$html .= '<div style="border:double 3px #008000; padding:5px;">' . get_option('wds_thankyou_message') . '</div>';
	
		
	}
	else{
	
	$html .= '<form method="post">
				<p><label style="width:50%">First Name</label> <input type="text"  style="width:50%" name="donor_firstname" id="donor_firstname"> </p>
				<p><label style="width:50%">Last Name</label> <input type="text"  style="width:50%" name="donor_lastname" id="donor_lastname"> </p>
				<p><label style="width:50%">E-mail</label> <input type="text" style="width:50%" name="donor_email" id="donor_email"> </p>
				<p><label style="width:50%">Amount (USD)</label> <input type="text" style="width:50%" name="donation_amount" id="donation_amount"> </p>
				<p><label style="width:50%">Card Number</label> <input type="text" maxlength="25" style="width:50%" name="donor_card_number" id="donor_card_number"> </p>
				<p><label style="width:50%">CVV</label> <input type="text" maxlength="3" name="donor_cvv" id="cvv"> </p>
				<p><label style="width:50%">Card Expiry (mmyy)</label> <input type="text" maxlength="4" name="donor_card_expiry" id="donor_card_expiry" > </p>
				<p> <input type="submit" name="wds_donate" value="Donate Now"> </p>
			</form>';
	
	}
	
	return $html;
}

function wds_donation_type() {

	$labels = array(
		'name'                => _x( 'Donations', 'Post Type General Name', 'wds_donation' ),
		'singular_name'       => _x( 'Donation', 'Post Type Singular Name', 'wds_donation' ),
		'menu_name'           => __( 'Donation ', 'wds_donation' ),
		'parent_item_colon'   => __( 'Parent Donation', 'wds_donation' ),
		'all_items'           => __( 'All Donation', 'wds_donation' ),
		'view_item'           => __( 'View Donation', 'wds_donation' ),
		'add_new_item'        => __( 'Add New Donation', 'wds_donation' ),
		'add_new'             => __( 'Add New', 'wds_donation' ),
		'edit_item'           => __( 'Edit Donation', 'wds_donation' ),
		'update_item'         => __( 'Update Donation', 'wds_donation' ),
		'search_items'        => __( 'Search Donation', 'wds_donation' ),
		'not_found'           => __( 'Not found', 'wds_donation' ),
		'not_found_in_trash'  => __( 'Not found in Trash', 'wds_donation' ),
	);
	$args = array(
		'label'               => __( 'wds_donation', 'wds_donation' ),
		'description'         => __( 'list of donations', 'wds_donation' ),
		'labels'              => $labels,
		'supports'            => array('title', 'custom-fields' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 5,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);
	register_post_type( 'wds_donation', $args );

}

add_action( 'init', 'wds_donation_type', 0 );

add_action('admin_menu', 'wds_create_menu');

function wds_create_menu() {

	add_menu_page('Donation Settings', 'Donation Settings', 'administrator', 'donation-settings-page', 'donation_settings_page');
   add_action( 'admin_init', 'register_mysettings' );
}


function register_mysettings() {

	register_setting( 'wds-settings-group', 'wds_donation_login_id' );
	register_setting( 'wds-settings-group', 'wds_donation_transaction_key' );
	register_setting( 'wds-settings-group', 'wds_donation_mode' );
	register_setting( 'wds-settings-group', 'wds_thankyou_message' );
	register_setting( 'wds-settings-group', 'wds_processor_description' );

}

function donation_settings_page() {
?>
<div class="wrap">
<h2>Donation Settings</h2>

<?php
   
if( isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true'):
   echo    '<div id="setting-error-settings_updated" class="updated settings-error"> 
<p><strong>Settings saved.</strong></p></div>';
endif;
?>

<form method="post" action="options.php">
    <?php settings_fields( 'wds-settings-group' ); ?>
    <?php do_settings_sections( 'wds-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
	    <th scope="row">Authorize.net Login Id</th>
        <td><input type="text" style="width:50%" name="wds_donation_login_id" value="<?php echo get_option('wds_donation_login_id'); ?>" /></td>
        </tr>
		
        <tr valign="top">
        <th scope="row">Authorize.net Transaction Key</th>
        <td><input type="text" style="width:50%" name="wds_donation_transaction_key" value="<?php echo get_option('wds_donation_transaction_key'); ?>" /></td>
        </tr>
		
		<tr valign="top">
        <th scope="row">Mode(Live/Test Sandbox)</th>
        <td><select name="wds_donation_mode" />
				<option value="live" <?php if( get_option('wds_donation_mode') == "live" ): echo 'selected'; endif;?> >Live</option>
				<option value="test" <?php if( get_option('wds_donation_mode') == "test" ): echo 'selected'; endif;?> >Test/Sandbox</option>
			</select></td>
		</tr>

		<tr valign="top">
        <th scope="row">Thank You Message</th>
        <td><input type="text" style="width:50%" name="wds_thankyou_message" value="<?php echo get_option('wds_thankyou_message'); ?>" /></td>
        </tr>
		
		<tr valign="top">
        <th scope="row">Processor Description</th>
        <td><input type="text" style="width:50%" name="wds_processor_description" value="<?php echo get_option('wds_processor_description'); ?>" /></td>
        </tr>
       
    </table>
    
    <?php submit_button(); ?>

</form>		
</div><?php
}

function donation_add_meta_box()
{
   $screens = array( 'wds_donation' );

   foreach ( $screens as $screen ) {

      add_meta_box(  'myplugin_sectionid', __( 'Donation Information', 'myplugin_textdomain' ),'donation_meta_box_callback', $screen, 'normal','high' );
   }
}

add_action( 'add_meta_boxes', 'donation_add_meta_box' );

function donation_meta_box_callback($post)
{

?>

   <ul>
   <li>
   <div class="html-label">First Name</div>

   <div class="html-field form-group" ><input type="text" name="donor_firstname" id=“donor_firstname" disabled value="<?php echo esc_attr(get_post_meta( $post->ID, 'donor_firstname', true ));?>"></div>
   </li>

   <li>
   <div class="html-label">Last Name</div>

   <div class="html-field form-group"><input type="text" name="donor_lastname" id="donor_last_name" disabled value="<?php echo esc_attr(get_post_meta( $post->ID, 'donor_lastname', true ));?>"></div>
   </li>

   <li>
   <div class="html-label">E-mail</div>

   <div class="html-field form-group"><input type="text" name="donor_email" id="donor_email" disabled value="<?php echo esc_attr(get_post_meta( $post->ID, 'donor_email', true ));?>"></div>
   </li>

   <li>
   <div class="html-label">Amount (USD)</div>

   <div class="html-field form-group"><input type="text" name="donation_amount" id="donation_amount" disabled value="<?php echo esc_attr(get_post_meta( $post->ID, 'donation_amount', true ));?>"></div>
   </li>

   <li>
   <div class="html-label">Transaction ID</div>

   <div class="html-field form-group"><input type="text" name="transaction_id" id="transaction_id" disabled value="<?php echo esc_attr(get_post_meta( $post->ID, 'transaction_id', true ));?>"></div>
   </li>

   <li>
   <div class="html-label">Last 4</div>

   <div class="html-field form-group"><input type="text" name="last_4" id="last_4" disabled value="<?php echo esc_attr(get_post_meta( $post->ID, 'last_4', true ));?>"></div>
   </li>

   <li>
   <div class="html-label">ApprovalCode</div>

   <div class="html-field form-group"><input type="text" name="approval_code" id="approval_code" disabled value="<?php echo esc_attr(get_post_meta( $post->ID, 'approval_code', true ));?>"></div>
   </li>
   </ul>


<?php  

}
