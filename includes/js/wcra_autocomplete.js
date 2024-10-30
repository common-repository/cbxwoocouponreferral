jQuery(document).ready(function ($) {

	var _data = '';

	function indexOfPartial(array, value) {
		for (var i = 0; i < array.length; i++) {
			if (array[i].indexOf(value) >= 0) {
				return i;
			}
		}
		return -1;
	}

	$('.coupon-custom-field-remove').click(function (e) {
		e.preventDefault();
		var id = $(this).attr('data-id');
		$('#cbx_coupon_refer_user_desc').html('');
		$('#cbx_coupon_refer_user_id').val('');

		$(this).hide();

	});

	//main autocomplete for coupon
	$("#cbx_coupon_refer_search").autocomplete(
		{
			search   : function () {
			},
			source   : function (request, response) {
				$.ajax(
					{
						type    : "post",
						url     : wcraac.ajaxurl,
						dataType: "json",
						data    : {
							action  : "cbcouponrefer_autocomplete",
							term    : request.term,
							security: wcraac.nonce
						},
						success : function (data) {
							_data = data;
							response(data.names);

						}
					});
			},
			minLength: 2,
			select   : function (event, ui) {
				var selected_name = ui.item.value;

				var index = (indexOfPartial(_data.names, selected_name));

				$('.coupon-custom-field-remove').show();

				$('#cbx_coupon_refer_user_id').val(_data.ids[index]);

				$('#cbx_coupon_refer_user_desc').html('<a target="_blank" href = "' + wcraac.userediturl + _data.ids[index] + '">' + _data.display[index] + '</a>');

			},
			close    : function (event, ui) {
				// Close event fires when selection options closes
				$(this).data().term = null;
			}
		});//end of auto complete

});//end of dom ready