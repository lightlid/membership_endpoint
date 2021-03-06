<?php
/**
 * Plugin Name: Membership REST Endpoint
 * Description: Membership Endpoint beta
 * Version: 1.1
 * Author: Cady Zhang@RFP
 */

require_once 'methods.php';

add_action( 'rest_api_init', function () {
	
   /* Check membership by user_id
	@param: id - required, user_id
			status - optional."active"(default),"delayed","complimentary","pending","paused","cancelled","expired", "all"
	@return: 
		200 - 'membership_id','membership_plan','membership_product','membership_order_id','membership_start_date' ,'membership_end_date' ,'membership_subscription', 'membership_status'
		200 - [] 
		404 - 'User doesn't exist.'
   */         
    register_rest_route( 'customized_endpoint/v1/membership', '/check/',array(
		'args' => array(
			'id' => array(
				'validate_callback' => function($param, $request, $key) {
					return is_numeric( $param );
				},
				'required' => true
			)
		),
        'methods'  => 'GET',
        'callback' => 'get_membership_by_userid',
		'permission_callback' => function () {
		  return current_user_can( 'administrator' );
		}
	) );
	
	/* Check if user email exists 
	@param: email - optional, user_email
	@return: 
		200 - user_id, user_login, user_display_name, user_email
		404 - 'No such user'
	*/
	register_rest_route( 'customized_endpoint/v1/users', '/email/(?P<email>\S+)',array(
		
        'methods'  => 'GET',
        'callback' => 'search_user_by_email',
		'permission_callback' => function () {
		  return current_user_can( 'administrator' );
		}
	) );
	
	/* Create account in WP
	  @param: 
		Required: email (user_email)
		Optional: first_name, last_name, address, city, state, postcode, country
	  @return:
		200 - WP User & WP password 
		500 - 'There's a problem creating wordpress account.'
	*/
	register_rest_route( 'rfp_endpoint/v1/users', '/create_account/',array(
		'args' => array(
			'email' => array('required' => true )
		),
        'methods'  => 'POST',
        'callback' => 'create_wp_account',
		'permission_callback' => function () {
		  return current_user_can( 'administrator' );
		}
	) );
	
	/* Update WP User Meta 
	  @param: 
		Required: id (user_id)
		Optional: first_name, last_name, address, city, state, postcode, country	  
	  @return:
		200 - WP User Meta
		404 - 'User doesn't exist.'
	 
	*/
	register_rest_route( 'customized_endpoint/v1/users', '/update_account/',array(
		'args' => array(
			'id' => array(
				'validate_callback' => function($param, $request, $key) {
				  return is_numeric( $param );
				},
				'required' => true
			  )
		),
        'methods'  => 'POST',
        'callback' => 'update_wp_account',
		'permission_callback' => function () {
		  return current_user_can( 'administrator' );
		}
	) );
	
	/* Create Membership 
	!!!Note: plan_id & product_id are hard-coded. 
	@param: 
		Required: id (user_id)
		Optional: source, source_url, subscription_id, transaction_id
	@return:
		200 - WC Membership
		404 - 'User doesn't exist.'
		500 - 'Existing active membership'
	*/
	register_rest_route( 'customized_endpoint/v1/membership', '/create_membership/',array(
 
        'methods'  => 'POST',
        'callback' => 'create_membership_at_registration',
		'args' => array(
			'id' => array(
				'validate_callback' => function($param, $request, $key) {
				  return is_numeric( $param );
				},
				'required' => true
			  )
		),
		'permission_callback' => function () {
		  return current_user_can( 'administrator' );
		}
		) );
		
		
	/* Renewal Membership
	@param
		Required: id - user_id
				  plan - Note: plan_id is hard-coded. 
		Optional: source, source_url, subscription_id, transaction_id 
	@return:
		200 - WC Membership
		404 - 'User doesn't exist.'
		500 - 'User has existing active membership under selected plan.'
	*/	
	register_rest_route( 'customized_endpoint/v1/membership', '/renewal_membership/',array(
 
        'methods'  => 'POST',
        'callback' => 'renewal_existing_membership',
		'args' => array(
			'id' => array(
				'validate_callback' => function($param, $request, $key) {
				  return is_numeric( $param );
				},
				'required' => true
			  ),
			 'plan' => array('required' => true)
		),
		'permission_callback' => function () {
		  return current_user_can( 'administrator' );
		}
		) );				
				
});

?>
