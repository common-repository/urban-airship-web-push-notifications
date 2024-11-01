(function($) {
	"use strict";

	$(".js-ua-wn-notice").on("click.dismissNotice", ".notice-dismiss", function(event) {
		event.preventDefault();

		var $this = $(this);
		if (!$this.parent().data("notice")) {
			return;
		}

		$.post(ajaxurl, {
			action: "dismiss_admin_notice",
			url: ajaxurl,
			notice: $this.parent().data("notice"),
			nonce: uaWnDismissibleNotice.nonce || ""
		});
	});
})(jQuery);
