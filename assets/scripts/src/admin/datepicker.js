/* global jQuery, document */
jQuery(document).ready(function($) {
	$(function() {
		$(".iworks-row .datepicker").each(function() {
			var format = $(this).data('date-format') || 'yy-mm-dd';
			$(this).datepicker({
				dateFormat: format
			});
		});
	});
});
