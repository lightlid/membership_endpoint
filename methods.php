<?php

function check_active_membership_by_plan_id($user_id, $plan_id){
	$args = array( 'status' => array('active') );  
	$active_memberships = wc_memberships_get_user_memberships( $user_id, $args );
	$active_member = array();	
	$check = false;
	foreach ( $active_memberships as $plan ) {
		$membership_plan_id = $plan->plan_id;
		if($membership_plan_id == $plan_id){
			$check = true;
		}
	}
	return $check;	
} 

function search_user_by_email(  WP_REST_Request $request ){
	$email = $request['email'];
	$user = get_user_by( 'email', $email );
	if ( ! empty( $user ) ) {
		$user_id = $user->ID;
			
		$user_details = array(
			'user_id'=>$user_id,
			'user_login'=>$user->user_login,
			'user_display_name'=>$user->display_name,
			'user_email' =>$user->user_email,
			'status' => 200
		);
		
		return $user_details;		
	}
	else{
		$result = array('status'=>404,'message'=>"No such user.");
		return $result;
		
	}	
	
}

function get_membership_by_userid ( WP_REST_Request $request){
	$user_id = $request['id'];
	if(isset($request['status'])){
		$membership_status = $request['status'];		
	}
	else $membership_status = "active";
	$user = get_user_by( 'id', $user_id );
	//$plans = wc_memberships_get_membership_plans();

	$existing_member = array();
	if ( ! empty( $user ) ) {
		$args = array( 'status' => array( $membership_status ));  
		$active_memberships = wc_memberships_get_user_memberships( $user_id, $args );
		foreach ( $active_memberships as $plan ) {
			
			$membership_id = $plan->id;
			$wcm_status = get_post($membership_id)->post_status;			
			$arr = explode('-',$wcm_status,2);
			$mebership_status = $arr[1];
	
			$membership = array(
			'membership_id'=>$plan->id,
			'membership_plan'=>$plan->plan_id,
			'membership_product' =>get_post_meta($membership_id,'_product_id')[0],
			'membership_order_id' =>get_post_meta($membership_id,'_order_id')[0],
			'membership_start_date' =>get_post_meta($membership_id,'_start_date')[0],
			'membership_end_date' =>get_post_meta($membership_id,'_end_date')[0],
			'membership_subscription' =>get_post_meta($membership_id,'_subscription_id')[0],
			'membership_status' => $mebership_status
			);
			array_push( $existing_member, $membership );	
			
		}
		
		return $existing_member;
	}
	else{
			$result = array('status'=>404,'message'=>"User doesn't exist.");
			return $result;
		
	}
			
}


function create_membership_at_registration ( WP_REST_Request $request ){
	$user_id = $request['id'];	
	$note = "";
	if(isset($request['subscription_id'])){
		$note .= "Subscription ID: ".$request['subscription_id'].". ";
	}
	if(isset($request['transaction_id'])){
		$note .= "Transaction ID: ".$request['transaction_id'].". ";
	}
	if(isset($request['source_url'])){
		$note .= "Link: ".$request['source_url'];
	}
	$referrer = $request['source'];
	$product_id = [PRODUCT_ID];
	$user = get_user_by( 'id', $user_id );
	$plan_id = [PLAN_ID];
	if ( ! empty( $user ) ) {
		if(!check_active_membership_by_plan_id($user_id,$plan_id)){
			
			 $args = array(
                'plan_id' => $plan_id,
                'user_id' => $user_id,
				'product_id' => $product_id 
				);
			wc_memberships_create_user_membership( $args );
			$user_membership = wc_memberships_get_user_membership( $user_id, $args['plan_id'] );
			$end_date = date('Y-m-d h:i:s', strtotime('+2 years'));	//grant 2 years access		
			update_post_meta($user_membership->id,'_end_date',$end_date);			
			$user_membership->add_note( 'Membership access granted automatically from API('.$referrer.'). '.$note);
			return $user_membership;
			
		}
		else{
			$result = array('status'=>500,'message'=>"Existing active membership");
			return $result;
		}
	}
	else{
			$result = array('status'=>404,'message'=>"User doesn't exist.");
			return $result;
	}
			
}

function renewal_membership($membership_id,$expire_date){
	$membership_renewal = array(
      'ID'           => $membership_id,
      'post_status'   => 'wcm-active'     
	);
	wp_update_post( $membership_renewal );
	update_post_meta($membership_id,'_end_date',$expire_date);
						
	return ;
}

