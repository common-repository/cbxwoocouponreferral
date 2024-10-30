<?php
/**
 * Plugin Name:       CBX Woo Coupon Referral Affiliate(WCRA)
 * Plugin URI:        http://codeboxr.com/product/cbx-woo-coupon-referral-affiliate/
 * Description:       Woocommerce Coupon Referral Affiliate For Sales Representative
 * Version:           3.0.2
 * Author:            Codeboxr Team
 * Author URI:        http://codeboxr.com
 * Text Domain:       cbxwoocouponreferral
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'CBXWOOCOUPONREFFERAL_BASENAME', 'CBX Woo Coupon Referral Affiliate(WCRA)' ); //define global var
define( 'CBXWOOCOUPONREFFERAL_VERSION', '3.0.2' ); //always keep sync with the plugin definition section's plugin version
define( 'CBXWOOCOUPONREFFERAL_AUTHOR', 'Codeboxr Team' );

define( 'CBXWOOCOUPONREFFERAL_URL', plugin_dir_path( __FILE__ ) ); //define global var

require_once( ABSPATH . WPINC . '/plugin.php' );
require_once( plugin_dir_path( __FILE__ ) . '/widgets/wcratop/wcratop-widget.php' );

require_once( plugin_dir_path( __FILE__ ) . "includes/class.wcrahelper.php" ); // helper class
require_once( plugin_dir_path( __FILE__ ) . "includes/class.cbxwoocouponreferralsetting.php" ); // include settings page
require_once( plugin_dir_path( __FILE__ ) . "includes/class.wcracoupontab.php" ); // include coupon refer  page
//require_once(plugin_dir_path(__FILE__) . "includes/cbxwoocouponreferral_functions.php"); // include coupon refer  page

//activation and deactivation hook
register_activation_hook( __FILE__, array( 'CBXWooCouponReferral', 'install_plugin' ) );
register_deactivation_hook( __FILE__, array( 'CBXWooCouponReferral', 'uninstall_plugin' ) );

/**
 * Class CBXWooCouponReferral
 */
class CBXWooCouponReferral {
	/**
	 * The plugin ID of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_slug The plugin slug of the plugin.
	 *
	 */
	protected $plugin_slug = 'cbxwoocouponreferral';

	/**
	 * Static monthname.
	 *
	 * @since    1.0.0
	 * @access   public static
	 * @var      array $monthname Monthname.
	 *
	 */
	public static $monthname = array();

	public static $shortmonthname = array();

