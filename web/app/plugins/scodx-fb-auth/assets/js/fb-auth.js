window.fbAsyncInit = function() {
  FB.init({
    appId      : '2218130684950042',
    cookie     : true,
    xfbml      : true,
    version    : 'v3.3'
  });
  FB.AppEvents.logPageView();
};

(function(d, s, id){
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) {return;}
  js = d.createElement(s); js.id = id;
  js.src = "https://connect.facebook.net/en_US/sdk.js";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

jQuery(window).ready(function () {
  // removing fields from login form. I know, it must be a
  // better way, but couldn't find it.
  // Please note that this is also happening in the CSS code
  jQuery('form#loginform label, p.forgetmenot, p.submit').remove();
});

function checkLoginState() {
  FB.getLoginStatus(function(response) {
    if (response.status === 'connected') {
      // obtaining fb user name and email to send it to wp
      FB.api('/me', {fields: 'name,email'}, function(fbData) {
        var data = {
          action: 'fb_auth_init',
          user_name: fbData.name,
          user_email: fbData.email,
          user_id: fbData.id,
        };
        jQuery.post(fb_auth.ajaxurl, data, function(fbAuthResponse) {
          if (fbAuthResponse.user_id) {
            window.location = fbAuthResponse.admin_url;
          }
        });
        return false;
      });

    }
  });
}

