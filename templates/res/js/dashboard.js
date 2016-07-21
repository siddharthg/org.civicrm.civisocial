CRM.$(function($) {
  var xhrOn = false;
  var post_char_limit = parseInt(cj('#chars-left').html());

	cj('#post-content').keyup(function() {
		updateCharsLeft();
	});

  cj('#post-content').blur(function() {
    if (cj(this).hasClass('error')) {
      cj(this).removeClass('error');
    }

    if (cj('#make-post > span.error').length) {
     cj('#make-post > span.error').remove(); 
    }

    cj('#post-button').val('Post');
  });

  cj('#post-to-facebook').change(function() {
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

  cj('#make-post').submit(function(e) {
    e.preventDefault();

    // Begin validation
    if (cj('#post-content').val().length === 0) {
      showError('Post cannot be empty.');
      return;
    }

    if (cj('#chars-left').html() < 0) {
      showError('Please limit the post length to ' + post_char_limit + ' characters.');
      return;
    }

    if (!cj('#post-to-facebook').is(':checked') && !cj('#post-to-twitter').is(':checked')) {
      showError('Please check at least one social network.');
      return;
    }

    if (xhrOn) {
      return;
    }

    var postData = {};
    cj('#make-post').find('[name]').each(function() {
      if (cj(this).is(':checkbox')) {
        if (cj(this).is(':checked')) {
          postData[this.name] = cj(this).is(':checked'); 
        }
      }
      else {
        postData[this.name] = this.value;  
      }
    });

    var xhrOn = true;
    cj('#post-button').val('Posting..');

    CRM.api3('CivisocialUser', 'updatestatus', postData).done(function(result) {
      console.log(result);
      if (result.is_error) {
        showError(result.error_message);
      }
      else {
        cj('#post-button').val('Posted!');
        cj('#post-content').val('');
        updateCharsLeft();
      }
      xhrOn = false;
    });
  });

  function updateCharsLeft() {
    var post_char_left = post_char_limit - cj('#post-content').val().length;
    cj('#chars-left').html(post_char_left);
  }

  function showError(message) {
    cj('#post-content').addClass('error');
    if (cj('#make-post > span.error')) {
      cj('#make-post > span.error').remove();
    }
    cj('<span class="error block">' + message + '</span>').insertAfter('#make-post > .post-to');
  }
});
