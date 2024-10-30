<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WCRACouponTab
 */
class WCRACouponTab {

	/**
	 * WCRACouponTab constructor.
	 */
	public function __construct() {

		// show tab, output panel and save for coupons
		// first check if woocommerce active
		if ( class_exists( 'Woocommerce' ) ) {
			//new tab for wcra
			add_filter( 'woocommerce_coupon_data_tabs', array( $this, 'cbx_couponrefer_tabs' ) );
			//show data in tab
			add_action( 'woocommerce_coupon_data_panels', array( $this, 'cbx_couponrefer_datapanel' ) );
			//save data
			add_action( 'woocommerce_coupon_options_save', array( $this, 'cbcoupon_refer_save_coupon_data' ) );
			//edit coupon table
		}
	}

	/**
	 * Add new tab in coupon settings
	 *
	 * @param $tabs
	 *
	 * @return array
	 */
	function cbx_couponrefer_tabs( $tabs ) {

		$tabs = array_merge(
			$tabs, array(
			'cbx_coupon_refer_settings' => array(
				'label'  => __( 'WCRA Referral', 'cbxwoocouponreferral' ),
				'target' => 'cbx_coupon_refer_settings_coupon_data',
				'class'  => 'cbx_coupon_refer_settings_coupon_data',
			)
		)
		);

		return $tabs;
	}

	/**
	 * function cbcoupon_refer_add_datapanel
	 * add data panel in coupon add/edit
	 */
	function cbx_couponrefer_datapanel() {


		echo '<div id="cbx_coupon_refer_settings_coupon_data" class="panel woocommerce_options_panel">
			  <div class="options_group">';

		// Add user who can refer the coupon
		$user_id   = (int) get_post_meta( get_the_ID(), 'cbx_coupon_refer_userid', true );
		$user_name = get_post_meta( get_the_ID(), 'cbx_coupon_refer_user', true );

		$remove_style = 'display:none;';
		$display_html = '';

		if ( $user_id > 0 ) {

			$display_html = '<a href = "' . admin_url( 'user-edit.php?user_id=' ) . $user_id . '" data-user_id="' . $user_id . '" class="wcruser_id">' . $user_name . '</a>';
			$remove_style = '';
		}

		woocommerce_wp_hidden_input( array( 'id' => 'cbx_coupon_refer_user_id', 'type' => 'hidden', 'value' => $user_id ) );

		echo '<div class="coupon-custom-field-wrapper coupon-custom-field-wrapper-' . get_the_ID() . '">

		<div style = "float:left">';
		woocommerce_wp_text_input( array( 'id' => 'cbx_coupon_refer_search', 'label' => __( 'Search User Name', 'cbxwoocouponreferral' ), 'placeholder' => _x( 'User Name', 'placeholder', 'cbxwoocouponreferral' ), 'description' => __( 'User from selected role who will refer this coupon to others. User role can be set from the setting menu.', 'cbxwoocouponreferral' ), 'type' => 'text', 'desc_tip' => true, 'class' => 'normal' ) );
		echo '</div>

				            <div style = "float:left;padding-top:20px;" id="cbx_coupon_refer_user_desc" class="cbx_coupon_refer_user_desc">' . $display_html . '</div>
				       </div>
				       <div style = "float:left; padding:10px;">
				            <a style="' . $remove_style . '" class = "coupon-custom-field-remove button btn-primaty" data-id = "' . get_the_ID() . '" id="coupon-custom-field-remove-' . get_the_ID() . '"   >' . __( 'Remove', 'cbxwoocouponreferral' ) . '</a>
				       </div>
				       <div style="clear:both;"></div>';

		woocommerce_wp_text_input(
			array(
				'id' => 'cbx_coupon_refer_user_milestone', 'label' => __( 'User Target Milestone Per Month', 'cbxwoocouponreferral' ), 'placeholder' => _x( 'User Milestone', 'placeholder', 'cbxwoocouponreferral' ), 'description' => __( 'What total order amount is target for this user with this coupon.', 'cbxwoocouponreferral' ), 'desc_tip' => true, 'class' => 'short', 'type' => 'number', 'custom_attributes' => array(
				'step' => '1',
				'min'  => '0'
			)
			)
		);

		woocommerce_wp_text_input( array( 'id' => 'cbx_coupon_refer_user_percent', 'label' => __( 'User Commission', 'cbxwoocouponreferral' ), 'placeholder' => _x( 'User Commission', 'placeholder', 'cbxwoocouponreferral' ), 'description' => __( 'How much money user will get from his referral amount', 'cbxwoocouponreferral' ), 'type' => 'text', 'desc_tip' => true, 'class' => 'normal' ) );


		woocommerce_wp_radio(
			array(
				'id'      => 'cbx_coupon_refer_userpercent_type',
				'label'   => __( 'Commission Type', 'cbxwoocouponreferral' ),
				'type'    => 'radio',
				'options' => array(
					'1' => __( 'Percentage', 'cbxwoocouponreferral' ),
					'2' => __( 'Fixed', 'cbxwoocouponreferral' )
				)

			)
		);

		echo '</div>';
		echo '</div>';
	}