function renewal_existing_membership(WP_REST_Request $request){
	//param = $membership_id,$plan
	$plan_name = $request['plan'];
	$user_id = $request['id'];
	
	$note = "";
	if(isset($request['subscription_id'])){
		$note .= "Subscription ID: ".$request['subscription_id'].". ";
	}
	if(isset($request['transaction_id'])){
		$note .= "Transaction ID: ".$request['transaction_id'].". ";
	}
	if(isset($request['source_url'])){
		$note .= "Link: ".$request['source_url'];
	}
	
	$referrer = $request['source'];
	$user = get_user_by( 'id', $user_id );
	if ( ! empty( $user ) ) {
	
		$plan_id = [PLAN_ID];
		$end_date = '2100-01-01 00:00:00';
		
		if(!check_active_membership_by_plan_id($user_id,$plan_id)){
			
			$args = array( 'status' => array("delayed","complimentary","pending","paused","cancelled","expired") );  
			$existing_memberships = wc_memberships_get_user_memberships( $user_id, $args );
			foreach ( $existing_memberships as $plan ) {
				$membership_plan_id = $plan->plan_id;
				if($membership_plan_id == $plan_id){
					renewal_membership($plan->id, $end_date);
					$plan->add_note( 'Membership is renewed from API('.$referrer.'). '.$note);
					return get_post_meta($plan->id);
				}
				else{
					$result = array('status'=>500,'message'=>"Can't renew membership under ".$plan_name);
					return $result;
				}
			}
		}
		else{
			$result = array('status'=>500,'message'=>"Existing active membership under plan ".$plan_name);
			return $result;
		}
		
	}
	else{
			$result = array('status'=>404,'message'=>"User doesn't exist.");
			return $result;
	}
}

function update_usermeta_field($user_id,$key,$value){
	$havemeta = get_user_meta($user_id,$key,false);
	if($havemeta){
		update_user_meta($user_id,$key,$value);
	}
	else{
		add_user_meta($user_id,$key,$value);
	}
}	


function create_wp_account(WP_REST_Request $request ){
	$user_email = $request['email'];
	$arr = explode('@',$user_email,2);
	$user_name = $arr[0];
	$user_id = username_exists( $user_name );

	$data = array(
		'first_name' => $request['first_name'],
		'last_name' => $request['last_name'],
		'billing_address_1' => $request['address'],
		'billing_city' => $request['city'],
		'billing_postcode' => $request['postcode'],
		'billing_state' => $request['state'],
		'billing_country' => $request['country'],
		'billing_first_name' => $request['first_name'],
		'billing_last_name' => $request['last_name']
		
	);
	
	 if( !$user_id and email_exists($user_email) == false ) {
		$random_password = wp_generate_password($length=12, $include_standard_special_chars=false );
		$user_id = wp_create_user( $user_name, $random_password, $user_email );
		foreach($data as $key => $value){
			update_usermeta_field($user_id,$key,$value);
		}
		
		return array(get_user_by( 'id', $user_id ),'wp_password' => $random_password);
		
	}
	elseif($user_id and email_exists($user_email) == false){
		$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
		$user_id = wp_create_user( $user_email, $random_password, $user_email );			
		foreach($data as $key => $value){
			update_usermeta_field($user_id,$key,$value);
		}
		return array(get_user_by( 'id', $user_id ),'wp_password' => $random_password);
		
	}
	else{
		
		$result = array('status'=>500,'message'=>"There's a problem creating worpdpress account.");
		return $result;
	}	
}

function update_wp_account(WP_REST_Request $request ){
	$user_id = $request['id'];
	$user = get_user_by( 'id', $user_id );
	if ( ! empty( $user ) ) {
		
		
		$data = array(
			'first_name' => $request['first_name'],
			'last_name' => $request['last_name'],
			'billing_address_1' => $request['address'],
			'billing_city' => $request['city'],
			'billing_postcode' => $request['postcode'],
			'billing_state' => $request['state'],
			'billing_country' => $request['country'],
			'billing_first_name' => $request['first_name'],
			'billing_last_name' => $request['last_name']
			
		);
		
		foreach($data as $key => $value){
			update_usermeta_field($user_id,$key,$value);
		}
		return get_user_meta($user_id);
	}
	else{
			$result = array('status'=>404,'message'=>"User doesn't exist.");
			return $result;
	}
		
}
?>
