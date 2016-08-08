CRM.$(function($) {
  var feedNext = {}, feedPrev = {}, notifNext = {}, notifPrev = {};

  $('#feed-next').click(function() {
    getFeed({'next' : feedNext});
  });

  $('#feed-prev').click(function() {
    getFeed({'prev' : feedPrev});
  });

  $('#notif-next').click(function() {
    getNotifs({'next' : notifNext});
  });

  $('#notif-prev').click(function() {
    getNotifs({'prev' : notifPrev});
  });

  function getFeed(postData) {
    postData = postData || {};
  	CRM.api3('CivisocialUser', 'getfacebookpagefeed', postData).done(function(result) {
      if (result.is_error) {
        // Show error
      }
      else {
        processAjaxResult('feed', result, postData);

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

  function getNotifs(postData) {
    postData = postData || null;

    CRM.api3('CivisocialUser', 'getfacebookpagenotifications').done(function(result) {
      if (result.is_error) {
        // Show error
      }
      else {
        processAjaxResult('notif', result, postData);

        var notifications = result.values.data;
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
          
          $('#notif').append(notificationHtml);
        }

        $('#notif-label').html('Notifications (' + result.values.unseen_count + ')');

        notifNext = result.values.next;
        notifPrev = result.values.prev;
      }
    });
  }

  function processAjaxResult(resultType, result, postData) {
    if (result.values.data.length === 0) {
      var nextBtn = $('#' + resultType + '-next').parent();
      var prevBtn = $('#' + resultType + '-prev').parent();

      if ('next' in postData) {
        $(nextBtn).hide();
      }
      else {
        $(prevBtn).hide();
      }
      return;
    }

    $(nextBtn).is(':visible') ? null : $(nextBtn).show();
    $(prevBtn).is(':visible') ? null : $(prevBtn).show();

    $('#' + resultType).empty();
  }
  // Get feed
  getFeed();
  getNotifs();
});
