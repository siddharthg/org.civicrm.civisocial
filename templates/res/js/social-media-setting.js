CRM.$(function($) {
	// Extract the event ID if the event URL is given
	cj('#facebook_page_url').change(function() {
		var pageUrl = cj(this).val();
    var matches = pageUrl.match(/(((https|http):\/\/)?(www.)?facebook.com\/[\w\-]*(\/)?).*/m);
    if (matches) {
      cj(this).val(matches[1]);
    } else {
    	cj(this).val('');
    }
	});
});
