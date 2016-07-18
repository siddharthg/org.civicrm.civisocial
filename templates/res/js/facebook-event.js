CRM.$(function($) {
  var waitingForResponse = false;
  cj('#fetch_fb_event_info').click(function() {
    if (waitingForResponse) {
      return;
    }
    cj(this).html('Fetching..');
    waitingForResponse = true;

    var match = cj('#facebook_event_url').val().match(/facebook\.com\/events\/([0-9]+)\/?/m);
    if (match) {
      eventId = match[1];
    }
    console.log(match);

    CRM.api3('CivisocialUser', 'getfacebookeventinfo', {
      "event_id": eventId
    }).done(function(result) {
      console.log(result);
      if (result.is_error) {
        // Display error
      }
      else {
        cj('#title').val(result.name);
        CKEDITOR.instances.description.setData(result.description);
        cj('[id^=start_date_display]').val(result.start_date);
        cj('#start_date_time').val(result.start_time);
        cj('[id^=end_date_display]').val(result.end_date);
        cj('#end_date_time').val(result.end_time);
      }

      cj('#fetch_fb_event_info').html('Fetch Info');
      waitingForResponse = false;
    });
  });
});
