function wcra_draw_graph(value, $) {


	var coupon_data_all = [];
	var coupon_data_filterd = [];
	var cbx_populate_orders_permonth_total = (value.all);
	var cbx_total_refer_amount = (value.filtered);
	var cbx_coupon_id = (value.coupon_id);
	var cbx_count = (value.count);
	var cbx_all_count = (value.all_count);

	var user_earning_value = value.user_earning;

	for (key in cbx_total_refer_amount) {
		coupon_data_all.push(cbx_populate_orders_permonth_total[key]);
		coupon_data_filterd.push(cbx_total_refer_amount[key]);
	}

	var coupon_data_all = $.map(coupon_data_all, function (value, index) {
		return [[wcra.shortmonthname[index], value]];
	});

	var coupon_data_filterd = $.map(coupon_data_filterd, function (value, index) {
		return [[wcra.shortmonthname[index], value]];
	});

	new Chartkick.ColumnChart(
		"cbcouponreferperyear",
		[
			{"name": wcra.wcraoagraph_upper_caption, "data": coupon_data_all},
			{"name": wcra.wcraoagraph_caption, "data": coupon_data_filterd},
		],
		{"discrete": true, "colors": ["#4285F4", "#db4437"]}
	);

	var months = wcra.monthname;
	var year = (new Date().getFullYear());

	var analysis_table_for_orders_ = '<h2 class="wcra_heading" id="cbcouponreferperyear_overview">' + wcra.wcraoo_header + '<span class="cbcouponreferperyear_title_yr"> ' + year + '</span></h2><table class="table widefat tablesorter display">';
	analysis_table_for_orders_ += '<thead><tr><th style="text-align:center;">' + wcra.wcraootable_month + '</th><th style="text-align:center;">' + wcra.wcraootable_all_amt + '</th><th style="text-align:center;">' + wcra.wcraootable_number + '</th><th style="text-align:center;">' + wcra.wcraootable_ref_amt + '</th><th style="text-align:center;">' + wcra.wcraootable_ref_number + '</th><th style="text-align:center;">' + wcra.wcraootable_percentage + '</th></tr></thead><tbody>';

	var order_amount_total 			= 0;
	var order_count_total 			= 0;
	var refer_order_amount_total 	= 0;
	var refer_order_count_total 	= 0;
	var user_earning_value_total 	= 0;

	for (key in cbx_total_refer_amount) {

		/*var cbxcouponorderpercentage = 0.00;
		if (cbx_populate_orders_permonth_total[key] != '' || cbx_populate_orders_permonth_total[key] != 0 || cbx_populate_orders_permonth_total[key] != 0.0) {
			cbxcouponorderpercentage = (100 * (cbx_total_refer_amount[key] / cbx_populate_orders_permonth_total[key])).toFixed(2);
		}
		else {
			cbxcouponorderpercentage = '0.00';
		}*/


		analysis_table_for_orders_ += '<tr>';
		analysis_table_for_orders_ += '<td align="center">' + months[key - 1] + '</td>';
		analysis_table_for_orders_ += '<td align="center">' + cbx_populate_orders_permonth_total[key] + '</td>';
		analysis_table_for_orders_ += '<td align="center">' + cbx_all_count[key] + '</td>';
		analysis_table_for_orders_ += '<td align="center">' + cbx_total_refer_amount[key] + '</td>';
		analysis_table_for_orders_ += '<td align="center">' + cbx_count[key] + '</td>';
		analysis_table_for_orders_ += '<td align="center">' + user_earning_value[key] + '</td>';

		analysis_table_for_orders_ += '</tr>';

		order_amount_total += parseFloat(cbx_populate_orders_permonth_total[key]); //total order amount
		order_count_total += parseInt(cbx_all_count[key]); //total order count
		refer_order_amount_total += parseFloat(cbx_total_refer_amount[key]); //total refer order amount
		refer_order_count_total += parseInt(cbx_count[key]); //total refer order counts
		user_earning_value_total += parseFloat(user_earning_value[key]); //total user earning

	}
	analysis_table_for_orders_ += '<tr><td align="right"></td>'
		+'<td align="center"><strong>'+order_amount_total+'</strong></td>'
		+'<td align="center"><strong>'+order_count_total+'</strong></td>'
		+'<td align="center"><strong>'+refer_order_amount_total+'</strong></td>'
		+'<td align="center"><strong>'+refer_order_count_total+'</strong></td>'
		+'<td align="center"><strong>'+user_earning_value_total+'</strong></td></tr>';

	analysis_table_for_orders_ += '</tbody></table>';

	$('#cbcouponreferperyear_compare').html(analysis_table_for_orders_);

}

