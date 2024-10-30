<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 *
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 *
 * Our theme for this list table is going to be movies.
 */
class WCRAAffiliators extends WP_List_Table {


	/** ************************************************************************
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 ***************************************************************************/
	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct(
			array(
				'singular' => 'affiliator',     //singular name of the listed records
				'plural'   => 'affiliators',    //plural name of the listed records
				'ajax'     => false      //does this table support ajax?
			)
		);

	}


	/** ************************************************************************
	 * Recommended. This method is called when the parent class can't find a method
	 * specifically build for a given column. Generally, it's recommended to include
	 * one method for each column you want to render, keeping your package class
	 * neat and organized. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title()
	 * exists - if it does, that method will be used. If it doesn't, this one will
	 * be used. Generally, you should try to use custom column methods as much as
	 * possible.
	 *
	 * Since we have defined a column_title() method later on, this method doesn't
	 * need to concern itself with any column with a name of 'title'. Instead, it
	 * needs to handle everything else.
	 *
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param array $item        A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 *
	 * @return string Text or HTML to be placed inside the column <td>
	 **************************************************************************/
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return $item[$column_name];
			case 'user_id':
				return $item[$column_name];
			case 'coupon_id':
				return $item[$column_name];
			case 'status':
				return $item[$column_name];
			case 'added_date':
				return $item[$column_name];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Callback for collumn 'id'
	 *
	 * @param array $item
	 *
	 * @return string
	 */

	function column_id( $item ) {
		$user_id    = $item['user_id'];
		$coupon_id  = $item['coupon_id'];
		$date       = $item['added_date'];
		$date_stamp = strtotime( $date );

		//var_dump($date);


		$aff_link = admin_url( 'admin.php?page=wcrapermonth&m=' . (int) date( 'm', $date_stamp ) . '&y=' . (int) date( 'Y', $date_stamp ) . '&u=' . (int) $user_id . '&c=' . (int) $coupon_id );


		return '<a href= "' . $aff_link . '" target = "_blank">' . $item['id'] . '</a>';
	}

	/**
	 * Callback for collumn 'user_id'
	 *
	 * @param array $item
	 *
	 * @return string
	 */

	function column_user_id( $item ) {
		$user_info = get_userdata( $item['user_id'] );

		return '<a href= ' . get_edit_user_link( $item['user_id'] ) . ' target = "_blank">' . $user_info->display_name . '</a>';
	}


	/**
	 * Callback for collumn 'order_id'
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_coupon_id( $item ) {


		$cbx_xoupon_refer_user_target   = get_post_meta( $item['coupon_id'], 'cbx_coupon_refer_user_milestone', true );
		$cbx_xoupon_refer_user_perc     = get_post_meta( $item['coupon_id'], 'cbx_coupon_refer_user_percent', true );
		$cbx_xoupon_refer_user_perctype = get_post_meta( $item['coupon_id'], 'cbx_coupon_refer_userpercent_type', true );

		$cbx_xoupon_refer_user    = get_post_meta( $item['coupon_id'], 'cbx_coupon_refer_user', true );
		$cbx_xoupon_refer_user_id = get_post_meta( $item['coupon_id'], 'cbx_coupon_refer_userid', true );

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
			$percentage_sign       = ( $cbx_xoupon_refer_user_perctype == 1 ) ? '%' : '';
		}

		if ( $item['status'] == 1 ) {
			return '<a href= ' . get_edit_post_link( $item['coupon_id'] ) . ' target = "_blank">' . get_the_title( $item['coupon_id'] ) . '</a>' . '(' . $cbx_xoupon_refer_user_perc . $percentage_sign . ')';
		} elseif ( $item['status'] == 0 ) {
			return get_the_title( $item['coupon_id'] );
		} else {
			return get_the_title( $item['coupon_id'] );
		}


	}

	/**
	 * Callback for collumn 'status'
	 *
	 * @param array $item
	 *
	 * @return string
	 */

	function column_status( $item ) {

		if ( $item['status'] == 1 ) {
			return __( 'Active', 'cbxwoocouponreferral' );
		} elseif ( $item['status'] == 0 ) {
			return __( 'Inactive', 'cbxwoocouponreferral' );
		} else {
			return $item['status'];
		}
	}


	/** ************************************************************************
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/
			$this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/
			$item['id']                //The value of the checkbox should be the record's id
		);
	}


	/** ************************************************************************
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value
	 * is the column's title text. If you need a checkbox for bulk actions, refer
	 * to the $columns array below.
	 *
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	function get_columns() {
		$columns = array(
			//'cb'         => '<input type="checkbox" />', //Render a checkbox instead of text
			'user_id'    => __( 'User', 'cbxwoocouponreferral' ),
			'coupon_id'  => __( 'Coupon', 'cbxwoocouponreferral' ),
			'status'     => __( 'Status', 'cbxwoocouponreferral' ),
			'added_date' => __( 'Date', 'cbxwoocouponreferral' ),
			'id'         => __( 'ID', 'cbxwoocouponreferral' )
		);

		return $columns;
	}


	/** ************************************************************************
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
	 * you will need to register it here. This should return an array where the
	 * key is the column that needs to be sortable, and the value is db column to
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 **************************************************************************/
	function get_sortable_columns() {
		$sortable_columns = array(
			'id'         => array( 'id', false ),
			'user_id'    => array( 'user_id', false ),     //true means it's already sorted
			'coupon_id'  => array( 'coupon_id', false ),
			'status'     => array( 'status', false ),
			'added_date' => array( 'added_date', false ),
		);

		return $sortable_columns;
	}


	/** ************************************************************************
	 * Optional. If you need to include bulk actions in your list table, this is
	 * the place to define them. Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * If this method returns an empty value, no bulk action will be rendered. If
	 * you specify any bulk actions, the bulk actions box will be rendered with
	 * the table automatically on display().
	 *
	 * Also note that list tables are not automatically wrapped in <form> elements,
	 * so you will need to create those manually in order for bulk actions to function.
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	function get_bulk_actions() {
		/*$actions = array(
			'delete'    => 'Delete'
		);*/
		$actions = array();

		return $actions;
	}


	/** ************************************************************************
	 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
	 * For this example package, we will handle it in the class to keep things
	 * clean and organized.
	 *
	 * @see $this->prepare_items()
	 **************************************************************************/
	function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			wp_die( __( 'Items deleted (or they would be if we had items to delete)!', 'cbxwoocouponreferral' ) );
		}

	}


	/** ************************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 *
	 * @global WPDB $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 **************************************************************************/
	function prepare_items() {
		global $wpdb; //This is used only if making any database queries

		/**
		 * First, lets decide how many records per page to show
		 */
		$user   = get_current_user_id();
		$screen = get_current_screen();

		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently
		 * looking at. We'll need this later, so you should always include it in
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();


		$option_name = $screen->get_option( 'per_page', 'option' ); //the core class name is WP_Screen


		$per_page = intval( get_user_meta( $user, $option_name, true ) );


		if ( $per_page == 0 ) {
			$per_page = intval( $screen->get_option( 'per_page', 'default' ) );
		}

		$order   = ( isset( $_GET['order'] ) && $_GET['order'] != '' ) ? $_GET['order'] : 'asc';
		$orderby = ( isset( $_GET['orderby'] ) && $_GET['orderby'] != '' ) ? $_GET['orderby'] : 'id';


		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();


		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );


		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();


		//$data = $wpdb->get_results("$sql_select  WHERE  $do_search",'ARRAY_A');
		$data = $this->getData( $orderby, $order, $per_page, $current_page );


		//usort($data, 'usort_reorder');
		//usort( $data, array( $this, 'usort_reorder' ) );


		/***********************************************************************
		 * ---------------------------------------------------------------------
		 * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
		 *
		 * In a real-world situation, this is where you would place your query.
		 *
		 * For information on making queries in WordPress, see this Codex entry:
		 * http://codex.wordpress.org/Class_Reference/wpdb
		 *
		 * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
		 * ---------------------------------------------------------------------
		 **********************************************************************/


		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array.
		 * In real-world use, this would be the total number of items in your database,
		 * without filtering. We'll need this later, so you should always include it
		 * in your own package classes.
		 */
		$total_items = intval( $this->getDataCount( $orderby, $order ) );


		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		//$data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
		//$data = $data, (($current_page - 1) * $per_page), $per_page);


		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;


		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,                  //WE have to calculate the total number of items
				'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $per_page )   //WE have to calculate the total number of pages
			)
		);
	}

	/**
	 * This checks for sorting input and sorts the data in our array accordingly.
	 *
	 * In a real-world situation involving a database, you would probably want
	 * to handle sorting by passing the 'orderby' and 'order' values directly
	 * to a custom query. The returned data will be pre-sorted, and this array
	 * sorting technique would be unnecessary.
	 */
	function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'id'; //If no sort, default to title
		$order   = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
		$result  = strcmp( $a[$orderby], $b[$orderby] ); //Determine sort order
		return ( $order === 'asc' ) ? $result : - $result; //Send final sort direction to usort
	}


	/**
	 * Get Data
	 *
	 * @param int $perpage
	 * @param int $page
	 *
	 * @return array|null|object
	 */
	function  getData( $orderby = 'id', $order = 'asc', $perpage = 20, $page = 1 ) {

		global $wpdb;


		$sql_select = "SELECT * FROM {$wpdb->prefix}wcra_referral_user";


		$search = '';
		if ( isset( $_REQUEST['s'] ) ) {
			if ( $_REQUEST['s'] == 'active' ) {
				$search = '1';
			} elseif ( $_REQUEST['s'] == 'inactive' ) {
				$search = '0';
			} else {
				$search = '';
			}
		}
		$do_search = ( $search != '' ) ? $wpdb->prepare( " status = '%s' ", $search ) : '1';

		$start_point = ( $page * $perpage ) - $perpage;
		$limit_sql   = "LIMIT";
		$limit_sql .= ' ' . $start_point . ',';
		$limit_sql .= ' ' . $perpage;

		$sortingOrder = '';

		$sortingOrder = " ORDER BY $orderby $order ";


		$data = $wpdb->get_results( "$sql_select  WHERE  $do_search $sortingOrder  $limit_sql", 'ARRAY_A' );

		return $data;
	}

	/**
	 * Get total data count
	 *
	 * @param int $perpage
	 * @param int $page
	 *
	 * @return array|null|object
	 */
	function  getDataCount( $orderby = 'id', $order = 'asc' ) {

		global $wpdb;

		$sql_select = "SELECT COUNT(*) FROM {$wpdb->prefix}wcra_referral_user";


		$search = '';
		if ( isset( $_REQUEST['s'] ) ) {
			if ( $_REQUEST['s'] == 'active' ) {
				$search = '1';
			} elseif ( $_REQUEST['s'] == 'inactive' ) {
				$search = '0';
			} else {
				$search = '';
			}
		}
		$do_search = ( $search != '' ) ? $wpdb->prepare( " status = '%s' ", $search ) : '1';

		$sortingOrder = '';

		$sortingOrder = " ORDER BY $orderby $order ";


		$count = $wpdb->get_var( "$sql_select  WHERE  $do_search $sortingOrder" );

		return $count;
	}


}