	/**
	 * save coupon data
	 *
	 * @param $post_id
	 */
	function cbcoupon_refer_save_coupon_data( $post_id ) {
		global $wpdb;

		$cborderbycoupon_setting_api = get_option( 'cbxwoocouponreferral_settings' );

		if ( isset( $cborderbycoupon_setting_api['cborderbycoupon_bindone'] ) ) {
			$cborderbycoupon_bindone = $cborderbycoupon_setting_api['cborderbycoupon_bindone'];
		} else {
			$cborderbycoupon_bindone = '';
		}

		if ( isset( $cborderbycoupon_setting_refer['cborderbycoupon_usergroupsr'] ) ) {
			$cborderbycoupon_usergroup = $cborderbycoupon_setting_refer['cborderbycoupon_usergroupsr'];
		} else {
			$cborderbycoupon_usergroup = '';
		}

		$prev_user_refer_id = (int) get_post_meta( $post_id, 'cbx_coupon_refer_userid', true ); //get if there is any previous user associated with his coupon

		//$cbxrefer_user                 = sanitize_text_field($_POST['cbx_coupon_refer_user']);
		$cbxrefer_user_id                  = isset( $_POST['cbx_coupon_refer_user_id'] ) ? intval( $_POST['cbx_coupon_refer_user_id'] ) : 0;
		$cbxrefer_user_milestone           = isset( $_POST['cbx_coupon_refer_user_milestone'] ) ? wc_format_decimal( $_POST['cbx_coupon_refer_user_milestone'] ) : 0;
		$cbx_coupon_refer_user_percent     = isset( $_POST['cbx_coupon_refer_user_percent'] ) ? doubleval( $_POST['cbx_coupon_refer_user_percent'] ) : 0;
		$cbx_coupon_refer_userpercent_type = isset( $_POST['cbx_coupon_refer_userpercent_type'] ) ? esc_attr( $_POST['cbx_coupon_refer_userpercent_type'] ) : 1;

		$cbxrefer_users  = get_userdata( (int) $cbxrefer_user_id );
		$cbxrefer_pusers = get_userdata( (int) $prev_user_refer_id );

		$cborderbycoupon_setting_refer = get_option( 'cbxwoocouponreferral_settings' );

		$cborderbycoupon_args = array(
			'posts_per_page' => - 1,
			'meta_key'       => 'cbx_coupon_refer_userid',
			'meta_value'     => $cbxrefer_user_id,
			'post_type'      => 'shop_coupon',
		);

		$cborderbycoupon_orders = new WP_Query( $cborderbycoupon_args );

		wp_reset_postdata();

		if ( $cbxrefer_user_id == 0 && $prev_user_refer_id > 0 ) {
			//remove mode
			//previous user id $prev_user_refer_id  is removed

			$user_updated = $wpdb->update(
				WCRAHelper::get_cborderbycoupon_user_table_name(),
				array(
					'status'     => 0,
					'added_date' => date( 'Y-m-d H:i:s' ),
				),
				array( 'user_id' => $prev_user_refer_id, 'coupon_id' => $post_id ),
				array(
					'%d',
					'%s',
				),
				array( '%d', '%d' )
			);
			do_action( 'wcra_before_removed_user_notification', $prev_user_refer_id, $post_id ); //added by sabuj

			//now delete
			//delete_user_meta($prev_user_refer_id, $post_id, $cbxrefer_user_milestone);
			delete_post_meta( $post_id, 'cbx_coupon_refer_user' ); //user display name
			delete_post_meta( $post_id, 'cbx_coupon_refer_userid' ); //user id
			delete_post_meta( $post_id, 'cbx_coupon_refer_user_milestone' ); //milestone
			delete_post_meta( $post_id, 'cbx_coupon_refer_user_percent' ); //commission
			delete_post_meta( $post_id, 'cbx_coupon_refer_userpercent_type' ); //commission type

			do_action( 'wcra_removed_user_notification', $prev_user_refer_id, $post_id );

		} else if ( $cbxrefer_user_id > 0 ) {
			//add new or replace
			if ( $cbxrefer_user_id != $prev_user_refer_id ) {
				//previous user id $prev_user_refer_id  is removed

				$user_updated = $wpdb->update(
					WCRAHelper::get_cborderbycoupon_user_table_name(),
					array(
						'status' => 0, 'added_date' => date( 'Y-m-d H:i:s' ),
					),
					array( 'user_id' => $prev_user_refer_id, 'coupon_id' => $post_id ),
					array(
						'%d', '%s',
					),
					array( '%d', '%d' )
				);

				do_action( 'wcra_before_removed_user_notification', $prev_user_refer_id, $post_id ); //added by sabuj

				//now delete old person meta data $prev_user_refer_id

				delete_post_meta( $post_id, 'cbx_coupon_refer_user' ); //user display name
				delete_post_meta( $post_id, 'cbx_coupon_refer_userid' ); //user id
				delete_post_meta( $post_id, 'cbx_coupon_refer_user_milestone' ); //milestone
				delete_post_meta( $post_id, 'cbx_coupon_refer_user_percent' ); //commission
				delete_post_meta( $post_id, 'cbx_coupon_refer_userpercent_type' ); //commission type

				do_action( 'wcra_removed_user_notification', $prev_user_refer_id, $post_id );

				//$cbxrefer_user_id added
				$add_user = true;
				if ( $cborderbycoupon_bindone == 'on' && $cborderbycoupon_orders->found_posts > 0 ) {
					$add_user = false;
				}

				if ( $add_user ) {
					do_action( 'wcra_before_added_user_notification', $cbxrefer_user_id, $post_id );

					add_post_meta( $post_id, 'cbx_coupon_refer_user', $cbxrefer_users->display_name ); //user display name
					add_post_meta( $post_id, 'cbx_coupon_refer_userid', $cbxrefer_user_id ); //user id
					add_post_meta( $post_id, 'cbx_coupon_refer_user_milestone', $cbxrefer_user_milestone ); //milestone
					add_post_meta( $post_id, 'cbx_coupon_refer_user_percent', $cbx_coupon_refer_user_percent ); //commission
					add_post_meta( $post_id, 'cbx_coupon_refer_userpercent_type', $cbx_coupon_refer_userpercent_type ); //commission type

					$wpdb->delete( WCRAHelper::get_cborderbycoupon_user_table_name(), array( 'user_id' => $cbxrefer_user_id, 'coupon_id' => $post_id ), array( '%d', '%d' ) );
					$user_added = $wpdb->insert(
						WCRAHelper::get_cborderbycoupon_user_table_name(),
						array(
							'user_id'    => $cbxrefer_user_id,
							'coupon_id'  => $post_id,
							'status'     => 1,
							'added_date' => date( 'Y-m-d H:i:s' ),
						),
						array(
							'%d', '%d', '%d', '%s'
						)
					);

					do_action( 'wcra_added_user_notification', $cbxrefer_user_id, $post_id );
				} else {
					WC_Admin_Meta_Boxes::add_error( sprintf( __( 'CBX WCRA: User %s (ID: %d) has a coupon association already', 'cbxwoocouponreferral' ), $cbxrefer_users->display_name, $cbxrefer_user_id ) );
				}

			} else if ( $cbxrefer_user_id == $prev_user_refer_id ) {
				//just update the previous user data

				update_post_meta( $post_id, 'cbx_coupon_refer_user', $cbxrefer_users->display_name ); //user display name
				update_post_meta( $post_id, 'cbx_coupon_refer_userid', $cbxrefer_user_id ); //user id
				update_post_meta( $post_id, 'cbx_coupon_refer_user_milestone', $cbxrefer_user_milestone ); //milestone
				update_post_meta( $post_id, 'cbx_coupon_refer_user_percent', $cbx_coupon_refer_user_percent ); //commission
				update_post_meta( $post_id, 'cbx_coupon_refer_userpercent_type', $cbx_coupon_refer_userpercent_type ); //commission
			}

		}

	}