	public $settings_api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 */
	public function __construct() {
		//loads textdomain/language file
		load_plugin_textdomain( 'cbxwoocouponreferral', false, plugin_dir_path( __FILE__ ) . 'languages/' );

		$this->settings_api = new CBXWoocouponreferralSetting( CBXWOOCOUPONREFFERAL_BASENAME, CBXWOOCOUPONREFFERAL_VERSION );

		//check if woocommerce is installed
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'add_woocommerce_activation_notice' ) );

			return;
		}

		//monthname
		self::$monthname = array(
			__( 'January' ),
			__( 'February' ),
			__( 'March' ),
			__( 'April' ),
			__( 'May' ),
			__( 'June' ),
			__( 'July' ),
			__( 'August' ),
			__( 'September' ),
			__( 'October' ),
			__( 'November' ),
			__( 'December' )
		);

		//shortmonthname
		self::$shortmonthname = array(
			_x( 'Jan', 'January abbreviation' ),
			_x( 'Feb', 'February abbreviation' ),
			_x( 'Mar', 'March abbreviation' ),
			_x( 'Apr', 'April abbreviation' ),
			_x( 'May', 'May abbreviation' ),
			_x( 'Jun', 'June abbreviation' ),
			_x( 'Jul', 'July abbreviation' ),
			_x( 'Aug', 'August abbreviation' ),
			_x( 'Sep', 'September abbreviation' ),
			_x( 'Oct', 'October abbreviation' ),
			_x( 'Nov', 'November abbreviation' ),
			_x( 'Dec', 'December abbreviation' )
		);

		//plugin row actions
		add_filter( 'plugin_action_links_' . CBXWOOCOUPONREFFERAL_BASENAME, array( $this, 'plugin_support' ) );

		add_action( 'admin_init', array( $this, 'admin_init' ) );

		//enqueue admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		//enqueue front end scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'public_enqueue_scripts' ) );

		// ajax for autocomplete, while suggesting user to coupon, it doesn't save in database
		add_action( "wp_ajax_cbcouponrefer_autocomplete", array( $this, "cb_ajax_cbcouponrefer_autocomplete" ) );
		add_action( "wp_ajax_cbrefercoupon_orderbyyears", array( $this, "cb_ajax_cbrefercoupon_orderbyyears" ) );
		add_action( "wp_ajax_cbrefercoupon_orderbymonths", array( $this, "cb_ajax_cbrefercoupon_orderbymonths" ) );

		//ajax for updating user meta
		add_action( "wp_ajax_wcra_user_contactinfo", array( $this, "wp_ajax_wcra_user_contactinfo" ) );

		// add menu
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );

		//all used woocommerce hooks

		//for completed order
		add_action( 'woocommerce_order_status_completed', array( $this, 'wcra_order_completed' ), 10.1 );
		//for refunded order
		add_action( 'woocommerce_order_refunded', array( $this, 'wcra_order_refunded' ), 10, 2 );
		//for cancelled order
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'wcra_order_status_cancelled' ) );

		/*** coupon related shortcodes ***/
		$posttype_coupon = "shop_coupon";
		// edit coupon table
		add_filter( "manage_edit-{$posttype_coupon}_columns", array( 'WCRACouponTab', 'cbcouponrefer_columnset' ), 20, 1 );      // add coupon coulmn
		add_action( "manage_{$posttype_coupon}_posts_custom_column", array( 'WCRACouponTab', 'cbcouponrefer_columndisplay' ), 20, 2 );  // show coupon names in column
		add_filter( "manage_edit-{$posttype_coupon}_sortable_columns", array( 'WCRACouponTab', 'cbcouponrefer_columnsort' ) );  //add coupon column as sort column
		/*** end ***/

		/*** order related shortcodes ***/
		$posttype = "shop_order";
		// edit order table
		add_filter( "manage_edit-{$posttype}_columns", array( $this, 'wcra_column_set' ), 20, 1 );
		add_action( "manage_{$posttype}_posts_custom_column", array( $this, 'wcra_column_display' ), 20, 2 );
		//add_filter( "manage_edit-{$posttype}_sortable_columns", array($this,'wcra_column_sort') );

		add_action( 'add_meta_boxes', array( $this, 'wcra_add_meta_boxes' ) );

		if ( $this->settings_api->get_option( 'cborderbycoupon_show_affiliator_info', 'cbxwoocouponreferral_settings', 'on' ) == 'on' ) {
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'wcra_referral_details_after_order_table' ), 10, 1 );
		}

		/*** end ***/

		//woocommerce hooks ended

		// add short code
		add_shortcode( 'cbxwoocouponreferral', array( $this, 'cbx_show_coupon_with_shortcode' ) );
		add_shortcode( 'wcratop', array( $this, 'wcratop_callback' ) );

		//affiliator listing page
		add_filter( 'set-screen-option', array( $this, 'wcra_set_option_affiliators' ), 10, 3 );
		add_filter( 'set-screen-option', array( $this, 'wcra_set_option_permonth' ), 10, 3 );

		//user profile fields show and update from admin panel
		add_action( 'show_user_profile', array( $this, 'wcra_show_extra_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'wcra_show_extra_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'wcra_save_extra_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'wcra_save_extra_profile_fields' ) );

		//widgets
		add_action( 'widgets_init', array( $this, 'wcratop_widget' ) );
		//dashboard widgets
		add_action( 'wp_dashboard_setup', array( $this, 'wcra_add_dashboard_widget' ) );

		//help 3rd party plugins to init
		do_action( 'cbxwoocouponreferral_init' );
		//wcra error dump in activation. used for debug purpose only
		//add_action('activated_plugin', array($this, 'wcra_activation_error'), 10);

	}// end of construct


	/**
	 * Refister Widgets
	 */
	public function wcratop_widget() {

		register_widget( 'WCRATopWidget' );

	}

	/**
	 * Setup Dasboard widgets
	 */
	public function  wcra_add_dashboard_widget() {

		$user = wp_get_current_user();


		$monthly = $this->settings_api->get_option( 'wcra_top_affiliator_dashboard_show', 'cbxwoocouponreferral_dashboard_widget', 'on' );
		$top     = $this->settings_api->get_option( 'wcra_monthly_dashboard_show', 'cbxwoocouponreferral_dashboard_widget', 'on' );

		//$monthly_user_role = $this->settings_api->get_option('wcra_monthly_dashboard_usergroup','cbxwoocouponreferral_dashboard_widget', 'administrator');
		//$top_user_role     = $this->settings_api->get_option('wcra_top_affiliator_dashboard_usergroup','cbxwoocouponreferral_dashboard_widget', 'administrator');

		$monthly_user_role = $this->settings_api->get_option( 'wcra_monthly_dashboard_usergroup', 'cbxwoocouponreferral_dashboard_widget', array( 'administrator' ) );
		$top_user_role     = $this->settings_api->get_option( 'wcra_top_affiliator_dashboard_usergroup', 'cbxwoocouponreferral_dashboard_widget', array( 'administrator' ) );

		if ( $top == 'on' && array_intersect( $top_user_role, $user->roles ) ) {
			wp_add_dashboard_widget( 'wcratop_affiliator_widget', __( 'WCRA Top Affiliator', 'cbxwoocouponreferral' ), array( $this, 'wcratop_affiliator_widget' ), array( $this, 'wcratop_affiliator_widget_handle' ) );
		}

		if ( $monthly == 'on' && array_intersect( $monthly_user_role, $user->roles ) ) {
			wp_add_dashboard_widget( 'wcra_monthly_dashboard_widget', __( 'WCRA Monthly Graph', 'cbxwoocouponreferral' ), array( $this, 'wcra_monthly_dashboard_widget' ), array( $this, 'wcra_monthly_dashboard_widget_handle' ) );
		}

		//wp_add_dashboard_widget('wcra_daily_dashboard_widget', __('WCRA Daily Graph','cbxwoocouponreferral'), array($this,'wcra_daily_dashboard_widget'), array($this,'wcra_daily_dashboard_widget_handle'));

	}

	/**
	 * Top affiliator Dashboard widget
	 */
	public function wcratop_affiliator_widget() {

		if ( ! $widget_options = get_option( 'wcratop_affiliator_dashboard_widget_options' ) ) {
			$widget_options = array();
		}

		$type     = isset( $widget_options['type'] ) ? $widget_options['type'] : 'month';
		$count    = isset( $widget_options['count'] ) ? $widget_options['count'] : 10;
		$order_by = isset( $widget_options['order_by'] ) ? $widget_options['order_by'] : 'total_earning';
		$order    = isset( $widget_options['order'] ) ? $widget_options['order'] : 'DESC';

		$data['count']    = $count;
		$data['type']     = $type;
		$data['order_by'] = $order_by;
		$data['order']    = $order;

		$output = $this->wcratop_callback( $data );

		echo "<div class='wcra_user_class_wrap'><label style='background:#ccc;'>$output</label></div>";
	}

	/**
	 * Top affiliator Dashboard widget habdeller
	 */
	public function wcratop_affiliator_widget_handle() {
		# get saved data
		if ( ! $widget_options = get_option( 'wcratop_affiliator_dashboard_widget_options' ) ) {
			$widget_options = array();
		}

		# process update
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST['wcratop_affiliator_dashboard_widget_options_type'] ) && isset( $_POST['wcratop_affiliator_dashboard_widget_options_count'] ) ) {

			$widget_options['type']     = esc_attr( $_POST['wcratop_affiliator_dashboard_widget_options_type'] );
			$widget_options['count']    = intval( $_POST['wcratop_affiliator_dashboard_widget_options_count'] );
			$widget_options['order_by'] = esc_attr( $_POST['wcratop_affiliator_dashboard_widget_options_order_by'] );
			$widget_options['order']    = esc_attr( $_POST['wcratop_affiliator_dashboard_widget_options_order'] );
			update_option( 'wcratop_affiliator_dashboard_widget_options', $widget_options );
		}

		# set defaults
		if ( ! isset( $widget_options['type'] ) ) {
			$widget_options['type'] = 'month';
		}
		if ( ! isset( $widget_options['count'] ) ) {
			$widget_options['count'] = 10;
		}
		if ( ! isset( $widget_options['order_by'] ) ) {
			$widget_options['order_by'] = 'total_earning';
		}
		if ( ! isset( $widget_options['order'] ) ) {
			$widget_options['order'] = 'DESC';
		}

		?>


		<p>
			<label for="type"><?php _e( 'Type:', 'cbxwoocouponreferral' ) ?></label>
			<select name="wcratop_affiliator_dashboard_widget_options_type">
				<option
					value="month" <?php selected( $widget_options['type'], 'month', true ) ?>><?php _e( 'Month', 'cbxwoocouponreferral' ); ?></option>
				<option
					value="year" <?php selected( $widget_options['type'], 'year', true ) ?>><?php _e( 'Year', 'cbxwoocouponreferral' ); ?></option>
			</select>
		</p>

		<p>
			<label for="count"><?php _e( 'Count:', 'cbxwoocouponreferral' ) ?></label>
			<input type="text" name="wcratop_affiliator_dashboard_widget_options_count"
				   value="<?php echo $widget_options['count']; ?>">
		</p>

		<p>
			<label for="type"><?php _e( 'Order By:', 'cbxwoocouponreferral' ) ?></label>
			<select name="wcratop_affiliator_dashboard_widget_options_order_by">
				<option
					value="total_earning" <?php selected( $widget_options['order_by'], 'total_earning', true ); ?> ><?php _e( 'Total Earning', 'cbxwoocouponreferral' ); ?></option>
				<option
					value="total_amount" <?php selected( $widget_options['order_by'], 'total_amount', true ); ?> ><?php _e( 'Total Amount', 'cbxwoocouponreferral' ); ?></option>
				<option
					value="total_referred" <?php selected( $widget_options['order_by'], 'total_referred', true ); ?> ><?php _e( 'Total Referred', 'cbxwoocouponreferral' ); ?></option>
			</select>
		</p>

		<p>
			<label for="type"><?php _e( 'Order:', 'cbxwoocouponreferral' ) ?></label>
			<select name="wcratop_affiliator_dashboard_widget_options_order">
				<option
					value="desc" <?php selected( $widget_options['order'], 'desc', true ); ?> ><?php _e( 'DESC', 'cbxwoocouponreferral' ); ?></option>
				<option
					value="asc" <?php selected( $widget_options['order'], 'asc', true ); ?> ><?php _e( 'ASC', 'cbxwoocouponreferral' ); ?></option>
			</select>
		</p>

	<?php }

	/*public function wcra_daily_dashboard_widget() {
        # get saved data
        if( !$widget_options = get_option( 'wcra_daily_dashboard_widget_options' ) )
            $widget_options = array( );

        # default output
        $output = sprintf(
            '<h2 style="text-align:right">%s</h2>',
            __( 'Please, configure the widget ☝' )
        );

        # check if saved data contains content
        $saved_feature_post = isset( $widget_options['wcra_user'] )
            ? $widget_options['feature_post'] : false;

        # custom content saved by control callback, modify output
        if( $saved_feature_post ) {
            $post = get_post( $saved_feature_post );
            if( $post ) {
                $content = do_shortcode( html_entity_decode( $post->post_content ) );
                $output = "<h2>{$post->post_title}</h2><p>{$content}</p>";
            }
        }
        echo "<div class='wcra_user_class_wrap'><label style='background:#ccc;'>$output</label></div>";
    }*/

	/*public function wcra_daily_dashboard_widget_handle()
    {
        # get saved data
        if( !$widget_options = get_option( 'wcra_daily_dashboard_widget_options' ) )
            $widget_options = array( );

        # process update
        if( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST['wcra_monthly_dashboard_widget_options'] ) ) {
            # minor validation
            $widget_options['wcra_user'] = absint( $_POST['wcra_daily_dashboard_widget_options']['wcra_user'] );
            # save update
            update_option( 'wcra_daily_dashboard_widget_options', $widget_options );
        }

        # set defaults
        if( !isset( $widget_options['wcra_user'] ) ) $widget_options['wcra_user'] = '';

        <label for="role"><?php //_e('Role:') ?></label>

        <select name="role" id="role">
            <?php //wp_dropdown_roles('editor'); ?>
        </select>
*/


	/**
	 * Top Monthly Dashboard widget
	 */
	public function wcra_monthly_dashboard_widget() {

		echo "<div id='cbcouponreferperyear'></div>";
	}

	/**
	 * Top Monthly Dashboard widget handler
	 */
	public function wcra_monthly_dashboard_widget_handle() {
	}

	/**
	 * @param $user
	 */
	public function wcra_show_extra_profile_fields( $user ) { ?>

		<h3><?php _e( 'WCRA Profile Information', 'cbxwoocouponreferral' ); ?></h3>

		<table class="form-table">

			<tr>
				<th><label for="wcra_user_contact_phone"><?php _e( 'Telephone/Phone', 'cbxwoocouponreferral' ); ?></label></th>
				<td>
					<input type="text" name="wcra_user_contact_phone" id="wcra_user_contact_phone"
						   value="<?php echo esc_attr( get_user_meta( $user->ID, 'wcra_user_contact_phone', true ) ); ?>"
						   class="regular-text wcra_user_contact_phone" />
                    <span class="description"><?php _e( 'Please enter Telephone/Phone.', 'cbxwoocouponreferral' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="wcra_user_paypal_email"><?php _e( 'Paypal Email', 'cbxwoocouponreferral' ); ?></label></th>
				<td>
					<input type="text" name="wcra_user_paypal_email" id="wcra_user_paypal_email"
						   value="<?php echo esc_attr( get_user_meta( $user->ID, 'wcra_user_paypal_email', true ) ); ?>"
						   class="regular-text wcra_user_paypal_email" />
						<span class="description"><?php _e( 'Please enter Paypal Email.', 'cbxwoocouponreferral' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="wcra_user_bank_accname"><?php _e( 'Bank Account Name.', 'cbxwoocouponreferral' ); ?></label></th>
				<td>
					<input type="text" name="wcra_user_bank_accname" id="wcra_user_bank_accname"
						   value="<?php echo esc_attr( get_user_meta( $user->ID, 'wcra_user_bank_accname', true ) ); ?>"
						   class="regular-text wcra_user_bank_accname" />
                    <span class="description"><?php _e( 'Please enter Bank Account Name.', 'cbxwoocouponreferral' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="wcra_user_bank_accno"><?php _e( 'Bank Account No.', 'cbxwoocouponreferral' ); ?></label></th>
				<td>
					<input type="text" name="wcra_user_bank_accno" id="wcra_user_bank_accno"
						   value="<?php echo esc_attr( get_user_meta( $user->ID, 'wcra_user_bank_accno', true ) ); ?>"
						   class="regular-text wcra_user_bank_accno" />
                    <span class="description"><?php _e( 'Please enter Bank Account No.', 'cbxwoocouponreferral' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label	for="wcra_user_branch_name"><?php _e( 'Bank Branch Name.', 'cbxwoocouponreferral' ); ?></label></th>
				<td>
					<input type="text" name="wcra_user_branch_name" id="wcra_user_branch_name"
						   value="<?php echo esc_attr( get_user_meta( $user->ID, 'wcra_user_branch_name', true ) ); ?>"
						   class="regular-text wcra_user_branch_name" />
                    <span class="description"><?php _e( 'Please enter Bank Branch Name.', 'cbxwoocouponreferral' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="wcra_user_sort_code"><?php _e( 'Sort Code', 'cbxwoocouponreferral' ); ?></label></th>
				<td>
					<input type="text" name="wcra_user_sort_code" id="wcra_user_sort_code"
						   value="<?php echo esc_attr( get_user_meta( $user->ID, 'wcra_user_sort_code', true ) ); ?>"
						   class="regular-text wcra_user_sort_code" />
					<span class="description"><?php _e( 'Please enter Sort Code.', 'cbxwoocouponreferral' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="wcra_user_iban"><?php _e( 'IBAN', 'cbxwoocouponreferral' ); ?></label></th>
				<td>
					<input type="text" name="wcra_user_iban" id="wcra_user_contact_phone"
						   value="<?php echo esc_attr( get_user_meta( $user->ID, 'wcra_user_iban', true ) ); ?>"
						   class="regular-text wcra_user_iban" />
					<span class="description"><?php _e( 'Please enter IBAN.', 'cbxwoocouponreferral' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="wcra_user_bic"><?php _e( 'BIC/SWIFT', 'cbxwoocouponreferral' ); ?></label></th>
				<td>
					<input type="text" name="wcra_user_bic" id="wcra_user_bic"
						   value="<?php echo esc_attr( get_user_meta( $user->ID, 'wcra_user_bic', true ) ); ?>"
						   class="regular-text wcra_user_bic" />
					<span class="description"><?php _e( 'Please enter BIC/SWIFT.', 'cbxwoocouponreferral' ); ?></span>
				</td>
			</tr>

		</table>

	<?php }

	/**
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function wcra_save_extra_profile_fields( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		update_user_meta( $user_id, 'wcra_user_contact_phone', esc_attr( $_POST['wcra_user_contact_phone'] ) );
		update_user_meta( $user_id, 'wcra_user_paypal_email', esc_attr( $_POST['wcra_user_paypal_email'] ) );
		update_user_meta( $user_id, 'wcra_user_bank_accname', esc_attr( $_POST['wcra_user_bank_accname'] ) );
		update_user_meta( $user_id, 'wcra_user_bank_accno', esc_attr( $_POST['wcra_user_bank_accno'] ) );
		update_user_meta( $user_id, 'wcra_user_branch_name', esc_attr( $_POST['wcra_user_branch_name'] ) );
		update_user_meta( $user_id, 'wcra_user_sort_code', esc_attr( $_POST['wcra_user_sort_code'] ) );
		update_user_meta( $user_id, 'wcra_user_iban', esc_attr( $_POST['wcra_user_iban'] ) );
		update_user_meta( $user_id, 'wcra_user_bic', esc_attr( $_POST['wcra_user_bic'] ) );
	}

	/**
	 * Display table of referral info in view order page
	 *
	 * @param $order
	 */
	public function wcra_referral_details_after_order_table( $order ) {
		echo sprintf( '<h2>%s</h2>', __( 'Affiliator Contact Info', 'cbxwoocouponreferral' ) );
		$this->wcra_referral_info_user( $order->id );
	}

	/**
	 * Display metabox in order details page
	 */
	public function wcra_add_meta_boxes() {
		add_meta_box( 'woocommerce-wcra-referral-info', __( 'CBX WCRA Referral Info', 'cbxwoocouponreferral'), array( $this, 'wcra_referral_info_admin' ), 'shop_order', 'normal', 'default' );
	}

	/**
	 * Show History in order edit page for admin
	 *
	 * @param $post
	 */
	public function wcra_referral_info_user( $order_id ) {
		global $wpdb;
		$order            = new WC_Order( $order_id );
		$wcra_data        = array();
		$couponitems      = $order->get_used_coupons();
		$posttable_name   = $wpdb->prefix . "posts";
		$coupontable_name = $this->get_cborderbycoupon_table_name();

		if ( is_array( $couponitems ) && ! empty( $couponitems ) ) {

			foreach ( $couponitems as $couponitem ) {

				$sql = $wpdb->prepare( "SELECT ID FROM $posttable_name WHERE post_type = %s AND post_title = %s ORDER BY post_date DESC LIMIT 1", 'shop_coupon', $couponitem );

				$couponid = $wpdb->get_results( $sql, ARRAY_A );

				if ( is_array( $couponid ) && ! empty( $couponid ) ) {
					$sql       = $wpdb->prepare( "SELECT * FROM $coupontable_name WHERE order_id = %d ", (int) $order_id );
					$wcra_data = $wpdb->get_results( $sql );
				}// if coupon id found
			}// end of foreach every coupon
		}// if order used any coupon
		else {
			$wcra_data = null;
		} ?>
		<div class="postbox">
			<div class="inside">
				<?php if ( isset( $wcra_data ) && $wcra_data != null ) { ?>
					<table class="table widefat tablesorter display">
						<thead>
						<tr>
							<th style="text-align:center;"> <?php _e( "Coupon", "cbxwoocouponreferral" ); ?></th>
							<th style="text-align:center;"> <?php _e( "Refer User", "cbxwoocouponreferral" ); ?></th>
							<th style="text-align:center;"> <?php _e( "Email", "cbxwoocouponreferral" ); ?></th>
							<th style="text-align:center;"> <?php _e( "Phone", "cbxwoocouponreferral" ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ( $wcra_data as $data ) {
							$u         = $data->user_id;
							$user_info = get_userdata( $u );
							?>
							<tr>
								<td style="text-align:center;"><?php echo $data->coupon_info; ?></td>
								<td style="text-align:center;"><?php echo $user_info->display_name; ?></td>
								<td style="text-align:center;"><?php echo $user_info->user_email; ?></td>
								<td style="text-align:center;"><?php echo get_user_meta( $u, 'wcra_user_contact_phone', true ); ?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				<?php } else { ?>
					<p><?php _e( 'No Referral Data Available', 'cbxwoocouponreferral' ); ?></p>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Show History in order edit page for admin
	 *
	 * @param $post
	 */
	public function wcra_referral_info_admin( $post ) {
		global $wpdb;
		$order_id         = $post->ID;
		$order            = new WC_Order( $order_id );
		$wcra_data        = array();
		$couponitems      = $order->get_used_coupons();
		$posttable_name   = $wpdb->prefix . "posts";
		$coupontable_name = $this->get_cborderbycoupon_table_name();

		if ( is_array( $couponitems ) && ! empty( $couponitems ) ) {

			foreach ( $couponitems as $couponitem ) {

				$sql      = $wpdb->prepare( "SELECT ID FROM $posttable_name WHERE post_type = %s AND post_title = %s ORDER BY post_date DESC LIMIT 1", 'shop_coupon', $couponitem );
				$couponid = $wpdb->get_results( $sql, ARRAY_A );

				if ( is_array( $couponid ) && ! empty( $couponid ) ) {
					$sql       = $wpdb->prepare( "SELECT * FROM $coupontable_name WHERE order_id = %d ", (int) $order_id );
					$wcra_data = $wpdb->get_results( $sql );
				}// if coupon id found
			}// end of foreach every coupon
		}// if order used any coupon
		else {
			$wcra_data = null;
		} ?>

		<div class="postbox">
			<div class="inside">
				<?php if ( isset( $wcra_data ) && $wcra_data != null ) { ?>
					<table class="table widefat tablesorter display">
						<thead>
						<tr>
							<th style="text-align:center;"> <?php _e( "Coupon", "cbxwoocouponreferral" ); ?></th>
							<th style="text-align:center;"> <?php _e( "Refer User", "cbxwoocouponreferral" ); ?></th>
							<th style="text-align:center;"> <?php _e( "User Commission", "cbxwoocouponreferral" ); ?></th>
							<th style="text-align:center;"> <?php echo sprintf( __( "User Earning(%s)", "cbxwoocouponreferral" ), get_woocommerce_currency_symbol() ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ( $wcra_data as $data ) {
							$page = 'wcrapermonth';
							$u    = $data->user_id;
							$c    = $data->coupon_id;


							$user_info          = get_userdata( $u );
							$admin_traverse_url = add_query_arg( compact( 'page', 'u', 'c' ), admin_url( 'admin.php' ) );
							?>
							<tr>
								<td style="text-align:center;"><?php echo '<a target="_blank" href="' . admin_url( 'post.php?post=' . $data->coupon_id . '&action=edit' ) . '">' . $data->coupon_info; ?></a></td>
								<td style="text-align:center;"><?php echo '<a target="_blank" href="' . $admin_traverse_url . '">' . $user_info->display_name; ?></a></td>
								<td style="text-align:center;"><?php echo $data->user_percentage . ( ( $data->user_percentage_type == 1 ) ? '%' : '(' . __( 'Fixed', 'cbxwoocouponreferral' ) . ')' ); ?></td>
								<td style="text-align:center;"><?php echo wcra_price( $data->user_earning ); ?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				<?php } else { ?>
					<p><?php _e( 'No Referral data available', 'cbxwoocouponreferral' ); ?></p>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Filter callback 'wcra_column_set'
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function wcra_column_set( $columns ) {
		$columns['order_referred'] = __( 'Referred By', 'cbxwoocouponreferral' );

		return $columns;
	}

	/**
	 * Action 'wcra_column_display' callback
	 *
	 * @param $column_name
	 * @param $post_id
	 */
	public function wcra_column_display( $column_name, $post_id ) {
		if ( 'order_referred' != $column_name ) {
			return;
		}

		global $wpdb;
		$order_id       = $post_id;
		$order          = new WC_Order( $order_id );
		$couponitems    = $order->get_used_coupons();
		$posttable_name = $wpdb->prefix . "posts";

		if ( is_array( $couponitems ) && ! empty( $couponitems ) ) {
			echo '<ul>';
			foreach ( $couponitems as $couponitem ) {

				$sql      = $wpdb->prepare( "SELECT ID FROM $posttable_name WHERE post_type = %s AND post_title = %s ORDER BY post_date DESC LIMIT 1", 'shop_coupon', $couponitem );
				$couponid = $wpdb->get_results( $sql, ARRAY_A );

				if ( is_array( $couponid ) && ! empty( $couponid ) ) {
					// user_id for database
					$coupon_refer_user_id = get_post_meta( (int) $couponid[0]['ID'], 'cbx_coupon_refer_userid', true );

					if ( $coupon_refer_user_id != false ) {
						$page      = 'wcrapermonth';
						$u         = $coupon_refer_user_id;
						$c         = (int) $couponid[0]['ID'];
						$user_info = get_userdata( $u );

						$url = add_query_arg( compact( 'page', 'u', 'c' ), admin_url( 'admin.php' ) );
						echo '<li><a target="_blank" href="' . $url . '">' . $user_info->display_name . ' ( ' . $couponitem . ' )</a></li>';

					}// if user id found
				}// if coupon id found
			}
			echo '<ul>';
			// end of foreach every coupon
		} else {
			_e( 'N/A', 'cbxwoocouponreferral' );
		}// if order used any coupon
	}

	/**
	 * Filter 'wcra_column_sort' callback
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function wcra_column_sort( $columns ) {
		$columns['order_referred'] = 'order_sales';

		return $columns;
	}

	/*
    public function wcra_activation_error(){
        update_option('wcra_activation_error',  ob_get_contents());
    }
    */

	/**
	 * Add admin menus for this plugin
	 */
	public function create_admin_menu() {
		//$page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null
		add_menu_page( __( 'CBX Coupon Referral Stats', 'cbxwoocouponreferral' ), __( 'CBX WCRA', 'cbxwoocouponreferral' ), 'manage_options', 'cbxcouponreferrral', array( $this, 'cbcoupon_refer_display_stats' ), plugins_url( 'includes/icons/menu-icon.png', __FILE__ ) );

		//$parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = ''
		add_submenu_page( 'cbxcouponreferrral', __( 'Coupon Referral Settings', 'cbxwoocouponreferral' ), __( 'Settings', 'cbxwoocouponreferral' ), 'manage_options', 'cbxwoocommercecouponsettings', array( $this, 'cbx_woocommerce_coupon_display' ) );

		//addons menu
		add_submenu_page( 'cbxcouponreferrral', __( 'WCRA ADD-ons:', 'cbxwoocouponreferral' ), __( 'Add-ons', 'cbxwoocouponreferral' ), 'manage_options', 'cbxwoocouponreferral_addons', array( $this, 'display_plugin_admin_addons' ), 'dashicons-chart-line', '58.16' );

		//adding log page menu
		$hook_affiliators = add_submenu_page( 'cbxcouponreferrral', __( 'Affiliators', 'cbxwoocouponreferral' ), __( 'Affiliators', 'cbxwoocouponreferral' ), 'manage_options', 'wcraaffiliators', array( $this, 'wcra_affiliators' ) );

		//add screen option for affiliators wp list table
		add_action( "load-$hook_affiliators", array( $this, 'wcra_add_option_affiliators' ) );

		//adding a custom page
		$hook_permonth = add_submenu_page( 'cbxcouponreferrral', __( 'Per Month Stat', 'cbxwoocouponreferral' ), __( 'Per Month Stat', 'cbxwoocouponreferral' ), 'manage_options', 'wcrapermonth', array( $this, 'wcra_permonth' ) );

		//add screen option for affiliators wp list table
		add_action( "load-$hook_permonth", array( $this, 'wcra_add_option_pmlog' ) );
	}


	/**
	 * Check the status of a plugin. (https://katz.co/simple-plugin-status-wordpress/)
	 *
	 * @param string $location Base plugin path from plugins directory.
	 *
	 * @return int 1 if active; 2 if inactive; 0 if not installed
	 */
	public function get_plugin_status( $location = '' ) {
		if ( is_plugin_active( $location ) ) {
			return array(
				'status'   => 1,
				'msg'      => __( 'Active and Installed', 'cbxwoocouponreferral' ),
				'btnclass' => 'button button-primary'
			);
		}

		if ( ! file_exists( trailingslashit( WP_PLUGIN_DIR ) . $location ) ) {
			return array(
				'status'   => 0,
				'msg'      => __( 'Not Installed or Active', 'cbxwoocouponreferral' ),
				'btnclass' => 'button'
			);
		}

		if ( is_plugin_inactive( $location ) ) {
			return array(
				'status'   => 2,
				'msg'      => __( 'Installed but Inactive', 'cbxwoocouponreferral' ),
				'btnclass' => 'button'
			);
		}
	}

	/**
	 * Display Available addons
	 */
	public function display_plugin_admin_addons() {
		//check wcra addon acitavtion status
		$wcra_addon = $this->get_plugin_status( 'cbxwoocouponreferraladdon/cbxwoocouponreferraladdon.php' );
		//check wcra payment addon acitavtion status
		$wcra_payment = $this->get_plugin_status( 'cbxwoocouponreferralpayment/cbxwoocouponreferralpayment.php' );
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php _e( 'WCRA: Add-ons', 'cbxwoocouponreferral' ); ?></h2>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<!-- main content -->
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<div class="postbox">
								<div class="inside">
									<?php
									$wcra_admin_url = plugin_dir_url( __FILE__ );

									$wcra_addon_thumb   = $wcra_admin_url . 'includes/images/addon.png';
									$wcra_payment_thumb = $wcra_admin_url . 'includes/images/payment.png';
									$idea_thumb         = $wcra_admin_url . 'includes/images/idea.png';
									?>
									<div class="wcra_addon">
										<a href="http://codeboxr.com/product/wcra-email-alert-export-addon/"
										   target="_blank"><img src="<?php echo $wcra_addon_thumb; ?>" alt="addon"></a>

										<p class=wcra_addonstatus"><a
												href="http://codeboxr.com/product/wcra-email-alert-export-addon/"
												class="<?php echo $wcra_addon['btnclass'] ?>"><?php echo $wcra_addon['msg']; ?></a>
										</p>
									</div>
									<div class="wcra_addon">
										<a href="http://codeboxr.com/product/wcra-payment-addon/"
										   target="_blank"><img
												src="<?php echo $wcra_payment_thumb; ?>" alt="payment"></a>

										<p class="wcra_addonstatus"><a
												href="http://codeboxr.com/product/wcra-payment-addon/"
												class="<?php echo $wcra_payment['btnclass'] ?>"><?php echo $wcra_payment['msg']; ?></a>
										</p>
									</div>
									<div class="wcra_addon"><a href="http://codeboxr.com/contact-us"
															   target="_blank"><img src="<?php echo $idea_thumb; ?>"
																					alt="idea"></a></div>
									<div class="cbxclearfix"></div>
								</div>
								<!-- .inside -->
							</div>
							<!-- .postbox -->
						</div>
						<!-- .meta-box-sortables .ui-sortable -->
					</div>
					<!-- post-body-content -->
					<?php include( 'includes/sidebar.php' ); ?>
				</div>
				<!-- #post-body .metabox-holder .columns-2 -->
				<br class="clear">
			</div>
			<!-- #poststuff -->
		</div> <!-- .wrap -->
		<script type="text/javascript">

			jQuery(document).ready(function ($) {
				//if need any js code here
			});

		</script>

	<?php }

	/**
	 * Add screen option for affiliators page wp list table
	 */
	public function wcra_add_option_affiliators() {
		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Affiliators', 'cbxwoocouponreferral' ),
			'default' => 50,
			'option'  => 'affiliators_per_page'
		);
		add_screen_option( $option, $args );
	}

	/**
	 * Set options for affiliators.
	 *
	 * @param $status
	 * @param $option
	 * @param $value
	 *
	 * @return mixed
	 */
	public function wcra_set_option_affiliators( $status, $option, $value ) {
		if ( 'affiliators_per_page' == $option ) {
			return $value;
		}

		return $status;
	}


	/**
	 * Add screen option for per month stat page wp list table
	 */
	function wcra_add_option_pmlog() {
		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Per Month Stat', 'cbxwoocouponreferral' ),
			'default' => 50,
			'option'  => 'permonth_per_page'
		);
		add_screen_option( $option, $args );
	}

	/**
	 * Set options for per month
	 *
	 * @param $status
	 * @param $option
	 * @param $value
	 *
	 * @return mixed
	 */
	public function wcra_set_option_permonth( $status, $option, $value ) {
		if ( 'permonth_per_page' == $option ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Show wordpress error notice if Woocommerce not found (inspired from Dokan plugin)
	 *
	 * @since 2.3
	 */
	function add_woocommerce_activation_notice() {
		echo '<div class="error"><p>' . sprintf( __( '<strong>CBX Woo Coupon Referral Affiliate(WCRA)</strong> requires %sWoocommerce%s to be installed & activated!', 'cbxwoocouponreferral' ), '<a target="_blank" href="https://wordpress.org/plugins/woocommerce/">', '</a>' ) . '</p></div>';
	}

	/**
	 * Called when plugin activated
	 */
	public static function install_plugin() {
		//check if woocommerce is installed
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( ! ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ) ) {
			deactivate_plugins( basename( __FILE__ ) );
			wp_die( sprintf( __( '<strong>CBX Woo Coupon Referral Affiliate(WCRA)</strong> requires %sWoocommerce%s to be installed & activated!', 'cbxwoocouponreferral' ), '<a target="_blank" href="https://wordpress.org/plugins/woocommerce/">', '</a>' ) );
		}

		//add the default options
		$check_user_group = ( get_option( 'cbxwoocouponreferral_settings' ) );

		if ( is_array( $check_user_group['cborderbycoupon_usergroupsr'] ) && empty( $check_user_group['cborderbycoupon_usergroupsr'] ) ) {
			$check_user_group['cborderbycoupon_usergroupsr'] = 'administrator';
		} else {
			$check_user_group['cborderbycoupon_usergroupsr'] = 'administrator';
		}
		update_option( 'cbxwoocouponreferral_settings', $check_user_group );

		//install the necessary tables
		WCRAHelper:: cborderbycoupon_install_table(); //create table for this plugin
		//create the plugin pages
		WCRAHelper:: cborderbycoupon_create_pages(); //create the shortcode page
	}

	/**
	 * call when uninstall plugin need to deregister schedule event
	 */
	public static function uninstall_plugin() {
	}

	/**
	 * Register and enqueues  JavaScript files for admin
	 *
	 * @since    1.0.0
	 */
	public function admin_enqueue_scripts( $hook ) {
		global $post;

		$getdate   = getdate();
		$default_year = $getdate["year"];

		$ajax_nonce = wp_create_nonce( "cbxwoocouponreferral_nonce" );

		//registering necessary js for later use
		//for woocommerce coupon
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );

		wp_register_script( 'wcra-autocomplete', plugins_url( '/includes/js/wcra_autocomplete.js', __FILE__ ), array( 'jquery', 'jquery-ui-autocomplete' ), CBXWOOCOUPONREFFERAL_VERSION );
		wp_register_style( 'wcra-coupon', plugins_url( '/includes/css/cbxwoocouponreferral_coupon.css', __FILE__ ), array(), CBXWOOCOUPONREFFERAL_VERSION );

		wp_register_style( $this->plugin_slug . '-ui-stylesadmin', plugins_url( 'includes/css/jquery-ui-1.10.3.flat.min.css', __FILE__ ), array(), CBXWOOCOUPONREFFERAL_VERSION );
		//wp_enqueue_script( 'jquery' );
		//wp_enqueue_script( 'jquery-ui-autocomplete' );

		//for settings
		wp_register_script( 'cbxchosenwcrajs', plugins_url( '/includes/js/chosen.jquery.min.js', __FILE__ ), array( 'jquery' ), '1.1.0', false );
		//for admin pages register
		wp_register_script( 'wcrakickgjsapi', 'http://www.google.com/jsapi?key=AIzaSyDGv1vFUzN3mAjpgewSY_6zIoQP4Cjjjgo', array(), CBXWOOCOUPONREFFERAL_VERSION, false );
		wp_register_script( $this->plugin_slug . '-chart-cb', plugins_url( '/includes/js/chartkick.js', __FILE__ ), array( 'jquery' ), CBXWOOCOUPONREFFERAL_VERSION );

		wp_register_script( $this->plugin_slug . '-print-cb', plugins_url( '/includes/js/jquery.PrintArea.js', __FILE__ ), array( 'jquery' ), CBXWOOCOUPONREFFERAL_VERSION );
		wp_register_script( $this->plugin_slug . '-customscript', plugins_url( '/includes/js/wcra_back.js', __FILE__ ), array( 'jquery' ), CBXWOOCOUPONREFFERAL_VERSION );
		wp_register_script( $this->plugin_slug . '-dashboardscript', plugins_url( '/includes/js/wcra_dashboard.js', __FILE__ ), array( 'jquery' ), CBXWOOCOUPONREFFERAL_VERSION );

		//end of registering js

		//registering necessary css for later use
		//for settings
		wp_register_style( 'cbxchosenwcracss', plugins_url( '/includes/css/chosen.min.css', __FILE__ ), array(), '1.1.0', 'all' );
		wp_register_style( $this->plugin_slug . '-settingstyle', plugins_url( '/includes/css/cbxwoocouponreferral_settings.css', __FILE__ ), array( 'cbxchosenwcracss' ), CBXWOOCOUPONREFFERAL_VERSION );
		//for other pages
		wp_register_style( $this->plugin_slug . '-customstyle', plugins_url( '/includes/css/cbxwoocouponreferral_back.css', __FILE__ ), array(), CBXWOOCOUPONREFFERAL_VERSION );
		//end of registering css

		//for coupon edit page
		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			if ( 'shop_coupon' === $post->post_type ) {
				//enqueue js
				wp_enqueue_style( $this->plugin_slug . '-ui-stylesadmin' );
				wp_enqueue_style( 'wcra-coupon' );

				wp_enqueue_script( 'wcra-autocomplete' );
				wp_localize_script(
					'wcra-autocomplete', 'wcraac', array(
					'ajaxurl'     => admin_url( 'admin-ajax.php' ),
					'nonce'       => $ajax_nonce,
					'userediturl' => admin_url( 'user-edit.php?user_id=' ),
				)
				);


			}
		}

		if ( get_current_screen()->id == 'cbx-wcra_page_cbxwoocouponreferral_addons' ) {
			//enqueue css
			wp_enqueue_style( $this->plugin_slug . '-customstyle' );
		}

		//for wcra settings page
		if ( get_current_screen()->id == 'cbx-wcra_page_cbxwoocommercecouponsettings' ) {
			//enqueue css
			wp_enqueue_style( 'cbxchosenwcracss' );
			wp_enqueue_style( $this->plugin_slug . '-settingstyle' );
			//enqueue js
			wp_enqueue_script( 'cbxchosenwcrajs' );
		}

		//for wcra admin page
		if ( get_current_screen()->id == 'toplevel_page_cbxcouponreferrral' ) {

			//initial data is not pulled using ajax
			//$cborder_data = wp_json_encode($this->wcra_per_year_data()); //optimization needed

			//enqueue css
			wp_enqueue_style( $this->plugin_slug . '-customstyle' );
			//enqueue js
			//wp_enqueue_script('jquery');
			//wp_enqueue_script('jquery-ui-autocomplete');
			wp_enqueue_script( 'wcrakickgjsapi' );
			wp_enqueue_script( $this->plugin_slug . '-chart-cb' );
			wp_enqueue_script( $this->plugin_slug . '-print-cb' );



			wp_localize_script(
				$this->plugin_slug . '-customscript', 'wcra', array(
					'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
					'nonce'                       => $ajax_nonce,
					'monthname'                   => self:: $monthname,
					'shortmonthname'              => self:: $shortmonthname,
					'wcraoo_header'               => __( 'Order Overview (including shipping & other costs) : Year', 'cbxwoocouponreferral' ),
					'wcraootable_month'           => __( 'Month', 'cbxwoocouponreferral' ),
					'wcraootable_amt'             => __( 'Order Amount', 'cbxwoocouponreferral' ),
					'wcraootable_number'          => __( 'Order Number', 'cbxwoocouponreferral' ),
					'wcraootable_earn'            => __( 'Earning', 'cbxwoocouponreferral' ),
					'wcraoagraph_caption'         => __( 'Orders by Reference', 'cbxwoocouponreferral' ),
					'wcraoagraph_upper_caption'   => __( 'All Orders', 'cbxwoocouponreferral' ),
					'wcraootable_all_amt'         => __( 'All Order Amount', 'cbxwoocouponreferral' ),
					'wcraootable_ref_amt'         => sprintf( __( 'Refer Order Amount(%s)', 'cbxwoocouponreferral' ), get_woocommerce_currency_symbol() ),
					'wcraootable_ref_number'      => __( 'Refer Order number', 'cbxwoocouponreferral' ),
					//'wcraootable_percentage'      => __('Percentage', 'cbxwoocouponreferral'),
					//'wcraootable_earn_total'      => sprintf(__( 'User Total Earning(%s) = ', 'cbxwoocouponreferral' ), get_woocommerce_currency_symbol()),
					'wcraootable_percentage'      => sprintf( __( 'User Earning(%s)', 'cbxwoocouponreferral' ), get_woocommerce_currency_symbol() ),
					'woocommerce_currency_symbol' => get_woocommerce_currency_symbol(),
					'error_msg'                   => __( 'No matching orders found', 'cbxwoocouponreferral' ),
					'mail_send'                   => __( 'Mail Sent Successfully!', 'cbxwoocouponreferral' ),
					//'default_value'               => $cborder_data,
					'default_year'               => $default_year,
				)
			);
			wp_enqueue_script( $this->plugin_slug . '-customscript' );
		}

		//for dashboard widgets
		if ( get_current_screen()->id == 'dashboard' && $hook == 'index.php' ) {

			//$cborder_data = wp_json_encode( $this->wcra_per_year_data() ); //opitmization
			//enqueue js

			wp_enqueue_script( 'wcrakickgjsapi' );
			wp_enqueue_script( $this->plugin_slug . '-chart-cb' );

			wp_localize_script(
				$this->plugin_slug . '-dashboardscript', 'wcra_dashboard', array(
					'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
					'nonce'                     => $ajax_nonce,
					'monthname'                 => self:: $monthname,
					'shortmonthname'            => self:: $shortmonthname,
					'wcraoagraph_caption'       => __( 'Orders by Reference', 'cbxwoocouponreferral' ),
					'wcraoagraph_upper_caption' => __( 'All Orders', 'cbxwoocouponreferral' ),
					//'default_value'             => $cborder_data,
					'default_year'               => $default_year,
				)
			);
			wp_enqueue_script( $this->plugin_slug . '-dashboardscript' );
		}
	}

	/**
	 * Register and enqueues  JavaScript files for public
	 *
	 * @since    1.0.0
	 */
	public function public_enqueue_scripts() {
		//register shortcode styles for use in the shortcode page/post later
		//wp_register_style($this->plugin_slug . '-ui-styles', plugins_url('includes/css/ui-lightness/jquery-ui.min.css', __FILE__), array(), CBXWOOCOUPONREFFERAL_VERSION);
		wp_register_style( $this->plugin_slug . '-customstyle', plugins_url( '/includes/css/cbxwoocouponreferral_front.css', __FILE__ ), array(), CBXWOOCOUPONREFFERAL_VERSION );
		wp_register_style( $this->plugin_slug . '-ui-styles', plugins_url( 'includes/css/jquery-ui-1.10.3.flat.min.css', __FILE__ ), array(), CBXWOOCOUPONREFFERAL_VERSION );

		//register shortcode scripts for ujsing in the shortcode page/post later
		wp_register_script( 'wcrakickgjsapi-front', 'http://www.google.com/jsapi?key=AIzaSyDGv1vFUzN3mAjpgewSY_6zIoQP4Cjjjgo', array(), CBXWOOCOUPONREFFERAL_VERSION, false );
		wp_register_script( $this->plugin_slug . '-chart-cb', plugins_url( '/includes/js/chartkick.js', __FILE__ ), array( 'jquery' ), CBXWOOCOUPONREFFERAL_VERSION );
		wp_register_script( $this->plugin_slug . '-print-cb', plugins_url( '/includes/js/jquery.PrintArea.js', __FILE__ ), array( 'jquery' ), CBXWOOCOUPONREFFERAL_VERSION );
		wp_register_script( $this->plugin_slug . '-customscript-front', plugins_url( '/includes/js/wcra_front.js', __FILE__ ), array( 'jquery' ), CBXWOOCOUPONREFFERAL_VERSION );
	}

	/**
	 *
	 * this function adds support link to plugin
	 */
	function plugin_support( $links ) {
		array_unshift( $links, sprintf( '<a href="options-general.php?page=cbxwoocouponreferral">' . __( 'Setting', 'cbxwoocouponreferral' ) . '</a>' ) );

		return $links;
	}

	/**
	 * @return string
	 */
	public static function cborderbycouponforwoocommerce_plugin_path() {
		// gets the absolute path to this plugin directory
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get table name for coupon referral
	 *
	 * @return string
	 *
	 */
	public function get_cborderbycoupon_table_name() {
		global $wpdb;

		return $wpdb->prefix . "wcra_referral";
	}

	/**
	 * Get table name for coupon referral user
	 *
	 * @return string
	 *
	 */
	public function get_cborderbycoupon_user_table_name() {
		global $wpdb;

		return $wpdb->prefix . "wcra_referral_user";
	}

	/**
	 * List all coupons
	 *
	 * @param boolean $get_ids Ids
	 *
	 * @return array
	 */
	public function cborderbycouponforwoocommerce_getallcoupons() {
		global $wpdb;
		$sql = 'Select * from ' . $this->get_cborderbycoupon_user_table_name() . '  ORDER BY status desc,added_date desc';

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Callback for 'admin_init'.
	 * initialize for plugin.
	 *
	 */
	public function admin_init() {
		//initialize the setting
		//set the settings
		$this->settings_api->set_sections( $this->get_settings_sections() );
		$this->settings_api->set_fields( $this->get_settings_fields() );
		//initialize settings
		$this->settings_api->admin_init();
		$coupon_refer = new WCRACouponTab();
	}


	/**
	 * Returns all the settings sections
	 *
	 * @return array settings sections
	 */
	public function get_settings_sections() {
		$sections = array(
			array(
				'id'    => 'cbxwoocouponreferral_settings',
				'title' => __( 'Settings', 'cbxwoocouponreferral' )
			),
			array(
				'id'    => 'cbxwoocouponreferral_dashboard_widget',
				'title' => __( 'Dashboard Widget Settings', 'cbxwoocouponreferral' )
			)
		);
		$sections = apply_filters( 'cbxreferralsettingssections', $sections );

		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	public function get_settings_fields() {
		global $wp_roles;
		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		$roles         = $wp_roles->get_names();
		$roles         = array_merge( $roles, array( 'guest' => 'Guest' ) );
		$pages         = get_pages();
		$pages_options = array();
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$pages_options[$page->ID] = $page->post_title;
			}
		}

		$wcra_default_page_id  = $this->settings_api->get_option( 'cborderbycoupon_pageid', 'cbxwoocouponreferral_settings', '0' );
		$wcra_page_description = sprintf( __( 'User\'s private Coupon Referral Dashboard. <a href="%s" target="_blank">Visit</a>', 'cbxwoocouponreferral' ), get_permalink( $wcra_default_page_id ) ) . '  ' . sprintf( __( '<a href="%s" target="_blank">Edit</a>', 'cbxwoocouponreferral' ), get_edit_post_link( $wcra_default_page_id ) );

		$fields = array(
			'cbxwoocouponreferral_settings'         => array(
				array(
					'name'    => 'cborderbycoupon_usergroupsr',
					'label'   => __( 'Referral User Role', 'cbxwoocouponreferral' ),
					'desc'    => __( 'Select role for affiliate users', 'cbxwoocouponreferral' ),
					'type'    => 'select',
					'default' => 'administrator',
					'options' => $roles
				),
				array(
					'name'    => 'cborderbycoupon_bindone',
					'label'   => __( 'Bind One User With One Coupon', 'cbxwoocouponreferral' ),
					'desc'    => __( 'If yes same user can not be assigned to multiple coupon', 'cbxwoocouponreferral' ),
					'type'    => 'checkbox',
					'default' => 'on',
				),
				array(
					'name'    => 'cborderbycoupon_pageid',
					'label'   => __( 'Frontend affilitor page', 'cbxwoocouponreferral' ),
					'desc'    => __( $wcra_page_description, 'cbxwoocouponreferral' ),
					'type'    => 'select',
					'default' => $wcra_default_page_id,
					'options' => $pages_options
				),
				array(
					'name'    => 'cborderbycoupon_show_affiliator_info',
					'label'   => __( 'Show Affiliator Info', 'cbxwoocouponreferral' ),
					'desc'    => __( 'If yes displays a section in view order page about the Affiliation', 'cbxwoocouponreferral' ),
					'type'    => 'checkbox',
					'default' => 'on',
				)
			),
			'cbxwoocouponreferral_dashboard_widget' => array(
				array(
					'name'        => 'wcra_top_affiliator_dashboard_usergroup',
					'label'       => __( 'Top Affiliator Visibility', 'cbxwoocouponreferral' ),
					'desc'        => __( 'Select role of users', 'cbxwoocouponreferral' ),
					'type'        => 'multiselect',
					'default'     => array( 'administrator' ),
					'placeholder' => __( 'Select Roles', 'cbxwoocouponreferral' ),
					'options'     => $roles,
				),
				array(
					'name'    => 'wcra_top_affiliator_dashboard_show',
					'label'   => __( 'Show Top Affiliator', 'cbxwoocouponreferral' ),
					'desc'    => __( 'If yes Top Affiliator Dashboard widget is shown', 'cbxwoocouponreferral' ),
					'type'    => 'checkbox',
					'default' => 'on',
				),
				array(
					'name'        => 'wcra_monthly_dashboard_usergroup',
					'label'       => __( 'Monthly Dashboard Visibility', 'cbxwoocouponreferral' ),
					'desc'        => __( 'Select role of users', 'cbxwoocouponreferral' ),
					'type'        => 'multiselect',
					'default'     => array( 'administrator' ),
					'placeholder' => __( 'Select Roles', 'cbxwoocouponreferral' ),
					'options'     => $roles,
				),
				array(
					'name'    => 'wcra_monthly_dashboard_show',
					'label'   => __( 'Show Monthly Stat', 'cbxwoocouponreferral' ),
					'desc'    => __( 'If yes the Monthly Dashboard Widget is shown', 'cbxwoocouponreferral' ),
					'type'    => 'checkbox',
					'default' => 'on',
				)
			)
		);

		$fields = apply_filters( 'cbxreferralsettingfields', $fields );

		return $fields;
	}

	/**
	 * function cb_ajax_cbcouponrefer_autocomplete
	 * Listing users for assining to coupon affiliator
	 */
	function cb_ajax_cbcouponrefer_autocomplete() {
		global $wpdb;

		check_ajax_referer( 'cbxwoocouponreferral_nonce', 'security' );

		if ( isset( $_POST['term'] ) && $_POST['term'] != '' ) {
			$cborderbycoupon_setting_api = get_option( 'cbxwoocouponreferral_settings' );

			if ( isset( $cborderbycoupon_setting_api['cborderbycoupon_usergroupsr'] ) ) {
				$cborderbycoupon_user = $cborderbycoupon_setting_api['cborderbycoupon_usergroupsr'];
			} else {
				$cborderbycoupon_user = 'administrator';
			}


			$posttable_name          = $wpdb->prefix . "users";
			$cbxvar                  = '%' . $_POST['term'] . '%';
			$cbxsql                  = $wpdb->prepare( "SELECT *  FROM $posttable_name WHERE user_login LIKE %s ", $cbxvar );
			$cbxusers                = $wpdb->get_results( $cbxsql, ARRAY_A );
			$cbx_refer_allowed_users = array();

			foreach ( $cbxusers as $cbx_refer_allowed_user ) {
				$user_cbx_data = get_userdata( (int) $cbx_refer_allowed_user['ID'] );
				if ( in_array( $cborderbycoupon_user, $user_cbx_data->roles ) ) {
					$cbx_refer_allowed_users ['names'][]   = $cbx_refer_allowed_user['user_login'];
					$cbx_refer_allowed_users ['ids'][]     = $cbx_refer_allowed_user['ID'];
					$cbx_refer_allowed_users ['display'][] = $cbx_refer_allowed_user['display_name'];
				}
			}
			echo json_encode( $cbx_refer_allowed_users );
		} // end of if isset
		else {
			echo '';
		}
		die();
	}

	public function cart_subtotal_temp( $order, $compound = false, $tax_display = '' ) {
		if ( ! $tax_display ) {
			$tax_display = $this->tax_display_cart;
		}

		$subtotal = 0;

		if ( ! $compound ) {
			foreach ( $order->get_items() as $item ) {

				if ( ! isset( $item['line_subtotal'] ) || ! isset( $item['line_subtotal_tax'] ) ) {
					return '';
				}

				$subtotal += $item['line_subtotal'];

				if ( 'incl' == $tax_display ) {
					$subtotal += $item['line_subtotal_tax'];
				}
			}

			//$subtotal = wc_price( $subtotal, array('currency' => $this->get_order_currency()) );

			/* if ( $tax_display == 'excl' && $this->prices_include_tax ) {
                 $subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
             }*/

		} else {

			if ( 'incl' == $tax_display ) {
				return '';
			}

			foreach ( $order->get_items() as $item ) {

				$subtotal += $item['line_subtotal'];

			}

			// Add Shipping Costs.
			$subtotal += $order->get_total_shipping();

			// Remove non-compound taxes.
			foreach ( $order->get_taxes() as $tax ) {

				if ( ! empty( $tax['compound'] ) ) {
					continue;
				}

				$subtotal = $subtotal + $tax['tax_amount'] + $tax['shipping_tax_amount'];

			}

			// Remove discounts.
			$subtotal = $subtotal - $order->get_total_discount();

			//$subtotal = wc_price( $subtotal, array('currency' => $this->get_order_currency()) );
		}

		//return apply_filters( 'woocommerce_order_subtotal_to_display', $subtotal, $compound, $this );
		return $subtotal;
	}

	/**
	 * @param $str
	 *
	 * @return mixed
	 */
	public function get_numerics( $str ) {
		preg_match_all( '(\d+(?:,\d+)?)', $str, $matches );

		return $matches;
	}

	/**
	 * Callback for 'woocommerce_order_status_completed' hook.
	 *
	 * @param $order_id
	 * update data base
	 *
	 */
	public function wcra_order_completed( $order_id ) {
		global $wpdb;
		$order              = new WC_Order( $order_id );
		$cbx_order_info     = json_encode( (array) $order );
		$cbxorderitems      = $order->get_items();
		$cbxorderitems_list = array();

		$discount = $order->get_total_discount( $order->tax_display_cart === 'excl' && $order->display_totals_ex_tax );
		$subtotal = $this->cart_subtotal_temp( $order, false, $order->tax_display_cart );

		$order_total = (float) $subtotal - (float) $discount;

		foreach ( $cbxorderitems as $cbxorderitem ) {
			array_push( $cbxorderitems_list, array( 'name' => $cbxorderitem['name'], 'qty' => $cbxorderitem['qty'], 'ID' => $cbxorderitem['product_id'] ) );
		}

		$cbxitem_count = $order->get_item_count();
		array_push( $cbxorderitems_list, array( 'item_count' => $cbxitem_count ) );

		$cbxorderitems = json_encode( $cbxorderitems_list );
		//$cbxorder_amount = $order->order_total;
		$cbxorder_amount = $order_total;
		$couponitems     = $order->get_used_coupons();
		$posttable_name  = $wpdb->prefix . "posts";
		$table_name      = $this->get_cborderbycoupon_table_name();

		// prepare coupon for inserting custom table in db
		if ( is_array( $couponitems ) && ! empty( $couponitems ) ) {

			foreach ( $couponitems as $couponitem ) {

				$sql = $wpdb->prepare( "SELECT ID FROM $posttable_name WHERE post_type = %s AND post_title = %s ORDER BY post_date DESC LIMIT 1", 'shop_coupon', $couponitem );

				$couponid = $wpdb->get_results( $sql, ARRAY_A );

				if ( is_array( $couponid ) && ! empty( $couponid ) ) {

					// user_id for database
					$coupon_refer_user_id = get_post_meta( (int) $couponid[0]['ID'], 'cbx_coupon_refer_userid', true );
					$coupon_id            = (int) $couponid[0]['ID'];

					if ( $coupon_refer_user_id != false ) {

						$cbxuser_id_list = get_userdata( (int) $coupon_refer_user_id )->ID;
						$coupon_obj      = new WC_Coupon( $couponitem );

						if ( $coupon_obj->discount_type == 'percent_product' || $coupon_obj->discount_type == 'fixed_product' ) {
							$wcrap_cbxorder_amount       = 0;
							$wcrap_cbxorder_amount_total = 0;

							if ( ! empty( $coupon_obj->product_ids ) ) {
								foreach ( $coupon_obj->product_ids as $wcra_product_id ) {

									foreach ( $cbxorderitems_list as $cbxorderitem_list ) {
										if ( isset( $cbxorderitem_list['ID'] ) ) {
											if ( $wcra_product_id == $cbxorderitem_list['ID'] ) {
												$price = get_post_meta( $wcra_product_id, "_regular_price", true );

												if ( $coupon_obj->discount_type == 'percent_product' ) {
													$wcrap_cbxorder_amount += ( $price - ( $price * ( $coupon_obj->coupon_amount / 100 ) ) );
												} elseif ( $coupon_obj->discount_type == 'fixed_product' ) {
													$wcrap_cbxorder_amount += ( $price - $coupon_obj->coupon_amount );
												}
												$wcrap_cbxorder_amount_total = $wcrap_cbxorder_amount * $cbxorderitem_list['qty'];
											}
										}
									}
								}
							} else {
								$wcrap_cbxorder_amount_total = $order_total;
							}

							$user_percentage      = get_post_meta( (int) $coupon_obj->id, 'cbx_coupon_refer_user_percent', true );
							$user_percentage_type = get_post_meta( (int) $coupon_obj->id, 'cbx_coupon_refer_userpercent_type', true );
							$user_percentage_type = ( $user_percentage_type === false ) ? 1 : $user_percentage_type; //1 = percentage 2 = fixed


							//percentage
							if ( $user_percentage_type == 1 ) {
								$user_earning = (double) ( $wcrap_cbxorder_amount_total * $user_percentage ) / 100;
							} else {
								//fixed
								$user_earning = $user_percentage;
							}

							$cbxcouponreferinsert_data = array(
								'user_id'              => $cbxuser_id_list,
								'order_id'             => $order_id,
								'coupon_id'            => $coupon_id,
								'order_info'           => $cbx_order_info,
								'coupon_info'          => $couponitem,
								'order_items'          => $cbxorderitems,
								'order_amount'         => $wcrap_cbxorder_amount_total,
								'user_percentage'      => $user_percentage,
								'user_percentage_type' => $user_percentage_type,
								'user_earning'         => $user_earning,
								'order_date'           => $order->order_date
							);

							$cbxcouponreferinsert_data_format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%d', '%d', '%f', '%s' );
							$success                          = $wpdb->insert( $table_name, $cbxcouponreferinsert_data, $cbxcouponreferinsert_data_format );

						} elseif ( $coupon_obj->discount_type == 'percent' || $coupon_obj->discount_type == 'fixed_cart' ) {

							$user_percentage      = get_post_meta( (int) $coupon_obj->id, 'cbx_coupon_refer_user_percent', true );
							$user_percentage_type = get_post_meta( (int) $coupon_obj->id, 'cbx_coupon_refer_userpercent_type', true );
							$user_percentage_type = ( $user_percentage_type === false ) ? 1 : $user_percentage_type; //1 = percentage 2 = fixed

							//percentage
							if ( $user_percentage_type == 1 ) {
								$user_earning = (double) ( $cbxorder_amount * $user_percentage ) / 100;
							} else {
								//fixed
								$user_earning = $user_percentage;
							}


							$cbxcouponreferinsert_data        = array(
								'user_id'              => $cbxuser_id_list,
								'order_id'             => $order_id,
								'coupon_id'            => $coupon_id,
								'order_info'           => $cbx_order_info,
								'coupon_info'          => $couponitem,
								'order_items'          => $cbxorderitems,
								'order_amount'         => $cbxorder_amount,
								'user_percentage'      => $user_percentage,
								'user_percentage_type' => $user_percentage_type,
								'user_earning'         => $user_earning,
								'order_date'           => $order->order_date
							);
							$cbxcouponreferinsert_data_format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%d', '%d', '%f', '%s' );
							$success                          = $wpdb->insert( $table_name, $cbxcouponreferinsert_data, $cbxcouponreferinsert_data_format );
						}
					}
					// if user id found
				}
				// if coupon id found
			}
			// end of foreach every coupon
		}
		// if order used any coupon
	}

	/**
	 * Callback filter for 'woocommerce_order_refunded' hook
	 *
	 * @param int $order_id
	 * @param int $refund_id
	 */
	public function wcra_order_refunded( $order_id, $refund_id ) {
		global $wpdb;

		if ( $order_id != null && $refund_id != null ) {
			$order = new WC_Order( $order_id );

			$cbx_order_info = json_encode( (array) $order );
			$cbxorderitems  = $order->get_items();
			$refund         = new WC_Order_Refund( $refund_id );

			$cbxorderitems_list = array();

			/*$get_order_item_totals = $order->get_order_item_totals();

            $subtotal_array = $this->get_numerics($get_order_item_totals['cart_subtotal']['value']);
            $discount_array = $this->get_numerics($get_order_item_totals['discount']['value']);

            $subtotal = (float)$subtotal_array[0][0] . '.' . $subtotal_array[0][1];
            $discount = (float)$discount_array[0][0] . '.' . $discount_array[0][1];

            $order_total = (float)$subtotal - $discount;
            */

			$discount = $order->get_total_discount( $order->tax_display_cart === 'excl' && $order->display_totals_ex_tax );
			$subtotal = $this->cart_subtotal_temp( $order, false, $order->tax_display_cart );

			$order_total = (float) $subtotal - (float) $discount;

			foreach ( $cbxorderitems as $cbxorderitem ) {
				array_push( $cbxorderitems_list, array( 'name' => $cbxorderitem['name'], 'qty' => $cbxorderitem['qty'], 'ID' => $cbxorderitem['product_id'] ) );
			}

			$cbxitem_count = $order->get_item_count();
			array_push( $cbxorderitems_list, array( 'item_count' => $cbxitem_count ) );

			$cbxorderitems   = json_encode( $cbxorderitems_list );
			$cbxorder_amount = $order_total;
			$couponitems     = $order->get_used_coupons();
			$posttable_name  = $wpdb->prefix . "posts";
			$table_name      = $this->get_cborderbycoupon_table_name();

			// prepare coupon for inserting custom table in db
			if ( is_array( $couponitems ) && ! empty( $couponitems ) ) {

				foreach ( $couponitems as $couponitem ) {

					$sql      = $wpdb->prepare( "SELECT ID FROM $posttable_name WHERE post_type = %s AND post_title = %s ORDER BY post_date DESC LIMIT 1", 'shop_coupon', $couponitem );
					$couponid = $wpdb->get_results( $sql, ARRAY_A );

					if ( is_array( $couponid ) && ! empty( $couponid ) ) {
						// user_id for database
						//$coupon_refer_user_id = get_post_meta((int)$couponid[0]['ID'], 'cbx_coupon_refer_userid', true);
						$coupon_id            = (int) $couponid[0]['ID'];
						$sql                  = $wpdb->prepare( "SELECT user_id from $table_name where order_id=%d AND coupon_id=%d", (int) $order_id, $coupon_id );
						$coupon_refer_user_id = $wpdb->get_var( $sql );

						if ( $coupon_refer_user_id != null ) {

							$cbxuser_id_list = get_userdata( (int) $coupon_refer_user_id )->ID;
							$coupon_obj      = new WC_Coupon( $couponitem );

							if ( $coupon_obj->discount_type == 'percent_product' || $coupon_obj->discount_type == 'fixed_product' ) {


								$sql            = $wpdb->prepare( "SELECT user_percentage, user_percentage_type from $table_name where order_id=%d AND coupon_id=%d", (int) $order_id, (int) $coupon_obj->id );
								$affiliate_info = $wpdb->get_results( $sql, ARRAY_A );

								if ( $affiliate_info !== null && sizeof( $affiliate_info ) > 0 ) {

									$wcrap_cbxorder_amount       = 0;
									$wcrap_cbxorder_amount_total = 0;

									if ( ! empty( $coupon_obj->product_ids ) ) {
										foreach ( $coupon_obj->product_ids as $wcra_product_id ) {
											foreach ( $cbxorderitems_list as $cbxorderitem_list ) {
												if ( isset( $cbxorderitem_list['ID'] ) ) {
													if ( $wcra_product_id == $cbxorderitem_list['ID'] ) {
														$price = get_post_meta( $wcra_product_id, "_regular_price", true );
														if ( $coupon_obj->discount_type == 'percent_product' ) {
															$wcrap_cbxorder_amount += ( $price - ( $price * ( $coupon_obj->coupon_amount / 100 ) ) );
														} elseif ( $coupon_obj->discount_type == 'fixed_product' ) {
															$wcrap_cbxorder_amount += ( $price - $coupon_obj->coupon_amount );
														}
														$wcrap_cbxorder_amount_total = $wcrap_cbxorder_amount * $cbxorderitem_list['qty'];
													}
												}
											}
										}
									} else {
										$wcrap_cbxorder_amount_total = $order_total;
									}

									//$wcrap_cbxorder_amount_total  = amount ordered via this coupon

									$get_refund_amount     = $refund->get_refund_amount();
									$wcrap_cbxorder_amount = ( $get_refund_amount * $wcrap_cbxorder_amount_total ) / ( $order_total ); //refund amount as per the product related with coupon


									$user_percentage      = $affiliate_info[0]['user_percentage'];
									$user_percentage_type = $affiliate_info[0]['user_percentage_type'];

									if ( $user_percentage_type == 1 ) {
										//percentage
										$user_earning = (double) ( $wcrap_cbxorder_amount * $user_percentage ) / 100;
									} else {
										//fixed
										//$user_earning = (double)($wcrap_cbxorder_amount * $user_percentage) / 100;
										$user_earning = (double) ( $user_percentage / $wcrap_cbxorder_amount_total ) * $wcrap_cbxorder_amount;
									}


									$cbxcouponreferinsert_data = array(
										'user_id'              => $cbxuser_id_list,
										'order_id'             => $order_id,
										'coupon_id'            => $coupon_id,
										'order_info'           => $cbx_order_info,
										'coupon_info'          => $couponitem,
										'order_items'          => $cbxorderitems,
										'order_amount'         => - ( $wcrap_cbxorder_amount ),
										'user_percentage'      => $user_percentage,
										'user_percentage_type' => $user_percentage_type,
										'user_earning'         => - ( $user_earning ),
										'order_date'           => current_time( 'mysql' )
									);

									$cbxcouponreferinsert_data_format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%d', '%f', '%s' );
									$success                          = $wpdb->insert( $table_name, $cbxcouponreferinsert_data, $cbxcouponreferinsert_data_format );
								}


							} elseif ( $coupon_obj->discount_type == 'percent' || $coupon_obj->discount_type == 'fixed_cart' ) {
								$get_refund_amount = $refund->get_refund_amount();

								$sql = $wpdb->prepare( "SELECT user_percentage, user_percentage_type from $table_name where order_id=%d AND coupon_id=%d", (int) $order_id, (int) $coupon_obj->id );
								//$user_percentage = $wpdb->get_var($sql);

								$affiliate_info = $wpdb->get_results( $sql, ARRAY_A );


								if ( $affiliate_info !== null && sizeof( $affiliate_info ) > 0 ) {


									$user_percentage      = $affiliate_info[0]['user_percentage'];
									$user_percentage_type = $affiliate_info[0]['user_percentage_type'];

									if ( $user_percentage_type == 1 ) {
										//percentage type
										$user_earning = (double) ( $get_refund_amount * $user_percentage ) / 100;
									} else {
										//fixed
										//$user_earning = (double)($get_refund_amount * $user_percentage) / 100;
										$user_earning = (double) ( $user_percentage / $order_total ) * $get_refund_amount;
									}


									$cbxcouponreferinsert_data = array(
										'user_id'              => $cbxuser_id_list,
										'order_id'             => $order_id,
										'coupon_id'            => $coupon_id,
										'order_info'           => $cbx_order_info,
										'coupon_info'          => $couponitem,
										'order_items'          => $cbxorderitems,
										'order_amount'         => - ( $get_refund_amount ),
										'user_percentage'      => $user_percentage,
										'user_percentage_type' => $user_percentage_type,
										'user_earning'         => - ( $user_earning ),
										'order_date'           => current_time( 'mysql' )
									);

									$cbxcouponreferinsert_data_format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%d', '%f', '%s' );
									$success                          = $wpdb->insert( $table_name, $cbxcouponreferinsert_data, $cbxcouponreferinsert_data_format );
								}

							}
						}// if user id found
					}// if coupon id found
				}// end of foreach every coupon
			}// if order used any coupon
		}//if orderid and refund id is not null
	}

	/**
	 * Callback for 'woocommerce_order_status_cancelled'
	 *
	 * @param $order_id
	 * update data base
	 *
	 */
	public function wcra_order_status_cancelled( $order_id ) {
		global $wpdb;
		$order_id = (int) $order_id;

		if ( $order_id != null ) {
			$order              = new WC_Order( $order_id );
			$cbx_order_info     = json_encode( (array) $order );
			$cbxorderitems      = $order->get_items();
			$cbxorderitems_list = array();

			foreach ( $cbxorderitems as $cbxorderitem ) {
				array_push( $cbxorderitems_list, array( 'name' => $cbxorderitem['name'], 'qty' => $cbxorderitem['qty'], 'ID' => $cbxorderitem['product_id'] ) );
			}

			/*
            $get_order_item_totals = $order->get_order_item_totals();

            $subtotal_array = $this->get_numerics($get_order_item_totals['cart_subtotal']['value']);
            $discount_array = $this->get_numerics($get_order_item_totals['discount']['value']);

            $subtotal = (float)$subtotal_array[0][0] . '.' . $subtotal_array[0][1];
            $discount = (float)$discount_array[0][0] . '.' . $discount_array[0][1];

            $order_total = (float)$subtotal - $discount;
            */

			$discount = $order->get_total_discount( $order->tax_display_cart === 'excl' && $order->display_totals_ex_tax );
			$subtotal = $this->cart_subtotal_temp( $order, false, $order->tax_display_cart );

			$order_total = (float) $subtotal - (float) $discount;

			$cbxitem_count = $order->get_item_count();
			array_push( $cbxorderitems_list, array( 'item_count' => $cbxitem_count ) );

			$cbxorderitems   = json_encode( $cbxorderitems_list );
			$cbxorder_amount = $order_total;
			$couponitems     = $order->get_used_coupons();
			$posttable_name  = $wpdb->prefix . "posts";
			$table_name      = $this->get_cborderbycoupon_table_name();

			// prepare coupon for inserting custom table in db
			if ( is_array( $couponitems ) && ! empty( $couponitems ) ) {

				foreach ( $couponitems as $couponitem ) {

					$sql      = $wpdb->prepare( "SELECT ID FROM $posttable_name WHERE post_type = %s AND post_title = %s ORDER BY post_date DESC LIMIT 1", 'shop_coupon', $couponitem );
					$couponid = $wpdb->get_results( $sql, ARRAY_A );

					if ( is_array( $couponid ) && ! empty( $couponid ) ) {

						$coupon_id            = (int) $couponid[0]['ID'];
						$sql                  = $wpdb->prepare( "SELECT user_id from $table_name where order_id=%d AND coupon_id=%d", (int) $order_id, $coupon_id );
						$coupon_refer_user_id = $wpdb->get_var( $sql );

						if ( $coupon_refer_user_id != null ) {
							$cbxuser_id_list = get_userdata( (int) $coupon_refer_user_id )->ID;
							$coupon_obj      = new WC_Coupon( $couponitem );

							if ( $coupon_obj->discount_type == 'percent_product' || $coupon_obj->discount_type == 'fixed_product' ) {


								$sql            = $wpdb->prepare( "SELECT user_percentage, user_percentage_type from $table_name where order_id=%d AND coupon_id=%d", (int) $order_id, (int) $coupon_obj->id );
								$user_affiliate = $wpdb->get_results( $sql, ARRAY_A );

								if ( $user_affiliate !== null && sizeof( $user_affiliate ) > 0 ) {
									//$user_percentage        = $user_affiliate[0]->user_percentage;
									//$user_percentage_type   = $user_affiliate[0]->user_percentage_type;

									$user_percentage      = $user_affiliate[0]['user_percentage'];
									$user_percentage_type = $user_affiliate[0]['user_percentage_type'];


									$wcrap_cbxorder_amount       = 0;
									$wcrap_cbxorder_amount_total = 0;

									if ( ! empty( $coupon_obj->product_ids ) ) {
										foreach ( $coupon_obj->product_ids as $wcra_product_id ) {
											foreach ( $cbxorderitems_list as $cbxorderitem_list ) {
												if ( isset( $cbxorderitem_list['ID'] ) ) {
													if ( $wcra_product_id == $cbxorderitem_list['ID'] ) {
														$price = get_post_meta( $wcra_product_id, "_regular_price", true );
														if ( $coupon_obj->discount_type == 'percent_product' ) {
															$wcrap_cbxorder_amount += ( $price - ( $price * ( $coupon_obj->coupon_amount / 100 ) ) );
														} elseif ( $coupon_obj->discount_type == 'fixed_product' ) {
															$wcrap_cbxorder_amount += ( $price - $coupon_obj->coupon_amount );
														}
														$wcrap_cbxorder_amount_total = $wcrap_cbxorder_amount * $cbxorderitem_list['qty'];
													}
												}
											}
										}
										$wcrap_cbxorder_amount_total -= ( $order->get_total_refunded() * $wcrap_cbxorder_amount_total ) / ( $order_total );;
									} else {
										$wcrap_cbxorder_amount_total = $order_total - $order->get_total_refunded();
									}

									$wcrap_cbxorder_amount = $wcrap_cbxorder_amount_total;


									if ( $user_percentage_type == 1 ) {
										//percentage
										$user_earning = (double) ( $wcrap_cbxorder_amount * $user_percentage ) / 100;
									} else {
										//fixed
										//$user_earning = (double)($wcrap_cbxorder_amount * $user_percentage) / 100;
										$user_earning = (double) ( $user_percentage / $wcrap_cbxorder_amount_total ) * $wcrap_cbxorder_amount;
									}


									$cbxcouponreferinsert_data = array(
										'user_id'              => $cbxuser_id_list,
										'order_id'             => $order_id,
										'coupon_id'            => $coupon_id,
										'order_info'           => $cbx_order_info,
										'coupon_info'          => $couponitem,
										'order_items'          => $cbxorderitems,
										'order_amount'         => - ( $wcrap_cbxorder_amount ),
										'user_percentage'      => $user_percentage,
										'user_percentage_type' => $user_percentage_type,
										'user_earning'         => - ( $user_earning ),
										'order_date'           => current_time( 'mysql' )
									);

									$cbxcouponreferinsert_data_format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%d', '%f', '%s' );
									$success                          = $wpdb->insert( $table_name, $cbxcouponreferinsert_data, $cbxcouponreferinsert_data_format );
								}

							} elseif ( $coupon_obj->discount_type == 'percent' || $coupon_obj->discount_type == 'fixed_cart' ) {

								$sql            = $wpdb->prepare( "SELECT user_percentage, user_percentage_type from $table_name where order_id=%d AND coupon_id=%d", (int) $order_id, (int) $coupon_obj->id );
								$user_affiliate = $wpdb->get_results( $sql, ARRAY_A );

								if ( $user_affiliate !== null && sizeof( $user_affiliate ) > 0 ) {
									$user_percentage      = $user_affiliate[0]['user_percentage'];
									$user_percentage_type = $user_affiliate[0]['user_percentage_type'];

									//$user_percentage        = $user_affiliate[0]->user_percentage;
									//$user_percentage_type   = $user_affiliate[0]->user_percentage_type;

									if ( $user_percentage_type == 1 ) {
										//percentage
										$user_earning = (double) ( ( $order_total - $order->get_total_refunded() ) * $user_percentage ) / 100;
									} else {
										//fixed
										//$user_earning = (double)(($order_total - $order->get_total_refunded()) * $user_percentage) / 100;
										$user_earning = (double) ( $user_percentage / $order_total ) * ( $order_total - $order->get_total_refunded() );
									}


									$cbxcouponreferinsert_data = array(
										'user_id'              => $cbxuser_id_list,
										'order_id'             => $order_id,
										'coupon_id'            => $coupon_id,
										'order_info'           => $cbx_order_info,
										'coupon_info'          => $couponitem,
										'order_items'          => $cbxorderitems,
										'order_amount'         => - ( $order_total - $order->get_total_refunded() ),
										'user_percentage'      => $user_percentage,
										'user_percentage_type' => $user_percentage_type,
										'user_earning'         => - ( $user_earning ),
										'order_date'           => current_time( 'mysql' )
									);

									$cbxcouponreferinsert_data_format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%d', '%f', '%s' );
									$success                          = $wpdb->insert( $table_name, $cbxcouponreferinsert_data, $cbxcouponreferinsert_data_format );
								}


							}
						}
						// if user id found
					}
					// if coupon id found
				}
				// end of foreach every coupon
			}
			// if order used any coupon
		}
		//if orderid and refund id is not null
	}

	/**
	 * Shortcode filterable tabs
	 *
	 * @return mixed|void
	 */
	public function wcra_shortcode_tabs() {
		$wcra_shortcode_tabs = array(
			'wcra_affiliation_stats' => __( 'Affiliation Stats', 'cbxwoocouponreferral' ),
			'wcra_contact_info'      => __( 'Contact Information', 'cbxwoocouponreferral' )
		);

		//Addon Implementation
		return apply_filters( 'wcra_shortcode_tabs', $wcra_shortcode_tabs );
	}

	/**
	 *Updating user contact info
	 */
	public function wp_ajax_wcra_user_contactinfo() {
		check_ajax_referer( 'cbxwoocouponreferral_nonce', 'security' );

		if ( ! is_user_logged_in() ) {
			return;
		}
		$user_id = get_current_user_id();

		$wcra_user_contact_phone = ( isset( $_POST['wcra_user_contact_phone'] ) && $_POST['wcra_user_contact_phone'] != '' ) ? esc_attr( $_POST['wcra_user_contact_phone'] ) : '';

		update_user_meta( $user_id, 'wcra_user_contact_phone', $wcra_user_contact_phone );

		echo json_encode( 'updated' );
		wp_die();
	}


	/**
	 * Shows top affiliate users
	 *
	 * @param $attr
	 *
	 * @return string
	 */
	public function wcratop_callback( $attr ) {

		$attr = shortcode_atts(
			array(
				'count'    => 10,
				'type'     => 'month',
				'order_by' => 'total_earning',
				'order'    => 'DESC'
			), $attr
		);

		extract( $attr );

		$order_by = ( $order_by == '' ) ? 'total_earning' : $order_by;
		$order    = ( $order == '' ) ? 'DESC' : $order;

		global $wpdb;
		$coupontable_name             = $this->get_cborderbycoupon_table_name();
		$sql_query_for_referred_order = '';
		$getdate                      = getdate();
		$year                         = (int) $getdate['year'];
		$month                        = (int) $getdate['mon'];

		$sql_query_for_referred_order .= 'SELECT SUM(order_amount) as total_amount ,SUM(user_earning) as total_earning , COUNT(DISTINCT order_id) as total_referred, user_id, coupon_id, user_percentage, user_percentage_type FROM ' . $coupontable_name . ' WHERE ';

		$sql_query_for_referred_order .= 'YEAR(order_date) = %d ';

		if ( $type == 'month' ) {
			$sql_query_for_referred_order .= 'AND MONTH(order_date) = %d ';
		}

		$sql_query_for_referred_order .= 'GROUP BY user_id ORDER BY ' . $order_by . ' ';

		$sql_query_for_referred_order .= $order;

		$sql_query_for_referred_order .= ' LIMIT %d';


		if ( $type == 'month' ) {
			$sql = $wpdb->prepare( $sql_query_for_referred_order, $year, $month, $count );
		} else {
			$sql = $wpdb->prepare( $sql_query_for_referred_order, $year, $count );
		}

		$data = $wpdb->get_results( $sql, ARRAY_A );

		ob_start();

		$monthname = self::$monthname;
		?>

		<?php if ( is_array( $data ) && sizeof( $data ) > 0 ) { ?>
			<div class="wcratop_affiliator">
				<table class="table widefat wcratop_affiliator_table">
					<thead>
					<tr>
						<th style="text-align:center;"><?php _e( 'Name', 'cbxwoocouponreferral' ); ?></th>
						<th style="text-align:center;"><?php _e( 'No Ref.', 'cbxwoocouponreferral' ); ?></th>
						<th style="text-align:center;"><?php _e( 'Total Earn', 'cbxwoocouponreferral' ); ?></th>
						<th style="text-align:center;"><?php _e( 'Total Sales', 'cbxwoocouponreferral' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ( $data as $key => $value ) { ?>
						<tr>
							<td style="text-align:center;"
								class="wcra_center"><?php echo get_user_by( 'ID', $value['user_id'] )->display_name; ?></td>
							<td style="text-align:center;"
								class="wcra_center"><?php echo $value['total_referred']; ?></td>
							<td style="text-align:center;"
								class="wcra_center"><?php echo wc_price( $value['total_earning'] ); ?></td>
							<td style="text-align:center;"
								class="wcra_center"><?php echo wc_price( $value['total_amount'] ); ?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>

		<?php }

		return ob_get_clean();
	}


	/**
	 * Render "peryear" shortcode data
	 */
	public function wcra_sc_render_peryear() {
		?>
		<input type="hidden" name="wcratype_year" class="wcratype_year" value="peryear" />

		<h2 class="wcra_heading" id="cbcouponreferperyear_title"><?php _e( "Affiliation Stat : Year", "cbxwoocouponreferral", 'cbxwoocouponreferral' ); ?>
			<span class="cbcouponreferperyear_title_yr"><?php _e( date( "Y" ), 'cbxwoocouponreferral' ); ?></span>

			<p class="cbcouponrefer_selected_coupon"></p>

			<p class="cbcouponrefer_total_earning cbcouponrefer_total_earning_peryear"></p>
		</h2>

		<p class="wcrayear_traverse">

			<a data-year="<?php _e( date( "Y" ) - 1 ) ?>" data-busy="0"
			   data-target=""
			   data-type="prev"
			   class="button button-primary button-small btn btn-info btn-sm   cbxcouponref_btn cbcouponreferperyear_years"><?php _e( "Prev", "cbxwoocouponreferral" ) ?></a>
			<a data-year="<?php _e( date( "Y" ) + 1 ) ?>" data-busy="0"
			   data-target=""
			   data-type="next"
			   class="button button-primary button-small btn btn-info btn-sm   cbx-next cbxcouponref_btn cbcouponreferperyear_years"><?php _e( "Next", "cbxwoocouponreferral" ); ?></a>

		</p>

		<div id="cbcouponreferperyear" style="height: 300px;"></div>

		<!--<div id="cbcouponreferperyear_labels"></div>-->

		<div class="cbcouponreferperyear_compare">
			<h2 class="wcra_heading"><?php _e( " Order Overview (including shipping & other costs) : Year", "cbxwoocouponreferral" ); ?></h2>
		</div>

		<?php
		$cbexportpdfurl_year = '?wcraexport=pdf&year=' . ( date( "Y" ) );

		$cbx_single_coupon_id = '';
		if ( $cbx_single_coupon_id != '' ) {
			$cbexportpdfurl_year .= '&coupon=' . $cbx_single_coupon_id;
		}

		$cbexportcsvurl_year = '?wcraexport=csv&year=' . ( date( "Y" ) );

		if ( $cbx_single_coupon_id != '' ) {
			$cbexportcsvurl_year .= '&coupon=' . $cbx_single_coupon_id;
		}

		$user_coupon_earn_target = '';

		$cbx_stats = 'peryear';

		?>

		<div class="cbcouponrefer_export">
			<?php echo apply_filters( 'cbx_wcra_sc_after_content', $cbx_single_coupon_id, $cbx_stats, $user_coupon_earn_target, $cbexportcsvurl_year, $cbexportpdfurl_year ); ?>
		</div>
	<?php }

	/**
	 * Render "permonth" shortcode data
	 */
	public function wcra_sc_render_permonth() {
		?>

		<input type="hidden" name="wcratype_month" class="wcratype_month" value="permonth" />

		<h2 class="wcra_heading" class="cbcouponreferperyear_title"><?php _e( "Affiliation Stat : ", "cbxwoocouponreferral", 'cbxwoocouponreferral' ); ?>
			<span class="cbcouponreferperyear_title_date"><?php _e( date( "Y" ), 'cbxwoocouponreferral' ); ?></span>

			<p class="cbcouponrefer_selected_coupon"></p>

			<p class="cbcouponrefer_total_earning cbcouponrefer_total_earning_permonth"></p>
		</h2>

		<p class="wcramonth_traverse">
			<?php
			$wcra_month = (int) date( 'm' );
			$wcra_year  = (int) date( 'Y' );

			if ( $wcra_month == 12 ) {
				$wcra_prev_month = $wcra_month - 1;
				$wcra_next_month = 1;
				$wcra_prev_year  = $wcra_year;
				$wcra_next_year  = $wcra_year + 1;
			} elseif ( $wcra_month == 1 ) {
				$wcra_prev_month = 12;
				$wcra_next_month = $wcra_month + 1;
				$wcra_prev_year  = $wcra_year - 1;
				$wcra_next_year  = $wcra_year;
			} else {
				$wcra_prev_month = $wcra_month - 1;
				$wcra_next_month = $wcra_month + 1;
				$wcra_prev_year  = $wcra_year;
				$wcra_next_year  = $wcra_year;
			}

			$wcra_display = ( $wcra_next_month > $wcra_month ) ? 'display:none;' : '';
			$cbx_stats    = 'permonth';

			?>
			<a data-year="<?php echo $wcra_prev_year; ?>"
			   data-month="<?php echo $wcra_prev_month; ?>"
			   data-busy="0"
			   data-target=""
			   data-type="prev"
			   class="button button-primary button-small btn btn-info btn-sm cbxcouponref_btn cbcouponreferpermonth_months"><?php _e( "Prev", "cbxwoocouponreferral" ) ?></a>
			<a data-year="<?php echo $wcra_next_year; ?>"
			   data-month="<?php echo $wcra_next_month; ?>"
			   data-busy="0"
			   data-target=""
			   data-type="next"
			   class="button button-primary button-small btn btn-info btn-sm  cbx-next cbxcouponref_btn cbcouponreferpermonth_months hidden"><?php _e( "Next", "cbxwoocouponreferral" ); ?></a>

		</p>

		<div id="cbcouponreferpermonth" style="height: 300px;"></div>

		<!--<div id="cbcouponreferperyear_labels"></div>-->

		<div class="cbcouponreferpermonth_compare">
			<h2 class="wcra_heading"><?php _e( " Affiliation Overview : ", "cbxwoocouponreferral" ); ?></h2>
		</div>

		<?php
		$cbexportpdfurl_month = '?wcraexport=pdf&year=' . $wcra_year . '&month=' . $wcra_month;

		$cbx_single_coupon_id = '';
		if ( $cbx_single_coupon_id != '' ) {
			$cbexportpdfurl_month .= '&coupon=' . $cbx_single_coupon_id;
		}

		$cbexportcsvurl_month = '?wcraexport=csv&year=' . $wcra_year . '&month=' . $wcra_month;

		if ( $cbx_single_coupon_id != '' ) {
			$cbexportcsvurl_month .= '&coupon=' . $cbx_single_coupon_id;
		}

		$user_coupon_earn_target = '';

		?>

		<div class="cbcouponrefer_export">
			<?php echo apply_filters( 'cbx_wcra_sc_after_content', $cbx_single_coupon_id, $cbx_stats, $user_coupon_earn_target, $cbexportcsvurl_month, $cbexportpdfurl_month ); ?>
		</div>

	<?php }

	/**
	 * Callback for 'add_shortcode'
	 * function for showing coupon with shortcode.
	 */
	public function cbx_show_coupon_with_shortcode( $attr ) {
		if ( ! is_user_logged_in() ) {
			return sprintf( '<a href= "%s">%s</a>', wp_login_url( site_url( '/wcramydashboard/' ) ), __( 'Login to view', 'cbxwoocouponreferral' ) );

		}
		global $wpdb;
		$attr = shortcode_atts(
			array(
				'type'   => 'permonth',
				'status' => "all"
			), $attr
		);

		extract( $attr );

		ob_start();
		//ajax nonce
		$ajax_nonce = wp_create_nonce( "cbxwoocouponreferral_nonce" );

		//enqueue the scripts in shortcode page/post
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'wcrakickgjsapi-front' );
		wp_enqueue_script( $this->plugin_slug . '-chart-cb' );
		wp_enqueue_script( $this->plugin_slug . '-print-cb' );
		wp_enqueue_script( $this->plugin_slug . '-customscript-front' );
		wp_localize_script(
			$this->plugin_slug . '-customscript-front', 'wcrafront',
			array(
				'userediturl'                 => admin_url( 'user-edit.php?user_id=' ),
				'nonce'                       => $ajax_nonce,
				'monthname'                   => self:: $monthname,
				'shortmonthname'              => self:: $shortmonthname,
				'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
				'wcraoo_header'               => __( 'Affiliation Overview : ', 'cbxwoocouponreferral' ),
				'wcraootable_month'           => __( 'Month', 'cbxwoocouponreferral' ),
				'wcraootable_day'             => __( 'Day', 'cbxwoocouponreferral' ),
				'wcraootable_amt'             => sprintf( __( 'Order Amount (%s)', 'cbxwoocouponreferral' ), get_woocommerce_currency_symbol() ),
				'wcraootable_number'          => __( 'Order Number', 'cbxwoocouponreferral' ),
				'wcraootable_earn'            => sprintf( __( 'Earn (%s)', 'cbxwoocouponreferral' ), get_woocommerce_currency_symbol() ),
				'wcraoagraph_caption'         => __( 'Orders by Reference', 'cbxwoocouponreferral' ),
				'woocommerce_currency_symbol' => get_woocommerce_currency_symbol(),
				'error_msg'                   => __( 'You Have No Order', 'cbxwoocouponreferral' ),
				'coupon_name'                 => __( 'Coupon Name :', 'cbxwoocouponreferral' ),
				'total_earning'               => sprintf( __( 'Total Earning (%s)', 'cbxwoocouponreferral' ), get_woocommerce_currency_symbol() ),
				'mail_send'                   => __( 'Mail Send Successfully!', 'cbxwoocouponreferral' )
			)
		);

		//enqueue the styles in shortcode page/post
		wp_enqueue_style( $this->plugin_slug . '-ui-styles' );
		wp_enqueue_style( $this->plugin_slug . '-customstyle' );

		$coupon_array = array();
		$all_coupons  = $this->cborderbycouponforwoocommerce_getallcoupons();
		$count        = 0;
		$user         = get_current_user_id();
		$current_user = get_user_by( 'id', $user );


		if ( is_array( $all_coupons ) && ! empty( $all_coupons ) ) {
			foreach ( $all_coupons as $coupon ) {
				if ( $coupon['user_id'] == $user ) {
					$cbx_populate_coupon = new WC_Coupon( get_the_title( $coupon['coupon_id'] ) );

					if ( $coupon['status'] == '1' ) {
						$coupontable_name               = WCRAHelper:: get_cborderbycoupon_table_name();
						$cbx_xoupon_refer_user_target   = get_post_meta( $coupon['coupon_id'], 'cbx_coupon_refer_user_milestone', true );
						$cbx_xoupon_refer_user_perc     = get_post_meta( $coupon['coupon_id'], 'cbx_coupon_refer_user_percent', true );
						$cbx_xoupon_refer_user_perctype = get_post_meta( $coupon['coupon_id'], 'cbx_coupon_refer_userpercent_type', true );

						$cbx_xoupon_refer_user    = get_post_meta( $coupon['coupon_id'], 'cbx_coupon_refer_user', true );
						$cbx_xoupon_refer_user_id = get_post_meta( $coupon['coupon_id'], 'cbx_coupon_refer_userid', true );

						$commission_type = array(
							'1' => __( 'Percentage', 'cbxwoocouponreferral' ),
							'2' => __( 'Fixed', 'cbxwoocouponreferral' )
						);

						$percentage_sign       = '';
						$commission_type_value = '';

						if ( $cbx_xoupon_refer_user_perctype == false ) {
							$commission_type_value = __( 'Percentage', 'cbxwoocouponreferral' );
							$percentage_sign       = '%';
						} else {

							if ( ! in_array( $cbx_xoupon_refer_user_perctype, array( 1, 2 ) ) ) {
								$cbx_xoupon_refer_user_perctype = 1;
							}

							$commission_type_value = $commission_type[$cbx_xoupon_refer_user_perctype];
							$percentage_sign       = ( $cbx_xoupon_refer_user_perctype == 1 ) ? '%' : __( '(Fixed)', 'cbxwoocouponreferral' );
						}

						$coupon_array[$count]['cinfo'] = ' - ' . $cbx_xoupon_refer_user_perc . $percentage_sign;
					} else {
						$coupon_array[$count]['cinfo'] = '';
					}


					$coupon_array[$count]['id']     = $coupon['coupon_id'];
					$coupon_array[$count]['title']  = $cbx_populate_coupon->code;
					$coupon_array[$count]['status'] = ( $coupon['status'] == '1' ) ? __( 'Active', 'cbxwoocouponreferral' ) : __( 'Inactive', 'cbxwoocouponreferral' );
					$count ++;
				}
			}
		} ?>

		<div class="wcrafronttab cbx_user_coupons_front_wrapper">
			<?php $wcra_tabs = $this->wcra_shortcode_tabs(); ?>
			<ul>
				<?php foreach ( $wcra_tabs as $tab_key => $tab_value ) { ?>
					<li><a href="#<?php echo $tab_key ?>"><?php echo $tab_value; ?></a></li>
				<?php } ?>
			</ul>

			<div id="wcra_affiliation_stats">

				<?php if ( $count == 0 ) {
					if ( $current_user != null ) {
						echo sprintf( __( '<p>Hi %s, </p>', 'cbxwoocouponreferral' ), $current_user->display_name );
						_e( '<p>Sorry you are not assigned to any coupon yet.</p>', 'cbxwoocouponreferral' );
					}
				} else { ?>
				<?php echo sprintf( __( '<h3>Hi %s, </h3>', 'cbxwoocouponreferral' ), $current_user->display_name ); ?>
				<select class="cbx_user_coupons_front">
					<option value="" class=""
							selected><?php _e( "Select a coupon", "cbxwoocouponreferral" ); ?></option>
					<?php foreach ( $coupon_array as $key => $value ) { ?>
						<option value="<?php echo $value['id']; ?>" class="">
							<?php echo $value['title'] . '(' . $value['status'] . ')' . $value['cinfo']; ?>
						</option>
					<?php } ?>
				</select>
				<?php $cbx_ajax_icon = plugins_url( 'cbxwoocouponreferral/includes/css/busy.gif' ); ?>
				<span data-busy="0" class="cbcouponrefer_ajax_icon"><img
						src="<?php echo $cbx_ajax_icon; ?>" /></span>

				<div class="cbx_user_coupons_analysis_front">
					<div class="cborderbycouponforwoocommerce_wrapper" style="display: none">
						<?php foreach ( explode( ',', $type ) as $key => $value ) {
							if ( $value == 'permonth' ) {
								$this->wcra_sc_render_permonth();
							}

							if ( $value == 'peryear' ) {
								$this->wcra_sc_render_peryear();
							}
						} ?>
						<?php } ?>
					</div>
				</div>
			</div>
			<div id="wcra_contact_info">
				<?php $cbx_ajax_icon = plugins_url( 'cbxwoocouponreferral/includes/css/busy.gif' ); ?>
				<form id="wcra_user_contact_info_form" name="wcra_user_contact_info" method="post" action="">
					<?php
					if ( $current_user != null ) {
						echo sprintf( __( '<p><strong>Name  : </strong> %s</p>', 'cbxwoocouponreferral' ), $current_user->display_name );
						echo sprintf( __( '<p><strong>Email : </strong> %s</p>', 'cbxwoocouponreferral' ), $current_user->user_email );
						$wcra_user_contact_phone = get_user_meta( $user, 'wcra_user_contact_phone', true );
					}
					?>

					<div class="form-group">
						<label for="wcra_user_contact_phone"><?php _e( 'Telephone/Phone : ', 'cbxwoocouponreferral' ); ?></label>
						<input type="text" name="wcra_user_contact_phone" value="<?php echo $wcra_user_contact_phone; ?>" class="form-control wcra_user_contact_phone" id="wcra_user_contact_phone" placeholder="<?php _e( 'Enter Contact Telephone/Phone.', 'cbxwoocouponreferral' ); ?>" />
					</div>
					<p class="wcra_front_action">
						<span align="right" data-busy="0" class="cbcouponrefer_ajax_icon"><img
								src="<?php echo $cbx_ajax_icon; ?>" /></span>
						<input type="submit" value="Save" id="wcra_user_contact_info" class="btn btn-primary wcra_user_contact_info" name="submit" />
					</p>
				</form>
			</div>
			<?php $output = ''; ?>
			<!-- Addon Implementation-->
			<?php apply_filters( 'wcra_shortcode_contents', $output ) ?>
		</div>
		<?php $ob_contents = ob_get_contents();
		ob_end_clean();

		return $ob_contents;
	}

	/**
	 * Coupon Referral main menu page display
	 */
	public function cbcoupon_refer_display_stats() {
		$coupon_stat_output = $this->cbxcoupon_stats();
		echo $coupon_stat_output;
	}

	/**
	 * Settings page of plugin with sidebar.
	 */
	public function cbx_woocommerce_coupon_display() {
		WCRAHelper::showSettingPanel( $this );
	}

	/**
	 * Affiliators page Display
	 */
	public function wcra_affiliators() {
		if ( ! class_exists( 'WCRAAffiliator' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . '/includes/class-wcra-affiliators-list.php' );
		}
		//Create an instance of our WCRALog class
		$wcraaffiliators = new WCRAAffiliators();
		//Fetch, prepare, sort, and filter WCRALog data
		$wcraaffiliators->prepare_items();
		?>
		<div class="wrap">
			<div id="icon-users" class="icon32"><br /></div>
			<h2><?php _e( 'CBX WCRA : Affiliators', 'cbxwoocouponreferral' ); ?></h2>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<!-- main content -->
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<div class="postbox">
								<div class="inside">
									<form method="get">
										<input type="hidden" name="page" value="wcraaffiliators" />
										<?php //$wcraaffiliators->search_box('search status', 'search_id');
										?>
										<?php
										$wcraaffiliators->display();
										?>
									</form>

								</div>
								<!-- .inside -->
							</div>
							<!-- .postbox -->
						</div>
						<!-- .meta-box-sortables .ui-sortable -->
					</div>
					<!-- post-body-content -->
					<!-- sidebar -->
					<?php include 'includes/sidebar.php'; ?>
					<!-- #postbox-container-1 .postbox-container -->
				</div>
				<!-- #post-body .metabox-holder .columns-2 -->
				<br class="clear">
			</div>
			<!-- #poststuff -->
		</div>
		<?php
	}

	/**
	 * Showing stats.
	 */
	public function cbxcoupon_stats() {
		global $wpdb;
		$cbx_total_target_achieved = 0.0;
		$coupontable_name          = $this->get_cborderbycoupon_table_name();
		$cbx_order_coupons         = $this->cborderbycouponforwoocommerce_getallcoupons();
		?>

		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<?php echo '<h2 class="wcra_heading">' . __( 'CBX Woo Coupon Affiliation For Sales Representative', 'cbxwoocouponreferral' ) . '</h2>'; ?>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<!-- main content -->
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<!--Top section monthly-->
							<div class="postbox">
								<div class="inside">
									<h2 id="cbcouponrefered_users"
										class="wcra_heading"> <?php _e( " Referred Users (Current Month Stats) ", "cbxwoocouponreferral" ); ?>
										<?php
										$wcra_prev_month = (int) date( 'm', strtotime( '-1 month' ) );
										$wcra_year       = (int) date( 'Y' );

										if ( $wcra_prev_month == 12 ) {
											$wcra_prev_month = 1;
											$wcra_year       = $wcra_year - 1;
										}

										?>
										<a href="<?php echo admin_url( 'admin.php?page=wcrapermonth&m=' . $wcra_prev_month . '&y=' . $wcra_year ); ?>"
										   target="_blank"><?php _e( "Prev Month", "cbxwoocouponreferral" ); ?></a>
									</h2>
									<?php
									if ( is_array( $cbx_order_coupons ) && ! empty( $cbx_order_coupons ) ) {
										$output = '';
										$output .= '<table  class = "table widefat tablesorter display">
                                                              <thead>
                                                               <tr>
                                                                 <th style="text-align:center;">' . __( "Coupon", "cbxwoocouponreferral" ) . '</th>
                                                                 <th style="text-align:center;">' . __( "Refer User", "cbxwoocouponreferral" ) . '</th>
                                                                 <th style="text-align:center;">' . sprintf( __( "Monthly Target(%s)", "cbxwoocouponreferral" ), get_woocommerce_currency_symbol() ) . '</th>
                                                                 <th style="text-align:center;">' . sprintf( __( "Monthly Milestone Earned(%s)", "cbxwoocouponreferral" ), get_woocommerce_currency_symbol() ) . '</th>
                                                                 <th style="text-align:center;">' . sprintf( __( "Earned(%s)", "cbxwoocouponreferral" ), get_woocommerce_currency_symbol() ) . '</th>
                                                                 <th style="text-align:center;">' . __( "Current Status", "cbxwoocouponreferral" ) . '</th>
                                                               </tr>
                                                               </thead>
                                                               <tbody>';

										$per_month_total_user_earning = 0;
										foreach ( $cbx_order_coupons as $cbx_order_coupon ) {
											$cbx_populate_coupon       = new WC_Coupon( get_the_title( $cbx_order_coupon['coupon_id'] ) );
											$cbx_populate_coupon_user  = get_user_by( 'id', $cbx_order_coupon['user_id'] );
											$cbx_total_target_achieved = 0.0;
											$user_totalorder_earned    = 0.0;

											$output .= '<tr>
                                                                    <td align="center"><a href = "' . admin_url( 'admin.php?page=wcrapermonth&m=' . (int) date( 'm' ) . '&y=' . (int) date( 'Y' ) . '&u=' . $cbx_order_coupon['user_id'] . '&c=' . (int) $cbx_order_coupon['coupon_id'] ) . '"  target="_blank">' . $cbx_populate_coupon->code . '</a></td>
												                    <td align="center"><a href = "' . admin_url( 'admin.php?page=wcrapermonth&m=' . (int) date( 'm' ) . '&y=' . (int) date( 'Y' ) . '&u=' . $cbx_order_coupon['user_id'] ) . '"  target="_blank">' . $cbx_populate_coupon_user->display_name . '</a></td>
												                    <td align="center">';
											$cbxordermilestone = get_post_meta( (int) $cbx_populate_coupon->id, 'cbx_coupon_refer_user_milestone', true );

											//$cbxordermilestone_ = ($cbxordermilestone != false) ? get_woocommerce_currency_symbol() . $cbxordermilestone : __("N/A", "");
											$cbxordermilestone_ = ( $cbxordermilestone != false ) ? $cbxordermilestone : __( "N/A", "" );

											$output .= $cbxordermilestone_ . ' </td>
                                                                    <td align="center">';
											$sql           = $wpdb->prepare( "SELECT * FROM $coupontable_name WHERE user_id = %d AND coupon_id = %d AND MONTH(CURDATE())= MONTH(order_date) ", (int) $cbx_order_coupon['user_id'], (int) $cbx_populate_coupon->id );
											$order_amounts = $wpdb->get_results( $sql, ARRAY_A );

											if ( is_array( $order_amounts ) && ! empty( $order_amounts ) ) {

												foreach ( $order_amounts as $order_amount ) {
													$cbx_total_target_achieved += (double) $order_amount ['order_amount'];
													$user_totalorder_earned += (double) $order_amount ['user_earning'];
												}
											}

											$per_month_total_user_earning += $user_totalorder_earned;

											$output .= wcra_price( $cbx_total_target_achieved );
											$output .= '</td><td align="center">';
											$user_totalorder_earned_ = ( $user_totalorder_earned != false ) ? wcra_price( $user_totalorder_earned ) : __( "N/A", "cbxwoocouponreferral" );
											$output .= $user_totalorder_earned_;
											$output .= '</td><td align="center">';
											$user_status = ( $cbx_order_coupon['status'] == 1 ) ? __( "Active", "cbxwoocouponreferral" ) : __( "Inactive", "cbxwoocouponreferral" );
											$output .= $user_status;
											$output .= '</td></tr>';
										}

										$output .= '<tr><td colspan="3"></td><td align="right">'.sprintf(__('Total User Earing for this Month(%s) = ', 'cbxwoocouponreferral'),get_woocommerce_currency_symbol()  ).'</td><td align="center"><strong>'.wcra_price($per_month_total_user_earning).'</strong></td><td></td></tr>';
										$output .= '</tbody></table>';
										echo $output;
									}
									?>
								</div>
								<!-- .inside -->
							</div>
							<!-- Middle section -->
							<div class="postbox">
								<div class="inside">
									<h2 id="cbcouponreferperyear_title"
										class="wcra_heading"><?php _e( "Order Analysis (including shipping & other costs) : Year", "cbxwoocouponreferral" ); ?>
										<span
											class="cbcouponreferperyear_title_yr"> <?php _e( date( 'Y' ), 'cbxwoocouponreferral' ); ?> </span>
									</h2>

									<p class="wcrayear_traverse">
										<a data-year="<?php _e( date( "Y" ) - 1 ) ?>" data-busy="0"
										   data-target="<?php echo $cbx_total_target_achieved ?>"
										   data-type="prev"
										   class="button button-large btn btn-info cbxcouponref_btn cbcouponreferperyear_years"><?php _e( "Prev", "cbxwoocouponreferral" ) ?></a>

										<a data-year="<?php _e( date( "Y" ) + 1 ) ?>" data-busy="0"
										   data-target="<?php echo $cbx_total_target_achieved; ?>"
										   data-type="next"
										   class="button button-large btn btn-info cbx-next cbxcouponref_btn cbcouponreferperyear_years hidden">
											<?php _e( "Next", "cbxwoocouponreferral" ); ?></a>
									</p>

									<div id="cbcouponreferperyear" style="height: 300px;"></div>
								</div>
								<!-- .inside -->
							</div>
							<!-- .postbox -->
							<!-- Bottom section -->
							<div class="postbox">
								<div class="inside">
									<div id="cbcouponreferperyear_compare"></div>
									<div class="cbcouponrefer_export">
										<?php
										$cbexportpdfurl = admin_url( 'admin.php?page=cbxcouponreferrral&wcraexport=pdf&year=' . ( date( "Y" ) ) );
										$cbexportcsvurl = admin_url( 'admin.php?page=cbxcouponreferrral&wcraexport=csv&year=' . ( date( "Y" ) ) );
										?>
										<?php
										//codeboxr's native addon shows the print, email, export and pdf buttons using this hook
										apply_filters( 'cbx_wcra_admin_after_content', $cbx_single_coupon_id = '', $cbx_total_target_achieved, $cbexportcsvurl, $cbexportpdfurl );
										?>
									</div>
								</div>
								<!-- .inside -->
							</div>
							<!-- .postbox -->
						</div>
						<!-- .meta-box-sortables .ui-sortable -->
					</div>
					<!-- post-body-content -->
					<!-- sidebar -->
					<?php include 'includes/sidebar.php'; ?>
					<!-- #postbox-container-1 .postbox-container -->
				</div>
				<!-- #post-body .metabox-holder .columns-2 -->
				<br class="clear">
			</div>
			<!-- #poststuff -->
		</div> <!-- .wrap -->
		<?php
		do_action( 'cbxcoupon_stats_display_admin_end' );
	}

	/**
	 * Display Per month Data
	 */
	public function wcra_permonth() {
		global $wpdb;
		$cbx_single_user_id   = '';
		$cbx_single_coupon_id = '';

		if ( isset( $_GET['u'] ) && isset( $_GET['u'] ) != '' ) {
			$cbx_single_user_id = sanitize_text_field( $_GET['u'] );
		}

		if ( isset( $_GET['c'] ) && isset( $_GET['c'] ) != '' ) {
			$cbx_single_coupon_id = sanitize_text_field( $_GET['c'] );
		}

		$cbx_order_coupons = $this->cborderbycouponforwoocommerce_getallcoupons();

		if ( $cbx_single_coupon_id != '' ) {
			$cbx_single_coupon_id    = (int) $cbx_single_coupon_id;
			$cbx_single_coupon_class = '';
		} else {
			$cbx_single_coupon_id    = '';
			$cbx_single_coupon_class = 'cbcouponreferperyear_listtable';
		}
		?>
		<div class="wrap">
		<div id="icon-options-general" class="icon32"></div>
		<?php echo '<h2 class="wcra_heading">' . __( 'CBX WCRA : Per Month Stats', 'cbxwoocouponreferral' ) . '</h2>'; ?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<!-- main content -->
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
						<!--Top section monthly-->
						<div class="postbox">
							<div class="inside">
								<?php
								$wcra_month = ( isset( $_GET['m'] ) && $_GET['m'] != null ) ? $_GET['m'] : (int) date( 'm' );
								$wcra_year  = ( isset( $_GET['y'] ) && $_GET['y'] != null ) ? $_GET['y'] : (int) date( 'Y' );

								if ( $wcra_month == 12 ) {
									$wcra_prev_month = $wcra_month - 1;
									$wcra_next_month = 1;
									$wcra_prev_year  = $wcra_year;
									$wcra_next_year  = $wcra_year + 1;
								} elseif ( $wcra_month == 1 ) {
									$wcra_prev_month = 12;
									$wcra_next_month = $wcra_month + 1;
									$wcra_prev_year  = $wcra_year - 1;
									$wcra_next_year  = $wcra_year;
								} else {
									$wcra_prev_month = $wcra_month - 1;
									$wcra_next_month = $wcra_month + 1;
									$wcra_prev_year  = $wcra_year;
									$wcra_next_year  = $wcra_year;
								}

								if ( $cbx_single_coupon_id == '' && $cbx_single_user_id == '' ) {
									$prev_link = admin_url( 'admin.php?page=wcrapermonth&m=' . $wcra_prev_month . '&y=' . $wcra_prev_year );
									$next_link = admin_url( 'admin.php?page=wcrapermonth&m=' . $wcra_next_month . '&y=' . $wcra_next_year );
								} else {
									$prev_url = "admin.php?page=wcrapermonth&m=" . $wcra_prev_month . "&y=" . $wcra_prev_year;
									$next_url = "admin.php?page=wcrapermonth&m=" . $wcra_next_month . "&y=" . $wcra_next_year;

									if ( $cbx_single_user_id != '' ) {
										$prev_url .= '&u=' . $cbx_single_user_id;
										$next_url .= '&u=' . $cbx_single_user_id;
									}

									if ( $cbx_single_coupon_id != '' ) {
										$prev_url .= '&c=' . $cbx_single_coupon_id;
										$next_url .= '&c=' . $cbx_single_coupon_id;
									}

									$prev_link = admin_url( $prev_url );
									$next_link = admin_url( $next_url );
								}

								$monthName = date( "F", mktime( 0, 0, 0, $wcra_month, 10 ) );
								?>
								<h2 id="cbcouponrefered_users"
									class="wcra_heading"> <?php echo sprintf( __( 'Referred Users ( %s %d Stats ) ', 'cbxwoocouponreferral' ), $monthName, $wcra_year ); ?>
									<a href="<?php echo $prev_link; ?>"><?php _e( "Prev Month", "cbxwoocouponreferral" ); ?></a>
									<?php
									if ( $wcra_next_year < (int) date( 'Y' ) ) {
										?>
										<a href="<?php echo $next_link; ?>"><?php _e( "Next Month", "cbxwoocouponreferral" ); ?></a>
									<?php } else {
										if ( $wcra_next_month <= (int) date( 'm' ) ) { ?>
											<a href="<?php echo $next_link; ?>"><?php _e( "Next Month", "cbxwoocouponreferral" ); ?></a>
										<?php }
									} ?>
								</h2>
								<?php
								if ( is_array( $cbx_order_coupons ) && ! empty( $cbx_order_coupons ) ) {
									$output       = '';
									$output_table = '<table id = "' . $cbx_single_coupon_class . '" class = "table widefat tablesorter display">
                                                               <thead>
                                                                 <tr>
                                                                   <th style="text-align:center;">' . __( "Coupon", "cbxwoocouponreferral" ) . '</th>
                                                                   <th style="text-align:center;">' . __( "Refer User", "cbxwoocouponreferral" ) . '</th>
                                                                   <th style="text-align:center;">' . sprintf( __( "Monthly Target(%s)", "cbxwoocouponreferral" ), get_woocommerce_currency_symbol() ) . '</th>
                                                                   <th style="text-align:center;">' . sprintf( __( "Monthly Milestone Earned(%s)", "cbxwoocouponreferral" ), get_woocommerce_currency_symbol() ) . '</th>
                                                                   <th style="text-align:center;">' . sprintf( __( "Earned(%s)", "cbxwoocouponreferral" ), get_woocommerce_currency_symbol() ) . '</th>
                                                                   <th style="text-align:center;">' . __( "Current Status", "cbxwoocouponreferral" ) . '</th>';

									$output .= apply_filters( 'wcrapermonth_admin_header_title', $output_table );
									$output .= '</tr></thead><tbody>';

									$per_month_total_user_earning = 0;
									foreach ( $cbx_order_coupons as $cbx_order_coupon ) {
										$cbx_enable_this_row      = false;
										$cbx_populate_coupon      = new WC_Coupon( get_the_title( $cbx_order_coupon['coupon_id'] ) );
										$cbx_populate_coupon_user = get_user_by( 'id', $cbx_order_coupon['user_id'] );
										$cbx_enable_this_row      = ( $cbx_single_coupon_id == '' ) ? true : ( $cbx_single_coupon_id == $cbx_populate_coupon->id ) ? true : false;

										if ( $cbx_enable_this_row ) {

											if ( $cbx_single_user_id != '' && $cbx_single_coupon_id == '' ) {

												if ( $cbx_single_user_id != $cbx_order_coupon['user_id'] ) {
													continue;
												}
											}

											$cbx_populate_coupon_refer_user = $cbx_order_coupon['user_id'];

											$output .= '<tr>
                                                <td align="center"><a href = "' . admin_url( 'admin.php?page=wcrapermonth&m=' . $wcra_month . '&y=' . $wcra_year . '&u=' . (int) $cbx_order_coupon['user_id'] . '&c=' . (int) $cbx_order_coupon['coupon_id'] ) . '"  target="_blank">' . $cbx_populate_coupon->code . '</a></td>
                                                <td align="center"><a href = "' . admin_url( 'admin.php?page=wcrapermonth&m=' . $wcra_month . '&y=' . $wcra_year . '&u=' . (int) $cbx_order_coupon['user_id'] ) . '"  target="_blank">' . $cbx_populate_coupon_user->display_name . '</a></td>
												<td align="center">';
											$cbxordermilestone  = get_post_meta( (int) $cbx_populate_coupon->id, 'cbx_coupon_refer_user_milestone', true );
											$cbxordermilestone_ = ( $cbxordermilestone != false ) ? wcra_price( $cbxordermilestone ) : __( "N/A", "" );

											$output .= $cbxordermilestone_ . ' </td>
                                                <td align="center">';

											extract( $this->wcra_referred_order_per_month_data( $wcra_year, $wcra_month, $cbx_populate_coupon_refer_user, $cbx_populate_coupon->id ) );

											$output .= wcra_price( $cbx_total_refer_amount_value );

											$output .= '</td>
												 <td align="center">';



											$output .= ( $cbx_refer_user_earning_value != 0 ) ? wcra_price( $cbx_refer_user_earning_value ) : 'N/A';

											$per_month_total_user_earning += $cbx_refer_user_earning_value;

											$output .= '</td>

                                                 <td align="center">';
											$user_status = ( $cbx_order_coupon['status'] == 1 ) ? __( "Active", "cbxwoocouponreferral" ) : __( "Inactive", "cbxwoocouponreferral" );
											$output .= $user_status;
											$output .= '</td>';
											$doutput = '';
											//$output .= apply_filters('wcrapermonth_admin_content', $doutput, $cbx_order_coupon, $user_totalorder_earned_exact);
											$output .= apply_filters( 'wcrapermonth_admin_content', $doutput, $cbx_order_coupon, $cbx_refer_user_earning_value );

											$output .= '</tr>';
										} // if show true


									}

									$output .= '<tr><td colspan="3"></td><td align="right">'.sprintf(__('Total User Earing for this Month(%s) = ', 'cbxwoocouponreferral'),get_woocommerce_currency_symbol()  ).'</td><td align="center"><strong>'.wcra_price($per_month_total_user_earning).'</strong></td><td></td></tr>';

									$output .= '</tbody>
                    </table>';
									echo $output;
								}
								?>
							</div>
							<!-- .inside -->
						</div>
						<!-- Bottom section -->
						<?php do_action( 'wcra_user_affiliation_log', $monthName, $wcra_year ); ?>
						<!-- .postbox -->
					</div>
					<!-- .meta-box-sortables .ui-sortable -->
				</div>
				<!-- post-body-content -->
				<!-- sidebar -->
				<?php include 'includes/sidebar.php'; ?>
				<!-- #postbox-container-1 .postbox-container -->
			</div>
			<!-- #post-body .metabox-holder .columns-2 -->
			<br class="clear">
			<!-- #poststuff -->
		</div>
		<!-- .wrap -->
	<?php }

	/**
	 * Per day Referred Order Data
	 *
	 * @param        $orderyear
	 * @param        $month
	 * @param string $coupon_id
	 * @param string $user_id
	 *
	 * @return array
	 */
	public function wcra_referred_order_per_day_data( $orderyear, $month, $day, $user_id = '', $coupon_id = '' ) {
		global $wpdb;
		$data                        = array();
		$cbx_total_refer_amount      = $user_percentage = 0.00;
		$cbx_total_refer_order_count = 0;
		$user_earning                = 0;

		//get_coupon table
		$coupontable_name = $this->get_cborderbycoupon_table_name();

		if ( $coupon_id != '' ) {
			$user_id_refercoupon          = ( $user_id != '' ) ? $user_id : get_post_meta( (int) $coupon_id, 'cbx_coupon_refer_userid', true );
			$sql_query_for_referred_order = "SELECT * FROM $coupontable_name WHERE YEAR(order_date) = %d AND MONTH(order_date) = %d AND DAY(order_date) = %d AND coupon_id = %d AND user_id = %d ";
			$sql                          = $wpdb->prepare( $sql_query_for_referred_order, $orderyear, $month, $day, (int) $coupon_id, (int) $user_id_refercoupon );
		} elseif ( $user_id != '' && $coupon_id == '' ) {
			$sql_query_for_referred_order = "SELECT * FROM $coupontable_name WHERE YEAR(order_date) = %d AND MONTH(order_date) = %d AND DAY(order_date) = %d AND  user_id = %d ";
			$sql                          = $wpdb->prepare( $sql_query_for_referred_order, $orderyear, $month, $day, (int) $user_id );
		} else {
			$sql_query_for_referred_order = "SELECT * FROM $coupontable_name WHERE YEAR(order_date) = %d AND MONTH(order_date) = %d AND DAY(order_date) = %d ";
			$sql                          = $wpdb->prepare( $sql_query_for_referred_order, $orderyear, $month, $day );
		}

		$order_amounts = $wpdb->get_results( $sql, ARRAY_A );

		//for referred orders
		if ( is_array( $order_amounts ) && ! empty( $order_amounts ) ) {
			$orders_unique_array = array();

			foreach ( $order_amounts as $order_amount ) {
				$cbx_total_refer_amount += $order_amount['order_amount'];

				if ( ! in_array( $order_amount['order_id'], $orders_unique_array ) && $order_amount['order_amount'] > 0 ) {
					array_push( $orders_unique_array, $order_amount['order_id'] );
				}

				//var_dump($order_amount['user_earning']);
				$user_earning += (double)$order_amount['user_earning'];
			}
			//$user_percentage             = $order_amount['user_percentage'];
			$cbx_total_refer_order_count = count( $orders_unique_array );
		}

		$data['cbx_total_refer_order_count_value'] 	= $cbx_total_refer_order_count;
		$data['cbx_total_refer_amount_value']      	= wcra_price($cbx_total_refer_amount);
		//$data['cbx_refer_user_percentage_value']   	= $user_percentage;
		$data['cbx_refer_user_earning_value'] 		= wcra_price($user_earning);

		return $data;
	}

	/**
	 * Per Month Referred Order Data
	 *
	 * @param        $orderyear
	 * @param        $month
	 * @param string $coupon_id
	 * @param string $user_id
	 *
	 * @return array
	 */
	public function wcra_referred_order_per_month_data_shortcode( $orderyear, $month, $user_id = '', $coupon_id = '' ) {
		global $wpdb;
		$data                        = array();
		$cbx_total_refer_amount      = $user_percentage = 0.00;
		$cbx_total_refer_order_count = 0;
		$user_earning                = 0;

		//get_coupon table
		$coupontable_name = $this->get_cborderbycoupon_table_name();

		if ( $coupon_id != '' ) {
			$user_id_refercoupon          = ( $user_id != '' ) ? $user_id : get_post_meta( (int) $coupon_id, 'cbx_coupon_refer_userid', true );
			$sql_query_for_referred_order = "SELECT * FROM $coupontable_name WHERE YEAR(order_date) = %d AND MONTH(order_date) = %d AND coupon_id = %d AND user_id = %d ";
			$sql                          = $wpdb->prepare( $sql_query_for_referred_order, $orderyear, $month, (int) $coupon_id, (int) $user_id_refercoupon );
		} elseif ( $user_id != '' && $coupon_id == '' ) {
			$sql_query_for_referred_order = "SELECT * FROM $coupontable_name WHERE YEAR(order_date) = %d AND MONTH(order_date) = %d AND  user_id = %d ";
			$sql                          = $wpdb->prepare( $sql_query_for_referred_order, $orderyear, $month, (int) $user_id );
		} else {
			$sql_query_for_referred_order = "SELECT * FROM $coupontable_name WHERE YEAR(order_date) = %d AND MONTH(order_date) = %d ";
			$sql                          = $wpdb->prepare( $sql_query_for_referred_order, $orderyear, $month );
		}

		$order_amounts = $wpdb->get_results( $sql, ARRAY_A );

		if ( $user_id == '' ) {
			//for overall referred orders
			if ( is_array( $order_amounts ) && ! empty( $order_amounts ) ) {
				$orders_unique_array = array();

				foreach ( $order_amounts as $order_amount ) {
					if ( ! in_array( $order_amount['order_id'], $orders_unique_array ) ) {

						$order = new WC_Order( $order_amount['order_id'] );

						$get_order_item_totals = $order->get_order_item_totals();

						$discount = $order->get_total_discount( $order->tax_display_cart === 'excl' && $order->display_totals_ex_tax );
						$subtotal = $this->cart_subtotal_temp( $order, false, $order->tax_display_cart );

						$order_total = (float) $subtotal - (float) $discount;

						if ( $order->post_status == 'wc-cancelled' ) {
							$cbx_total_refer_amount -= $order_total - $order->get_total_refunded();
						}
						$cbx_total_refer_amount += $order_total - $order->get_total_refunded();

						array_push( $orders_unique_array, $order_amount['order_id'] );

						$user_earning += (double)$order_amount['user_earning'];

					}
				}
				//$user_percentage             = $order_amount['user_percentage'];
				$cbx_total_refer_order_count = count( $orders_unique_array );
			}
		} else {
			//for specific user order
			if ( is_array( $order_amounts ) && ! empty( $order_amounts ) ) {
				$orders_unique_array = array();

				foreach ( $order_amounts as $order_amount ) {
					$cbx_total_refer_amount += $order_amount['order_amount'];

					if ( ! in_array( $order_amount['order_id'], $orders_unique_array ) && $order_amount['order_amount'] > 0 ) {
						array_push( $orders_unique_array, $order_amount['order_id'] );
					}
				}
				//$user_percentage             = $order_amount['user_percentage'];
				$cbx_total_refer_order_count = count( $orders_unique_array );
				$user_earning += (double)$order_amount['user_earning'];
			}
		}





		$data['cbx_total_refer_order_count_value'] 	= $cbx_total_refer_order_count;
		$data['cbx_total_refer_amount_value']      	= $cbx_total_refer_amount;
		//$data['cbx_refer_user_percentage_value']   	= $user_percentage;
		$data['cbx_refer_user_earning_value'] 		= $user_earning;

		return $data;

	}

	/**
	 * Per Month Referred Order Data
	 *
	 * @param        $orderyear
	 * @param        $month
	 * @param string $coupon_id
	 * @param string $user_id
	 *
	 * @return array
	 */
	public function wcra_referred_order_per_month_data( $orderyear, $month, $user_id = '', $coupon_id = '' ) {
		global $wpdb;
		$data                        = array();
		$cbx_total_refer_amount      = $user_percentage = 0.00;
		$user_earning                = 0;
		$cbx_total_refer_order_count = 0;

		//get_coupon table
		$coupontable_name = $this->get_cborderbycoupon_table_name();

		if ( $coupon_id != '' ) {
			$user_id_refercoupon          = ( $user_id != '' ) ? $user_id : get_post_meta( (int) $coupon_id, 'cbx_coupon_refer_userid', true );
			$sql_query_for_referred_order = "SELECT * FROM $coupontable_name WHERE YEAR(order_date) = %d AND MONTH(order_date) = %d AND coupon_id = %d AND user_id = %d ";
			$sql                          = $wpdb->prepare( $sql_query_for_referred_order, $orderyear, $month, (int) $coupon_id, (int) $user_id_refercoupon );
		} elseif ( $user_id != '' && $coupon_id == '' ) {
			$sql_query_for_referred_order = "SELECT * FROM $coupontable_name WHERE YEAR(order_date) = %d AND MONTH(order_date) = %d AND  user_id = %d ";
			$sql                          = $wpdb->prepare( $sql_query_for_referred_order, $orderyear, $month, (int) $user_id );
		} else {
			$sql_query_for_referred_order = "SELECT * FROM $coupontable_name WHERE YEAR(order_date) = %d AND MONTH(order_date) = %d ";
			$sql                          = $wpdb->prepare( $sql_query_for_referred_order, $orderyear, $month );
		}

		$order_amounts = $wpdb->get_results( $sql, ARRAY_A );


		if ( $user_id == '' ) {

			//for overall referred orders
			if ( is_array( $order_amounts ) && ! empty( $order_amounts ) ) {
				$orders_unique_array = array();

				foreach ( $order_amounts as $order_amount ) {


					if ( ! in_array( $order_amount['order_id'], $orders_unique_array ) ) {

						$order = new WC_Order( $order_amount['order_id'] );

						$get_order_item_totals = $order->get_order_item_totals();


						$discount = $order->get_total_discount( $order->tax_display_cart === 'excl' && $order->display_totals_ex_tax );
						$subtotal = $this->cart_subtotal_temp( $order, false, $order->tax_display_cart );

						$order_total = (float) $subtotal - (float) $discount;

						if ( $order->post_status == 'wc-cancelled' ) {
							$cbx_total_refer_amount -= $order->get_total() - $order->get_total_refunded();
						}
						$cbx_total_refer_amount += $order->get_total() - $order->get_total_refunded();
						array_push( $orders_unique_array, $order_amount['order_id'] );


						//$user_earning += (double)$order_amount['user_earning'];
					}

					//var_dump($order_amount['user_earning']);
					$user_earning += (double)$order_amount['user_earning'];
				}

				//$user_percentage             = $order_amount['user_percentage'];
				//$user_percentage_type        = $order_amount['user_percentage_type'];
				$cbx_total_refer_order_count = count( $orders_unique_array );


				$data['cbx_total_refer_order_count_value'] = $cbx_total_refer_order_count;
				$data['cbx_total_refer_amount_value']      = wcra_price($cbx_total_refer_amount);
				//$data['cbx_refer_user_percentage_value']   	= $user_percentage;
				//$data['cbx_refer_user_percentage_type']   	= $user_percentage_type;
				$data['cbx_refer_user_earning_value'] = wcra_price($user_earning);
			} else {
				$data['cbx_total_refer_order_count_value'] = 0;
				$data['cbx_total_refer_amount_value']      = wcra_price(0);
				//$data['cbx_refer_user_percentage_value']   	= 0;
				//$data['cbx_refer_user_percentage_type']   	= 0;
				$data['cbx_refer_user_earning_value'] = wcra_price(0);
			}
		} else {
			//for specific user order
			if ( is_array( $order_amounts ) && ! empty( $order_amounts ) ) {
				$orders_unique_array = array();

				foreach ( $order_amounts as $order_amount ) {

					//var_dump($order_amount);

					$cbx_total_refer_amount += $order_amount['order_amount'];
					$user_earning += (double)$order_amount['user_earning'];

					if ( ! in_array( $order_amount['order_id'], $orders_unique_array ) && $order_amount['order_amount'] > 0 ) {
						array_push( $orders_unique_array, $order_amount['order_id'] );
					}
				}

				//$user_percentage             = $order_amount['user_percentage'];
				//$user_percentage_type        = $order_amount['user_percentage_type'];
				$cbx_total_refer_order_count = count( $orders_unique_array );


				$data['cbx_total_refer_order_count_value'] = $cbx_total_refer_order_count;
				$data['cbx_total_refer_amount_value']      = wcra_price($cbx_total_refer_amount);
				//$data['cbx_refer_user_percentage_value']   	= $user_percentage;
				//$data['cbx_refer_user_percentage_type']   	= $user_percentage_type;
				$data['cbx_refer_user_earning_value'] = wcra_price($user_earning);
			} else {
				$data['cbx_total_refer_order_count_value'] = 0;
				$data['cbx_total_refer_amount_value']      = wcra_price(0);
				//$data['cbx_refer_user_percentage_value']   	= 0;
				//$data['cbx_refer_user_percentage_type']   	= 0;
				$data['cbx_refer_user_earning_value'] = wcra_price(0);
			}
		}

		return $data;
	}

	/**
	 * Per Month Order Data
	 *
	 * @param        $orderyear
	 * @param        $i
	 * @param string $coupon_id
	 */
	public function wcra_order_per_month_data( $orderyear, $month, $user_id = '', $coupon_id = '' ) {
		$data                               = $cbx_populate_orders = array();
		$cbx_populate_orders_permonth_total = $cbx_total_all_orders_count = 0;

		global $post;
		$data                               = $cbx_populate_orders = array();
		$cbx_populate_orders_permonth_total = $cbx_total_all_orders_count = 0;

		$orders = get_posts(
			array(
				'post_type'   => 'shop_order',
				'post_status' => array( 'wc-completed' ),
				//'post_status' => array_keys( wc_get_order_statuses() ),
				'date_query'  => array(
					array(
						'year'  => $orderyear,
						'month' => $month
					),
				),
			)
		);

		foreach ( $orders as $order ) :
			setup_postdata( $order );

			$cbx_populate_orders_permonth_total += (float) get_post_meta( $order->ID, '_order_total', true );
			$cbx_total_all_orders_count += 1;
			array_push( $cbx_populate_orders, $order->ID );

		endforeach;
		wp_reset_postdata();

		//end order

		$data['cbx_populate_orders_permonth_total_value'] = wcra_price($cbx_populate_orders_permonth_total);
		$data['cbx_total_all_orders_count_value']         = $cbx_total_all_orders_count;
		$data['cbx_populate_orders_value']                = $cbx_populate_orders;

		return $data;
	}

	/**
	 * Per Month Refund Data
	 *
	 * @param        $orderyear
	 * @param        $month
	 * @param string $coupon_id
	 */
	public function wcra_refund_per_month_data( $orderyear, $month, $user_id = '', $coupon_id = '' ) {
		$data                                = $cbx_populate_refunds = array();
		$cbx_populate_refunds_permonth_total = $cbx_total_all_refunds_count = 0;

		global $post;
		$data                                = $cbx_populate_refunds = array();
		$cbx_populate_refunds_permonth_total = $cbx_total_all_refunds_count = 0;

		$refunds = get_posts(
			array(
				'post_type'   => 'shop_order_refund',
				'post_status' => array( 'wc-completed' ),
				//'post_status' => array_keys( wc_get_order_statuses() ),
				'date_query'  => array(
					array(
						'year'  => $orderyear,
						'month' => $month
					),
				),
			)
		);

		foreach ( $refunds as $refund ) :
			setup_postdata( $refund );

			$cbx_populate_refunds_permonth_total += (float) get_post_meta( $refund->ID, '_order_total', true );
			$cbx_total_all_refunds_count += 1;
			array_push( $cbx_populate_refunds, $refund->ID );

		endforeach;
		wp_reset_postdata();
		//end order

		$data['cbx_populate_refunds_permonth_total_value'] = wcra_price($cbx_populate_refunds_permonth_total);
		$data['cbx_total_all_refunds_count_value']         = $cbx_total_all_refunds_count;
		$data['cbx_populate_refunds_value']                = $cbx_populate_refunds;

		return $data;
	}

	/**
	 * Ajax function for month data
	 */
	public function cb_ajax_cbrefercoupon_orderbymonths() {
		check_ajax_referer( 'cbxwoocouponreferral_nonce', 'security' );

		if ( isset( $_POST['cborderbycoupon_year'] ) && $_POST['cborderbycoupon_year'] != '' ) {
			$cbrefercoupon_year = intval( $_POST['cborderbycoupon_year'] );
		} else {
			$cbrefercoupon_year = '';
		}

		if ( isset( $_POST['cborderbycoupon_month'] ) && $_POST['cborderbycoupon_month'] != '' ) {
			$cbrefercoupon_month = intval( $_POST['cborderbycoupon_month'] );
		} else {
			$cbrefercoupon_month = '';
		}
		if ( isset( $_POST['cborderbycoupon_coupon'] ) && $_POST['cborderbycoupon_coupon'] != '' ) {
			$coupon = intval( $_POST['cborderbycoupon_coupon'] );
		} else {
			$coupon = '';
		}

		// check type
		$cborderbycoupon_nextmonth_orders         = $this->wcra_per_month_data( (int) $cbrefercoupon_year, (int) $cbrefercoupon_month, $coupon );
		$cborderbycoupon_nextmonth_orders['type'] = 'permonth';
		//cborderbycoupon_type
		if ( isset( $_POST['cborderbycoupon_type'] ) && $_POST['cborderbycoupon_type'] == 'mail' ) {
			do_action( 'wcra_monthly_notification', (int) $cbrefercoupon_year, $cborderbycoupon_nextmonth_orders, (int) $cbrefercoupon_month );
		}
		echo json_encode( $cborderbycoupon_nextmonth_orders );
		die();
	}

	/**
	 * Ajax function for year data
	 */
	public function cb_ajax_cbrefercoupon_orderbyyears() {
		check_ajax_referer( 'cbxwoocouponreferral_nonce', 'security' );

		if ( isset( $_POST['cborderbycoupon_year'] ) && $_POST['cborderbycoupon_year'] != '' ) {
			$cbrefercoupon_year = intval( $_POST['cborderbycoupon_year'] );
		} else {
			$cbrefercoupon_year = '';
		}
		if ( isset( $_POST['cborderbycoupon_coupon'] ) && $_POST['cborderbycoupon_coupon'] != '' ) {

			$coupon = intval( $_POST['cborderbycoupon_coupon'] );
		} else {
			$coupon = '';
		}

		if ( isset( $_POST['cborderbycoupon_ref'] ) && $_POST['cborderbycoupon_ref'] != '' ) {
			$ref = esc_attr( $_POST['cborderbycoupon_ref'] );
		} else {
			$ref = '';
		}

		// check type
		$cborderbycoupon_nextyear_orders         = $this->wcra_per_year_data( (int) $cbrefercoupon_year, $coupon, $ref );
		$cborderbycoupon_nextyear_orders['type'] = 'peryear';

		//cborderbycoupon_type
		if ( isset( $_POST['cborderbycoupon_type'] ) && $_POST['cborderbycoupon_type'] == 'mail' ) {
			do_action( 'wcra_yearly_notification', (int) $cbrefercoupon_year, $cborderbycoupon_nextyear_orders );
		}
		echo json_encode( $cborderbycoupon_nextyear_orders );
		die();
	}

	/**
	 * Get Per Year data
	 *
	 * @param string $year
	 * @param string $coupon_id
	 *
	 * @return array
	 */
	public function wcra_per_month_data( $year = '', $month = '', $coupon_id = '' ) {
		// for drawing graph
		global $wpdb;

		$getdate             = getdate();
		$cbxstat_month_names = self::$shortmonthname;

		if ( $year == '' ) {
			$year = (int) $getdate["year"];
		}

		if ( $year % 4 == 0 ) {
			$cbxstat_days_of_month = array( 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
		} else {
			$cbxstat_days_of_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
		}

		if ( $month == '' ) {
			$month = (int) $getdate["mon"];
		}
		$days_of_this_month = $cbxstat_days_of_month[$month - 1];
		//for referred order

		for ( $i = 1; $i <= $days_of_this_month; $i ++ ) {
			extract( $this->wcra_referred_order_per_day_data( $year, $month, $i, $user_id = '', $coupon_id ) );

			$cbx_total_refer_amount[$i]      	= wcra_price($cbx_total_refer_amount_value);
			$cbx_total_refer_order_count[$i] 	= $cbx_total_refer_order_count_value;

			//$cbx_refer_user_percentage[$i]   = $cbx_refer_user_percentage_value;
			$cbx_refer_user_earning[$i] 		= wcra_price($cbx_refer_user_earning_value);
		}

		return array(
			'count'        => $cbx_total_refer_order_count,
			'filtered'     => $cbx_total_refer_amount,
			'coupon_id'    => $coupon_id,
			//'user_percentage' => $cbx_refer_user_earning
			'user_earning' => $cbx_refer_user_earning
		);
	}

	/**
	 * Get Per Year data
	 *
	 * @param string $year
	 * @param string $coupon_id
	 *
	 * @return array
	 */
	public function wcra_per_year_data( $year = '', $coupon_id = '', $ref = '' ) {
		// for drawing graph
		global $wpdb;
		$cbx_populate_orders = $cbx_populate_orders_permonth_total = array();

		for ( $i = 1; $i < 13; $i ++ ) {

			$cbx_populate_orders[$i]                = array();
			$cbx_populate_orders_permonth_total[$i] = 0.0;
			if ( $year == '' ) {
				$getdate   = getdate();
				$orderyear = $getdate["year"];
			} else {
				$orderyear = $year;
			}

			//for all order
			extract( $this->wcra_order_per_month_data( $orderyear, $i ) );

			$cbx_populate_orders_permonth_total[$i] = $cbx_populate_orders_permonth_total_value;
			$cbx_total_all_order_count[$i]          = $cbx_total_all_orders_count_value;
			$cbx_populate_orders[$i]                = $cbx_populate_orders_value;
			//end order


			//for all refund
			extract( $this->wcra_refund_per_month_data( $orderyear, $i ) );

			$cbx_populate_orders_permonth_total[$i] += doubleval($cbx_populate_refunds_permonth_total_value);
			$cbx_populate_orders[$i] = $cbx_populate_refunds_value;
			//end refund


			//for referred order

			if ( $ref == 'shortcode' ) {
				extract( $this->wcra_referred_order_per_month_data_shortcode( $orderyear, $i, $user_id = '', $coupon_id ) );
			} else {
				extract( $this->wcra_referred_order_per_month_data( $orderyear, $i, $user_id = '', $coupon_id ) );
			}

			//var_dump( $cbx_refer_user_earning );

			$cbx_total_refer_order_count[$i] 	= $cbx_total_refer_order_count_value;
			$cbx_total_refer_amount[$i]      	= wcra_price($cbx_total_refer_amount_value);
			//$cbx_refer_user_percentage[$i]   = $cbx_refer_user_percentage_value;
			$cbx_refer_user_earning[$i] 		= wcra_price($cbx_refer_user_earning_value);
			//end referred order

			$cbx_populate_orders_permonth_total[$i] = wcra_price($cbx_populate_orders_permonth_total[$i]);

		}



		// end of for month
		return array(
			'all'             	=> $cbx_populate_orders_permonth_total,
			'all_count'       	=> $cbx_total_all_order_count,
			'count'           	=> $cbx_total_refer_order_count,
			'filtered'        	=> $cbx_total_refer_amount,
			'coupon_id'       	=> $coupon_id,
			//'user_percentage' => $cbx_refer_user_earning
			'user_earning' 		=> $cbx_refer_user_earning
		);
	}
}


/**
 * Format the price with a currency symbol.
 *
 * @param float $price
 * @param array $args (default: array())
 *
 * @return string
 */
function wcra_price( $price, $args = array() ) {
	extract(
		apply_filters(
			'wc_price_args', wp_parse_args(
				$args, array(
					'currency'           => '',
					'decimal_separator'  => wc_get_price_decimal_separator(),
					'thousand_separator' => wc_get_price_thousand_separator(),
					'decimals'           => wc_get_price_decimals(),
					'price_format'       => get_woocommerce_price_format()
				)
			)
		)
	);

	$negative = $price < 0;
	$price    = apply_filters( 'raw_woocommerce_price', floatval( $negative ? $price * - 1 : $price ) );
	$price    = apply_filters( 'formatted_woocommerce_price', number_format( $price, $decimals, $decimal_separator, $thousand_separator ), $price, $decimals, $decimal_separator, $thousand_separator );

	/*if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $decimals > 0 ) {
			$price = wc_trim_zeros( $price );
		}*/

	$formatted_price = ( $negative ? '-' : '' ) . $price;


	return $formatted_price;
	//$return          = '<span class="woocommerce-Price-amount amount">' . $formatted_price . '</span>';


	//return apply_filters( 'wc_price', $return, $price, $args );
}

/**
 * Load WCRA Plugin when all plugins loaded
 *
 * @return void
 */
function wcra_load_plugin() {
	new CBXWooCouponReferral();
}

add_action( 'plugins_loaded', 'wcra_load_plugin', 5 );