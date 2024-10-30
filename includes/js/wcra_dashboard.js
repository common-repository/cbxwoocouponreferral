function draw_graph(value, $) {

	var coupon_data_all = [];
	var coupon_data_filterd = [];
	var cbx_populate_orders_permonth_total = (value.all);
	var cbx_total_refer_amount = (value.filtered);
	var cbx_coupon_id = (value.coupon_id);
	var cbx_count = (value.count);
	var cbx_all_count = (value.all_count);

	for (key in cbx_total_refer_amount) {
		coupon_data_all.push(cbx_populate_orders_permonth_total[key]);
		coupon_data_filterd.push(cbx_total_refer_amount[key]);
	}

	var coupon_data_all = $.map(coupon_data_all, function (value, index) {
		return [[wcra_dashboard.shortmonthname[index], value]];
	});

	var coupon_data_filterd = $.map(coupon_data_filterd, function (value, index) {
		return [[wcra_dashboard.shortmonthname[index], value]];
	});

	new Chartkick.ColumnChart(
		"cbcouponreferperyear",
		[
			{"name": wcra_dashboard.wcraoagraph_upper_caption, "data": coupon_data_all},
			{"name": wcra_dashboard.wcraoagraph_caption, "data": coupon_data_filterd},
		],
		{"discrete": true, "colors": ["#4285F4", "#db4437"]}
	);


}


jQuery(document).ready(function ($) {


	if ($('#cbcouponreferperyear').length > 0) {
		//draw_graph($.parseJSON(wcra_dashboard.default_value, $));
		$.ajax({
			type    : "post",
			dataType: "json",
			url     : wcra_dashboard.ajaxurl,
			data    : {
				action                : "cbrefercoupon_orderbyyears",
				percent_of_thecoupon  : '0',
				cborderbycoupon_coupon: '',
				cborderbycoupon_year  : wcra_dashboard.default_year,
				cborderbycoupon_type  : '',
				security              : wcra_dashboard.nonce
			},
			success : function (data, textStatus, XMLHttpRequest) {
				//draw graph(by year)
				draw_graph(data, $);

			}// end of success
		});// end of ajax
	}


});//end of dom ready