	/**
	 * cborderbycouponforwoocommerce_columnset
	 * set order coupon column in order table
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public static function cbcouponrefer_columnset( $columns ) {
		$columns['coupon_refer_user']             = __( "Rererral User", "cbxwoocouponreferral" );
		$columns['coupon_refer_user_milestone']   = __( "Milestone Per Month", "cbxwoocouponreferral" );
		$columns['coupon_refer_user_achieved']    = __( "Milestone Achieved", "cbxwoocouponreferral" );
		$columns['coupon_refer_user_percentage']  = __( "User %", "cbxwoocouponreferral" );
		$columns['coupon_refer_userpercent_type'] = __( "% Type", "cbxwoocouponreferral" );

		return $columns;
	}

	/**
	 * show coupon name in coupon column
	 *
	 * @param $column_name
	 * @param $post_id
	 */
	public static function cbcouponrefer_columndisplay( $column_name, $post_id ) {

		global $wpdb;
		$coupontable_name               = WCRAHelper:: get_cborderbycoupon_table_name();
		$cbx_xoupon_refer_user_target   = get_post_meta( $post_id, 'cbx_coupon_refer_user_milestone', true );
		$cbx_xoupon_refer_user_perc     = get_post_meta( $post_id, 'cbx_coupon_refer_user_percent', true );
		$cbx_xoupon_refer_user_perctype = get_post_meta( $post_id, 'cbx_coupon_refer_userpercent_type', true );

		$cbx_xoupon_refer_user    = get_post_meta( $post_id, 'cbx_coupon_refer_user', true );
		$cbx_xoupon_refer_user_id = get_post_meta( $post_id, 'cbx_coupon_refer_userid', true );

		$commission_type = array(
			'1' => '%',
			'2' => __( 'Fixed', 'cbxwoocouponreferral' )
		);

		$percentage_sign       = '';
		$commission_type_value = '';

		//var_dump($cbx_xoupon_refer_user_perctype);

		if ( $cbx_xoupon_refer_user_perctype == false ) {
			$commission_type_value = '%';
			$percentage_sign       = '%';
		} else {
			if ( ! in_array( $cbx_xoupon_refer_user_perctype, array( 1, 2 ) ) ) {
				$cbx_xoupon_refer_user_perctype = 1;
			}

			$commission_type_value = $commission_type[$cbx_xoupon_refer_user_perctype];
			$percentage_sign       = ( $cbx_xoupon_refer_user_perctype == 1 ) ? '%' : '';
		}

		switch ( $column_name ) {

			case 'coupon_refer_user':
				echo ( $cbx_xoupon_refer_user == false ) ? __( 'N/A', 'cbxwoocouponreferral' ) : '<a  target="_blank" href="' . get_edit_user_link( $cbx_xoupon_refer_user_id ) . '">' . $cbx_xoupon_refer_user . '</a>';
				break;

			case 'coupon_refer_user_milestone':
				echo ( $cbx_xoupon_refer_user_target == false ) ? __( 'N/A', 'cbxwoocouponreferral' ) : $cbx_xoupon_refer_user_target;
				break;

			case 'coupon_refer_user_percentage':
				echo ( $cbx_xoupon_refer_user_perc == false ) ? __( 'N/A', 'cbxwoocouponreferral' ) : $cbx_xoupon_refer_user_perc . $percentage_sign;
				break;

			case 'coupon_refer_userpercent_type':
				//echo ($cbx_xoupon_refer_user_perctype == false) ? __('Percentage', 'cbxwoocouponreferral') : $commission_type[$cbx_xoupon_refer_user_perctype];
				echo $commission_type_value;
				break;


			case 'coupon_refer_user_achieved' :

				if ( $cbx_xoupon_refer_user_id != false ) {
					$cbx_total_target_achieved = 0.0;
					$sql                       = $wpdb->prepare( "SELECT order_amount FROM $coupontable_name WHERE user_id = %d AND coupon_id = %d ", (int) $cbx_xoupon_refer_user_id, $post_id );
					$order_amounts             = $wpdb->get_results( $sql, ARRAY_A );
					if ( is_array( $order_amounts ) && ! empty( $order_amounts ) ) {
						foreach ( $order_amounts as $order_amount ) {
							$cbx_total_target_achieved += (double) $order_amount ['order_amount'];
						}
					}
					echo $cbx_total_target_achieved;
				}// end of if user id found
				break;
		}// end of switch
	}

	/**
	 * Called to add sort function to column coupon
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public static function cbcouponrefer_columnsort( $columns ) {
		$columns['coupon_refer_user']             = "coupon_refer_user";
		$columns['coupon_refer_user_milestone']   = "coupon_refer_user_milestone";
		$columns['coupon_refer_user_achieved']    = "coupon_refer_user_achieved";
		$columns['coupon_refer_user_percentage']  = "coupon_refer_user_percentage";
		$columns['coupon_refer_userpercent_type'] = "coupon_refer_userpercent_type";

		return $columns;
	}
}