CRM.$(function($) {
	// Extract the event ID if the event URL is given
	cj('#facebook_event_url').change(function() {
		var eventUrl = cj(this).val();
    var matches = eventUrl.match(/(((https|http):\/\/)?(www.)?facebook.com\/events\/[0-9]*(\/)?).*/m);
    if (matches) {
      cj(this).val(matches[1]);
    } else {
    	cj(this).val('');
    }
	});

});
