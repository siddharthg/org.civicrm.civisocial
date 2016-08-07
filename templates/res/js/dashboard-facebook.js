CRM.$(function($) {
  var feedNext = {}, feedPrev = {}, notifNext = {}, notifPrev = {};

  $('#feed-next').click(function() {
    getFeed({'next' : feedNext});
  });

  $('#feed-prev').click(function() {
    getFeed({'next' : feedPrev});
  });

  function getFeed(postData) {
    postData = postData || {};
  	CRM.api3('CivisocialUser', 'getfacebookpagefeed', postData).done(function(result) {
      console.log(result);
      if (result.is_error) {
        // Show error
      }
      else {
        $('#feed').empty();

        var posts = result.values.data;
        for (var i = 0; i < posts.length; i++) {
          var post = posts[i];
          var message = (post.message) ? post.message : "";
          var link = (post.link) ? '<a class="media" href="' + post.link + '">' + post.link + '</a>' : "";
          var postHtml = '' +
            '<div class="activity">' +
              '<div class="avatar">' +
                '<img src="' + post.from.picture + '">' +
              '</div>' +
              '<div class="message">' +
                '<span class="posted-by"><a target="_blank" href="' + post.from.link + '">' + post.from.name + '</a></span>' +
                message + link +
                '<span class="activity-status">' + post.time + '</span>' +
                '<ul class="actions">' +
                  '<li><a target="_blank" href="' + post.link + '">See Post</a></li>' +
                '</ul>' +
              '</div>' +
            '</div>';
          
          $('#feed').append(postHtml);
        }

        feedNext = result.values.next;
        feedPrev = result.values.prev;
      }
    });
  }

  CRM.api3('CivisocialUser', 'getfacebookpagenotifications').done(function(result) {
    if (result.is_error) {
      // Show error
    }
    else {
      var notifications = result.values.notifications;
      for (var i = 0; i < notifications.length; i++) {
        var notification = notifications[i];
        var notificationHtml = '' +
          '<a target="_blank" href="' + notification.link + '">' +
            '<div class="activity">' +
              '<div class="avatar">' +
                '<img src="' + notification.from.picture + '">' +
              '</div>' +
              '<div class="message">' +
                notification.message +
                '<span class="activity-status">' + notification.time + '</span>' +
              '</div>' +
            '</div>' +
          '</a>';
        
        $('#notifications').append(notificationHtml);
      }

      $('#notification-label').html('Notifications (' + result.values.unseen_count + ')');
    }
  });

  // Get feed
  getFeed();

});
