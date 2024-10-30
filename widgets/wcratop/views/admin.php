<!-- This file is used to markup the administration form of the widget. -->
<!-- Custom  Title Field -->
<p>
	<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', "cbxwoocouponreferral" ); ?></label>

	<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Type', "cbxwoocouponreferral" ); ?></label>
	<select class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>">
		<option value=""><?php _e( 'Please Select', 'cbxwoocouponreferral' ); ?></option>
		<option value="month" <?php selected( $type, 'month', true ); ?> ><?php _e( 'Month', 'cbxwoocouponreferral' ); ?></option>
		<option value="year" <?php selected( $type, 'year', true ); ?> ><?php _e( 'Year', 'cbxwoocouponreferral' ); ?></option>
	</select>
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Count', "cbxwoocouponreferral" ); ?></label>

	<input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="text" value="<?php echo $count; ?>" />
</p>

<p>
	<label for="<?php echo $this->get_field_id( 'order_by' ); ?>"><?php _e( 'Order By', "cbxwoocouponreferral" ); ?></label>
	<select class="widefat" id="<?php echo $this->get_field_id( 'order_by' ); ?>" name="<?php echo $this->get_field_name( 'order_by' ); ?>">
		<option value="total_earning" <?php selected( $order_by, 'total_earning', true ); ?> ><?php _e( 'Total Earning', 'cbxwoocouponreferral' ); ?></option>
		<option value="total_amount" <?php selected( $order_by, 'total_amount', true ); ?> ><?php _e( 'Total Amount', 'cbxwoocouponreferral' ); ?></option>
		<option value="total_referred" <?php selected( $order_by, 'total_referred', true ); ?> ><?php _e( 'Total Referred', 'cbxwoocouponreferral' ); ?></option>
	</select>
</p>

<p>
	<label for="<?php echo $this->get_field_id( 'order' ); ?>"><?php _e( 'Order', "cbxwoocouponreferral" ); ?></label>
	<select class="widefat" id="<?php echo $this->get_field_id( 'order' ); ?>" name="<?php echo $this->get_field_name( 'order' ); ?>">
		<option value="desc" <?php selected( $order, 'desc', true ); ?> ><?php _e( 'DESC', 'cbxwoocouponreferral' ); ?></option>
		<option value="asc" <?php selected( $order, 'asc', true ); ?> ><?php _e( 'ASC', 'cbxwoocouponreferral' ); ?></option>
	</select>
</p>

<input type="hidden" id="<?php echo $this->get_field_id( 'submit' ); ?>" name="<?php echo $this->get_field_name( 'submit' ); ?>" value="1" />
<?php
do_action( 'wcratop_form_admin', $instance, $this )
?>