jQuery(document).ready(function ($) {



	if ($('#cbcouponreferperyear').length > 0) {


		//draw_graph($.parseJSON(wcra.default_value));
		//$('.cbcouponreferperyear_years').trigger( "click" );
		$.ajax({
			type    : "post",
			dataType: "json",
			url     : wcra.ajaxurl,
			data    : {
				action                : "cbrefercoupon_orderbyyears",
				percent_of_thecoupon  : '0',
				cborderbycoupon_coupon: '',
				cborderbycoupon_year  : wcra.default_year,
				cborderbycoupon_type  : '',
				security              : wcra.nonce
			},
			success : function (data, textStatus, XMLHttpRequest) {
				//draw graph(by year)
				//console.log(data);
				wcra_draw_graph(data, $);

			}// end of success
		});// end of ajax

	}

	// next prev year click on overview page, used in backend
	$('.cbcouponreferperyear_years').click(function (e) {

		e.preventDefault();

		//console.log('clicked');

		var _this = $(this);

		var cborderbycoupon_busy 		= _this.attr('data-busy');
		var cborderbycoupon_coupon 		= _this.attr('data-coupon');
		var cborderbycoupon_year 		= _this.attr('data-year');
		var cborderbycoupon_type 		= _this.attr('data-type');

		var percent_of_thecoupon 		= _this.attr('data-target');

		if (cborderbycoupon_busy == '0') {

			$(".cbcouponreferperyear_years").attr("data-busy", '1');
			//$(_this).addClass('cbcouponreferperyear_years_active');

			$.ajax({
				type    : "post",
				dataType: "json",
				url     : wcra.ajaxurl,
				data    : {
					action                : "cbrefercoupon_orderbyyears",
					percent_of_thecoupon  : percent_of_thecoupon,
					cborderbycoupon_coupon: cborderbycoupon_coupon,
					cborderbycoupon_year  : cborderbycoupon_year,
					cborderbycoupon_type  : cborderbycoupon_type,
					security              : wcra.nonce
				},
				success : function (data, textStatus, XMLHttpRequest) {
					if (cborderbycoupon_type != 'mail') {
						//draw graph(by year)
						wcra_draw_graph(data, $);

						$(".cbcouponreferperyear_years").attr("data-busy", '0');
						//$(_this).removeClass('cbcouponreferperyear_years_active');

						var cborderbycoupon_url = $(".cbcouponreferperyear_export[data-type='pdf']").attr('data-url');

						if (cborderbycoupon_coupon != '') {
							$(".cbcouponreferperyear_export[data-type='pdf']").attr('href', cborderbycoupon_url + '&wcraexport=pdf&year=' + cborderbycoupon_year + '&coupon=' + cborderbycoupon_coupon);
							$(".cbcouponreferperyear_export[data-type='csv']").attr('href', cborderbycoupon_url + '&wcraexport=csv&year=' + cborderbycoupon_year + '&coupon=' + cborderbycoupon_coupon);
						}
						else {
							$(".cbcouponreferperyear_export[data-type='pdf']").attr('href', cborderbycoupon_url + '&wcraexport=pdf&year=' + cborderbycoupon_year);
							$(".cbcouponreferperyear_export[data-type='csv']").attr('href', cborderbycoupon_url + '&wcraexport=csv&year=' + cborderbycoupon_year);
						}

						$(".cbcouponreferperyear_years[data-type='next']").attr("data-year", (parseInt(cborderbycoupon_year) + 1));
						$(".cbcouponreferperyear_years[data-type='prev']").attr("data-year", (parseInt(cborderbycoupon_year) - 1));
						$(".cbcouponreferperyear_years[data-type='mail']").attr("data-year", (parseInt(cborderbycoupon_year)));
						$(".cbcouponreferperyear_title_yr").html(' ' + parseInt(cborderbycoupon_year));

						var year = (new Date().getFullYear());

						if ($(".cbcouponreferperyear_years[data-type='next']").attr("data-year") <= year) {
							$(".cbcouponreferperyear_years[data-type='next']").removeClass('hidden');
						} else {
							$(".cbcouponreferperyear_years[data-type='next']").addClass('hidden');
						}

						//cb_coupon_year
					}// end of if not mail
					else { // if mail
						$(".cbcouponreferperyear_years").attr("data-busy", '0');
						//$(_this).removeClass('cbcouponreferperyear_years_active');
						alert(wcra.mail_send);
					}
				}// end of success
			});// end of ajax
		}// end of if busy
	});

	$(".cbcouponrefer_ajax_icon").hide();

	//print pdf csv print a div when button click
	$('.cbcouponreferperyear_print').click(function (e) {
		e.preventDefault();
		var cborderbycoupon_type = $(this).attr('data-type');

		if (cborderbycoupon_type == 'print') {
			$("div#cbcouponreferperyear_compare").printArea({mode: "popup"});
		}

	})

});//end of dom ready