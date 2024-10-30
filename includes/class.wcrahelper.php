<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*
 * Class WCRAHelper
 */

class WCRAHelper {

	/**
	 * Showing/Displaying Settings page
	 *
	 * @param $setRef
	 */
	public static function showSettingPanel( $setRef ) {
		?>
		<div class="wrap">

			<div id="icon-options-general" class="icon32"></div>
			<?php echo '<h2>' . __( 'CBX WCRA : Setting', 'cbxwoocouponreferral' ) . '</h2>'; ?>

			<div id="poststuff">

				<div id="post-body" class="metabox-holder columns-2">
					<!-- main content -->
					<div id="post-body-content">

						<div class="meta-box-sortables ui-sortable">

							<div class="postbox">

								<div class="inside">
									<?php
									$setRef->settings_api->show_navigation();
									$setRef->settings_api->show_forms();
									?>
								</div>
								<!-- .inside -->

							</div>
							<!-- .postbox -->

						</div>
						<!-- .meta-box-sortables .ui-sortable -->

					</div>
					<!-- post-body-content -->

					<!-- sidebar -->
					<?php
					include 'sidebar.php';
					?>
					<!-- #postbox-container-1 .postbox-container -->

				</div>
				<!-- #post-body .metabox-holder .columns-2 -->

				<br class="clear">
			</div>
			<!-- #poststuff -->

		</div> <!-- .wrap -->
		<?php
	}

	/**
	 * Install database tables on activation
	 */
	public static function cborderbycoupon_install_table() {

		global $wpdb;
		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}
		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

		//create brand new tables
		$table_name = self::get_cborderbycoupon_table_name();
		$sql        = "CREATE TABLE $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                order_id bigint(20) NOT NULL,
                coupon_id bigint(20) NOT NULL,
                order_info text,
                coupon_info varchar(255),
                order_items text,
                order_amount double NOT NULL,
                user_percentage double NOT NULL,
                user_percentage_type tinyint(1) NOT NULL DEFAULT '1',
                user_earning double NOT NULL ,
                order_date timestamp NOT NULL DEFAULT 0,
                extraparms text,
                PRIMARY KEY  (id)
            ) $charset_collate;";
		dbDelta( $sql );

		$referral_user_table_name = self::get_cborderbycoupon_user_table_name();
		$sql                      = "CREATE TABLE $referral_user_table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                coupon_id bigint(20) NOT NULL,
                status int(1) NOT NULL,
                added_date timestamp NOT NULL DEFAULT 0,
                PRIMARY KEY  (id)
            ) $charset_collate;";
		dbDelta( $sql );
	}

	/**
	 * Table name for coupon refer
	 *
	 * @return string
	 */
	public static function get_cborderbycoupon_table_name() {
		global $wpdb;

		return $wpdb->prefix . "wcra_referral";
	}

	/**
	 * Table name for coupon refer user
	 *
	 * @return string
	 */
	public static function get_cborderbycoupon_user_table_name() {
		global $wpdb;

		return $wpdb->prefix . "wcra_referral_user";
	}

	/**
	 * Create pages that the plugin relies on, storing page id's in variables.
	 */
	public static function cborderbycoupon_create_pages() {

		$pages = apply_filters(
			'cborderbycoupon_create_pages', array(
			'mycoupon' => array(
				'name'    => _x( 'wcramydashboard', 'Page slug', 'cbxwoocouponreferral' ),
				'title'   => _x( 'User Coupon Referral Dashboard', 'Page title', 'cbxwoocouponreferral' ),
				'content' => '[cbxwoocouponreferral]'
			)
		)
		);

		foreach ( $pages as $key => $page ) {
			self::cborderbycoupon_create_page( esc_sql( $page['name'] ), $key . '_page_id', $page['title'], $page['content'], '' );
		}

	}

	/**
	 * Create a page and store the ID in an option.
	 *
	 * @param mixed  $slug         Slug for the new page
	 * @param string $option       Option name to store the page's ID
	 * @param string $page_title   (default: '') Title for the new page
	 * @param string $page_content (default: '') Content for the new page
	 * @param int    $post_parent  (default: 0) Parent for the new page
	 *
	 * @return int page ID
	 */
	public static function cborderbycoupon_create_page( $slug, $optionname = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
		global $wpdb;

		$cborderbycoupon_basics = get_option( 'cbxwoocouponreferral_settings' );
		$option_value           = isset( $cborderbycoupon_basics['cborderbycoupon_pageid'] ) ? intval( $cborderbycoupon_basics['cborderbycoupon_pageid'] ) : 0;

		//if valid page id already exists
		if ( $option_value > 0 ) {
			$page_object = get_post( $option_value );

			if ( 'page' === $page_object->post_type && ! in_array( $page_object->post_status, array( 'pending', 'trash', 'future', 'auto-draft' ) ) ) {
				// Valid page is already in place
				return $page_object->ID;
			}
		}

		if ( strlen( $page_content ) > 0 ) {
			// Search for an existing page with the specified page content (typically a shortcode)
			$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
		} else {
			// Search for an existing page with the specified page slug
			$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );
		}

		$valid_page_found = apply_filters( 'cborderbycoupon_create_page_id', $valid_page_found, $slug, $page_content );

		// Search for a matching valid trashed page
		if ( strlen( $page_content ) > 0 ) {
			// Search for an existing page with the specified page content (typically a shortcode)
			$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
		} else {
			// Search for an existing page with the specified page slug
			$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_name = %s LIMIT 1;", $slug ) );
		}

		if ( $trashed_page_found ) {
			$page_id   = $trashed_page_found;
			$page_data = array(
				'ID'          => $page_id,
				'post_status' => 'publish',
			);
			wp_update_post( $page_data );
		} else {
			$page_data = array(
				'post_status'    => 'publish',
				'post_type'      => 'page',
				//'post_author'    => 1,
				'post_name'      => $slug,
				'post_title'     => $page_title,
				'post_content'   => $page_content,
				'post_parent'    => $post_parent,
				'comment_status' => 'closed'
			);
			$page_id   = wp_insert_post( $page_data );
		}

		//let's update the option
		$cborderbycoupon_basics['cborderbycoupon_pageid'] = $page_id;
		update_option( 'cbxwoocouponreferral_settings', $cborderbycoupon_basics );

		return $page_id;
	}


}