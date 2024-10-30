function wcra_draw_graph(data, $element, $) {



	//var _this = context;
	var count = 0;
	var coupon_data_all = [];
	var coupon_data_filterd = [];

	var cbx_populate_orders_permonth_total = data.all;
	var cbx_total_refer_amount = data.filtered;
	var cbx_coupon_id = data.coupon_id;
	var cbx_count = data.count;
	var cbx_all_count = data.all_count;

	var user_earning_value = data.user_earning;



	//per month data graph
	if (data.type == 'permonth') {

		for (key in cbx_total_refer_amount) {
			coupon_data_filterd.push(cbx_total_refer_amount[key]);
			count++;
		}
		var coupon_data_filterd = $.map(coupon_data_filterd, function (value, index) {
			return [[index + 1, value]];
		});

		//var root = $element.find('.cborderbycouponforwoocommerce_wrapper');

		//$(root).show();

		$element.find('.cborderbycouponforwoocommerce_wrapper').show();

		//show graph
		new Chartkick.ColumnChart(
			"cbcouponreferpermonth",
			[
				{"name": wcrafront.wcraoagraph_caption, "data": coupon_data_filterd},
			],
			{"discrete": true, "colors": ["#4285F4", "#db4437"]}
		);

		analysis_table_for_orders_ = '<h2 class="wcra_heading" id="cbcouponreferperyear_overview"> ' + wcrafront.wcraoo_header + ' <span class="cbcouponreferperyear_title_date"></span></h2><table class="table widefat tablesorter display">';
		analysis_table_for_orders_ += '<thead><tr><th style="text-align:center;">' + wcrafront.wcraootable_day + '</th><th style="text-align:center;">' + wcrafront.wcraootable_amt + '</th><th style="text-align:center;">' + wcrafront.wcraootable_number + '</th><th style="text-align:center;">' + wcrafront.wcraootable_earn + '</th></tr></thead><tbody>';

		var monthearn_of_thecoupon = 0.00;

		for (count; count > 0; count--) {
			//var sign = '';
			analysis_table_for_orders_ += '<tr>';
			analysis_table_for_orders_ += '<td class="wcra_center">' + count + '</td>';
			analysis_table_for_orders_ += '<td class="wcra_center">' + cbx_total_refer_amount[count] + '</td>';
			analysis_table_for_orders_ += '<td class="wcra_center">' + cbx_count[count] + '</td>';

			monthearn_of_thecoupon += parseFloat(user_earning_value[count]);

			analysis_table_for_orders_ += '<td class="wcra_center">' + user_earning_value[count] + '</td>';
			analysis_table_for_orders_ += '</tr>';
		}

		var wcrac_month_earning = wcrafront.total_earning + ' = ' + monthearn_of_thecoupon;

		$element.find('.cbcouponrefer_total_earning_permonth').html(wcrac_month_earning);

		analysis_table_for_orders_ += '<tr><td colspan="4" class="wcra_right">' + wcrac_month_earning + '</td></tr>';
		analysis_table_for_orders_ += '</tbody></table>';
		$element.find('.cbcouponreferpermonth_compare').html(analysis_table_for_orders_);

		//$(root).find('.cbcouponreferpermonth_months').attr('data-coupon', cbx_coupon_id);
		$element.find('.cbcouponreferpermonth_months').attr('data-coupon', cbx_coupon_id);

		var year = (new Date().getFullYear());
		var month = (new Date().getMonth());
		//$('.cbcouponreferperyear_title_date').html(wcrafront.shortmonthname[month] + ', ' + year);
		$element.find('.cbcouponreferperyear_title_date').html(wcrafront.shortmonthname[month] + ', ' + year);

		$element.find(".cbcouponrefer_ajax_icon").hide();
		$element.find(".cbcouponrefer_ajax_icon").attr('data-busy', '0');

		//cbcouponreferperyear_export
		$element.find('.cbcouponreferpermonth_months').attr('data-coupon', cbx_coupon_id);
		$element.find('.cbcouponreferpermonth_export').attr('data-coupon', cbx_coupon_id);

		var cborderbycoupon_url = $element.find(".cbcouponreferpermonth_export[data-type='pdf']").attr('href');
		if (typeof cborderbycoupon_url !== 'undefined') {
			$(".cbcouponreferpermonth_export[data-type='pdf']").attr('href', cborderbycoupon_url + '&coupon=' + cbx_coupon_id);
		}

		var cborderbycoupon_url_ = $element.find(".cbcouponreferpermonth_export[data-type='csv']").attr('href');
		if (typeof cborderbycoupon_url_ !== 'undefined') {
			$element.find(".cbcouponreferpermonth_export[data-type='csv']").attr('href', cborderbycoupon_url_ + '&coupon=' + cbx_coupon_id);
		}
	}

	//per yaer data graph
	if (data.type == 'peryear') {
		for (key in cbx_total_refer_amount) {
			coupon_data_filterd.push(cbx_total_refer_amount[key]);
		}

		var coupon_data_filterd = $.map(coupon_data_filterd, function (value, index) {
			return [[wcrafront.shortmonthname[index], value]];
		});

		//var root = $(_this).parents('.cbx_user_coupons_front_wrapper').find('.cborderbycouponforwoocommerce_wrapper');

		//$(root).show();
		$element.find('.cborderbycouponforwoocommerce_wrapper').show();

		new Chartkick.ColumnChart(
			"cbcouponreferperyear",
			[
				{"name": wcrafront.wcraoagraph_caption, "data": coupon_data_filterd},
			],
			{"discrete": true, "colors": ["#4285F4", "#db4437"]}
		);


		months = wcrafront.monthname;
		analysis_table_for_orders_ = '<h2 class="wcra_heading" id="cbcouponreferperyear_overview"> ' + wcrafront.wcraoo_header + ' <span class="cbcouponreferperyear_title_yr"></span></h2>';

		analysis_table_for_orders_ += '<table class="table widefat tablesorter display">';
		analysis_table_for_orders_ += '<thead><tr><th style="text-align:center;">' + wcrafront.wcraootable_month + '</th><th style="text-align:center;">' + wcrafront.wcraootable_amt + '</th><th style="text-align:center;">' + wcrafront.wcraootable_number + '</th><th style="text-align:center;">' + wcrafront.wcraootable_earn + '</th></tr></thead><tbody>';

		var yearearn_of_thecoupon = 0.00;
		for (key in cbx_total_refer_amount) {
			analysis_table_for_orders_ += '<tr>';
			analysis_table_for_orders_ += '<td class="wcra_center">' + months[key - 1] + '</td>';
			analysis_table_for_orders_ += '<td class="wcra_center">' + cbx_total_refer_amount[key] + '</td>';
			analysis_table_for_orders_ += '<td class="wcra_center">' + cbx_count[key] + '</td>';


			yearearn_of_thecoupon += parseFloat(user_earning_value[key]);


			analysis_table_for_orders_ += '<td class="wcra_center">' + user_earning_value[key] + '</td>';
			analysis_table_for_orders_ += '</tr>';

		}

		var wcrac_year_earning = wcrafront.total_earning + ' = ' + yearearn_of_thecoupon;

		$element.find('.cbcouponrefer_total_earning_peryear').html(wcrac_year_earning);

		analysis_table_for_orders_ += '<tr><td colspan="4" class="wcra_right">' + wcrac_year_earning + '</td></tr>';
		analysis_table_for_orders_ += '</tbody></table>';


		$element.find('.cbcouponreferperyear_compare').html(analysis_table_for_orders_);

		var year = (new Date().getFullYear());
		$element.find('.cbcouponreferperyear_title_yr').html(year);

		$element.find(".cbcouponrefer_ajax_icon").hide();
		$element.find(".cbcouponrefer_ajax_icon").attr('data-busy', '0');

		//cbcouponreferperyear_export
		$element.find('.cbcouponreferperyear_years').attr('data-coupon', cbx_coupon_id);
		$element.find('.cbcouponreferperyear_export').attr('data-coupon', cbx_coupon_id);

		var cborderbycoupon_url = $element.find(".cbcouponreferperyear_export[data-type='pdf']").attr('href');
		if (typeof cborderbycoupon_url !== 'undefined') {
			$(".cbcouponreferperyear_export[data-type='pdf']").attr('href', cborderbycoupon_url + '&coupon=' + cbx_coupon_id);
		}
		var cborderbycoupon_url_ = $element.find(".cbcouponreferperyear_export[data-type='csv']").attr('href');
		if (typeof cborderbycoupon_url_ !== 'undefined') {
			$element.find(".cbcouponreferperyear_export[data-type='csv']").attr('href', cborderbycoupon_url_ + '&coupon=' + cbx_coupon_id);
		}


	}

}

