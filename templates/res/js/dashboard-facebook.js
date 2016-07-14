CRM.$(function($) {
  cj('#tabs').tabs();

	cj('#post-content').keydown(function() {
		var post_char_left = 500 - cj('#post-content').val().length;
    cj('#chars-left').html(post_char_left);
	});

  cj('#make-post').submit(function(e) {
    e.preventDefault();

    // Pefrom AJAX request to API
  });
});
