CRM.$(function($) {
  // Extract the event ID if the event URL is given
	cj('#facebook_event_id').change(function() {
		var eventUrl = cj(this).val();
    var eventId = eventUrl.match(/facebook\.com\/events\/([0-9]+)\/?/m);
    if (eventId) {
      cj(this).val(eventId[1]);
    }
	});

});