jQuery(document).ready(function ($) {

	$(".cbcouponrefer_ajax_icon").hide();

	$('.cbx_user_coupons_front_wrapper').each(function (index, element) {

		var $element = $(element);

		//create tabs
		$element.tabs({});

		var couponoption = $element.find('.cbx_user_coupons_front');
		var couponoption_firstvalue = couponoption.find("option:eq(1)").val();

		//console.log(couponoption);
		//console.log(couponoption.find("option:eq(1)").val());




		$element.find('.cbx_user_coupons_front').change(function (e) {
			var _this = $(this);

			var selected_option 		= _this.find('option:selected').text();
			var cborderbycoupon_id 		= _this.val();
			var cborderbycoupon_year 	= '';
			var cborderbycoupon_month 	= '';
			var cborderbymonth 			= $element.find('.wcratype_month').val();
			var cborderbyyear 			= $element.find('.wcratype_year').val();

			var cbx_busy 				= $element.find(".cbcouponrefer_ajax_icon").attr('data-busy');

			$element.find('.cbcouponrefer_selected_coupon').html(wcrafront.coupon_name + selected_option);

			if (cbx_busy == '0' && cborderbycoupon_id != '') {
				$element.find(".cbcouponrefer_ajax_icon").show();
				$element.find(".cbcouponrefer_ajax_icon").css({	'width' : '16px',	'height': '16px'});

				$element.find(".cbcouponrefer_ajax_icon").attr('data-busy', '1');

				if (cborderbymonth == 'permonth') {
					var action = 'cbrefercoupon_orderbymonths';

					$.ajax({
						type    : "post",
						dataType: "json",
						url     : wcrafront.ajaxurl,
						data    : {
							action                : action,
							cborderbycoupon_coupon: cborderbycoupon_id,
							cborderbycoupon_year  : cborderbycoupon_year,
							cborderbycoupon_month : cborderbycoupon_month,
							cborderbycoupon_ref   : 'shortcode',
							security              : wcrafront.nonce
						},
						success : function (data, textStatus, XMLHttpRequest) {
							//draw_graph(data, _this, $, $element);
							wcra_draw_graph(data, $element, $);
						}// end of success
					});// end of ajax
				}

				if (cborderbyyear == 'peryear') {
					var action = 'cbrefercoupon_orderbyyears';

					$.ajax({
						type    : "post",
						dataType: "json",
						url     : wcrafront.ajaxurl,
						data    : {
							action                : action,
							cborderbycoupon_coupon: cborderbycoupon_id,
							cborderbycoupon_year  : cborderbycoupon_year,
							cborderbycoupon_month : cborderbycoupon_month,
							cborderbycoupon_ref   : 'shortcode',
							security              : wcrafront.nonce
						},
						success : function (data, textStatus, XMLHttpRequest) {
							wcra_draw_graph(data, $element, $);
						}// end of success
					});// end of ajax
				}

			} // end of if not busy

		});

		couponoption.val(couponoption_firstvalue).trigger("change");

		// next prev click on overview page for month
		$element.find('.cbcouponreferpermonth_months').click(function (e) {

			e.preventDefault();
			var _this = $(this);

			var cborderbycoupon_busy 		= _this.attr('data-busy');
			var cborderbycoupon_coupon 		= _this.attr('data-coupon');
			var cborderbycoupon_year 		= _this.attr('data-year');
			var cborderbycoupon_month 		= _this.attr('data-month');
			var cborderbycoupon_type 		= _this.attr('data-type');

			var percent_of_thecoupon = $(this).attr('data-target');

			if (cborderbycoupon_busy == '0') {

				$element.find(".cbcouponreferpermonth_months").attr("data-busy", '1');
				_this.addClass('cbcouponreferperyear_years_active');

				$.ajax({
					type    : "post",
					dataType: "json",
					url     : wcrafront.ajaxurl,
					data    : {
						action                : "cbrefercoupon_orderbymonths",
						percent_of_thecoupon  : percent_of_thecoupon,
						cborderbycoupon_coupon: cborderbycoupon_coupon,
						cborderbycoupon_year  : cborderbycoupon_year,
						cborderbycoupon_month : cborderbycoupon_month,
						cborderbycoupon_type  : cborderbycoupon_type,
						security              : wcrafront.nonce
					},
					success : function (data, textStatus, XMLHttpRequest) {
						if (cborderbycoupon_type != 'mail') {
							//draw graph(by year)
							wcra_draw_graph(data, $element, $);

							$element.find(".cbcouponreferpermonth_months").attr("data-busy", '0');
							_this.removeClass('cbcouponreferperyear_years_active');

							//var cborderbycoupon_url = $(".cbcouponreferpermonth_export[data-type='pdf']").attr('data-url');

							if (cborderbycoupon_coupon != '') {
								$element.find(".cbcouponreferpermonth_export[data-type='pdf']").attr('href', '?wcraexport=pdf&year=' + cborderbycoupon_year + '&month=' + cborderbycoupon_month + '&coupon=' + cborderbycoupon_coupon);
								$element.find(".cbcouponreferpermonth_export[data-type='csv']").attr('href', '?wcraexport=csv&year=' + cborderbycoupon_year + '&month=' + cborderbycoupon_month + '&coupon=' + cborderbycoupon_coupon);
							}
							else {
								$element.find(".cbcouponreferpermonth_export[data-type='pdf']").attr('href', '?wcraexport=pdf&year=' + cborderbycoupon_year + '&month=' + cborderbycoupon_month);
								$element.find(".cbcouponreferpermonth_export[data-type='csv']").attr('href', '?wcraexport=csv&year=' + cborderbycoupon_year + '&month=' + cborderbycoupon_month);
							}

							if (cborderbycoupon_month == 12) {
								var wcra_prev_month = parseInt(cborderbycoupon_month) - 1;
								var wcra_next_month = 1;
								var wcra_prev_year = parseInt(cborderbycoupon_year);
								var wcra_next_year = parseInt(cborderbycoupon_year) + 1;
							} else if (cborderbycoupon_month == 1) {
								var wcra_prev_month = 12;
								var wcra_next_month = parseInt(cborderbycoupon_month) + 1;
								var wcra_prev_year = parseInt(cborderbycoupon_year) - 1;
								var wcra_next_year = parseInt(cborderbycoupon_year);
							} else {
								var wcra_prev_month = parseInt(cborderbycoupon_month) - 1;
								var wcra_next_month = parseInt(cborderbycoupon_month) + 1;
								var wcra_prev_year = parseInt(cborderbycoupon_year);
								var wcra_next_year = parseInt(cborderbycoupon_year);
							}

							var month = (new Date().getMonth());
							var year = (new Date().getFullYear());

							$element.find(".cbcouponreferpermonth_months[data-type='next']").attr("data-year", (wcra_next_year));
							$element.find(".cbcouponreferpermonth_months[data-type='next']").attr("data-month", (wcra_next_month));

							$element.find(".cbcouponreferpermonth_months[data-type='prev']").attr("data-year", (wcra_prev_year));
							$element.find(".cbcouponreferpermonth_months[data-type='prev']").attr("data-month", (wcra_prev_month));

							$element.find(".cbcouponreferpermonth_months[data-type='mail']").attr("data-year", (cborderbycoupon_year));
							$element.find(".cbcouponreferpermonth_months[data-type='mail']").attr("data-month", (cborderbycoupon_month));
							$element.find(".cbcouponreferperyear_title_date").html(wcrafront.shortmonthname[cborderbycoupon_month - 1] + ', ' + parseInt(cborderbycoupon_year));

							if ($element.find(".cbcouponreferpermonth_months[data-type='next']").attr("data-year") < year) {
								$element.find(".cbcouponreferpermonth_months[data-type='next']").removeClass('hidden');
							} else {
								if ($element.find(".cbcouponreferpermonth_months[data-type='next']").attr("data-month") <= (month + 1)) {
									$element.find(".cbcouponreferpermonth_months[data-type='next']").removeClass('hidden');
								} else {
									$element.find(".cbcouponreferpermonth_months[data-type='next']").addClass('hidden');
								}

							}
							//cb_coupon_year
						}// end of if not mail
						else { // if mail
							$element.find(".cbcouponreferpermonth_months").attr("data-busy", '0');
							_this.removeClass('cbcouponreferperyear_years_active');
							alert(wcrafront.mail_send);
						}
					}// end of success
				});// end of ajax
			}// end of if busy
		});

		// next prev click on overview page for year
		$element.find('.cbcouponreferperyear_years').click(function (e) {

			e.preventDefault();
			var _this = $(this);

			var cborderbycoupon_busy 	= _this.attr('data-busy');
			var cborderbycoupon_coupon 	= _this.attr('data-coupon');
			var cborderbycoupon_year 	= _this.attr('data-year');
			var cborderbycoupon_type 	= _this.attr('data-type');

			var percent_of_thecoupon 	= _this.attr('data-target');

			if (cborderbycoupon_busy == '0') {

				$element.find(".cbcouponreferperyear_years").attr("data-busy", '1');
				_this.addClass('cbcouponreferperyear_years_active');

				$.ajax({
					type    : "post",
					dataType: "json",
					url     : wcrafront.ajaxurl,
					data    : {
						action                : "cbrefercoupon_orderbyyears",
						percent_of_thecoupon  : percent_of_thecoupon,
						cborderbycoupon_coupon: cborderbycoupon_coupon,
						cborderbycoupon_year  : cborderbycoupon_year,
						cborderbycoupon_type  : cborderbycoupon_type,
						cborderbycoupon_ref   : 'shortcode',
						security              : wcrafront.nonce
					},
					success : function (data, textStatus, XMLHttpRequest) {
						if (cborderbycoupon_type != 'mail') {
							//draw graph(by year)
							wcra_draw_graph(data, $element, $);

							$element.find(".cbcouponreferperyear_years").attr("data-busy", '0');
							_this.removeClass('cbcouponreferperyear_years_active');

							//var cborderbycoupon_url = $(".cbcouponreferperyear_export[data-type='pdf']").attr('data-url');

							if (cborderbycoupon_coupon != '') {
								$element.find(".cbcouponreferperyear_export[data-type='pdf']").attr('href', '?wcraexport=pdf&year=' + cborderbycoupon_year + '&coupon=' + cborderbycoupon_coupon);
								$element.find(".cbcouponreferperyear_export[data-type='csv']").attr('href', '?wcraexport=csv&year=' + cborderbycoupon_year + '&coupon=' + cborderbycoupon_coupon);
							} else {
								$element.find(".cbcouponreferperyear_export[data-type='pdf']").attr('href', '?wcraexport=pdf&year=' + cborderbycoupon_year);
								$element.find(".cbcouponreferperyear_export[data-type='csv']").attr('href', '?wcraexport=csv&year=' + cborderbycoupon_year);
							}

							$element.find(".cbcouponreferperyear_years[data-type='next']").attr("data-year", (parseInt(cborderbycoupon_year) + 1));
							$element.find(".cbcouponreferperyear_years[data-type='prev']").attr("data-year", (parseInt(cborderbycoupon_year) - 1));
							$element.find(".cbcouponreferperyear_years[data-type='mail']").attr("data-year", (parseInt(cborderbycoupon_year)));
							$element.find(".cbcouponreferperyear_title_yr").html(parseInt(cborderbycoupon_year));

							var year = (new Date().getFullYear());

							if ($element.find(".cbcouponreferperyear_years[data-type='next']").attr("data-year") <= year) {
								$element.find(".cbcouponreferperyear_years[data-type='next']").removeClass('hidden');
							} else {
								$element.find(".cbcouponreferperyear_years[data-type='next']").addClass('hidden');
							}
							//cb_coupon_year
						}// end of if not mail
						else { // if mail
							$element.find(".cbcouponreferperyear_years").attr("data-busy", '0');
							_this.removeClass('cbcouponreferperyear_years_active');
							alert(wcrafront.mail_send);
						}
					}// end of success
				});// end of ajax
			}// end of if busy
		});






		//print pdf csv print a div when button click
		$element.find('.cbcouponreferperyear_print').click(function (e) {
			e.preventDefault();

			var cborderbycoupon_type = $(this).attr('data-type');

			if (cborderbycoupon_type == 'print') {
				$element.find(".cbcouponreferperyear_compare").printArea({mode: "popup"});
			}
		});

		//print pdf csv print a div when button click
		$element.find('.cbcouponreferpermonth_print').click(function (e) {
			e.preventDefault();
			var cborderbycoupon_type = $(this).attr('data-type');

			if (cborderbycoupon_type == 'print') {
				$element.find(".cbcouponreferpermonth_compare").printArea({mode: "popup"});
			}

		});



		//updating user contactinfo
		$element.find(".wcra_user_contact_info").click(function (e) {

			e.preventDefault();

			$element.find('.cbcouponrefer_ajax_icon').show();

			var contact_phone = $element.find(".wcra_user_contact_phone").val();

			$.ajax({
				type    : 'POST',
				dataType: "json",
				url     : wcrafront.ajaxurl,
				data    : {
					action                 : "wcra_user_contactinfo",
					wcra_user_contact_phone: contact_phone,
					security               : wcrafront.nonce
				},
				success : function (data, textStatus, XMLHttpRequest) {
					//$('#wcra_payments_settings').append('<p>Updated</p>');
					$element.find('.cbcouponrefer_ajax_icon').hide();
				}// end of success
			});
		});
	});

});



