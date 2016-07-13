CRM.$(function($) {
  var post_char_limit = 140;

	cj('#post-content').keydown(function() {
		updateCharsLeft();
	});

  cj('#post-to-facebook').change(function() {
    console.log('fb');
    if (cj('#post-to-facebook').is(':checked') && !cj('#post-to-twitter').is(':checked')) {
      post_char_limit = 500;
      updateCharsLeft();
    }
  });

  cj('#post-to-twitter').change(function() {
    if (cj('#post-to-twitter').is(':checked')) {
      post_char_limit = 140;
    } else {
      post_char_limit = 500;
    }
    updateCharsLeft();
  });

  function updateCharsLeft() {
    var post_char_left = post_char_limit - cj('#post-content').val().length;
    cj('#chars-left').html(post_char_left);
  }

  cj('#make-post').submit(function(e) {
    e.preventDefault();

    // Pefrom AJAX request to API
  });
});
