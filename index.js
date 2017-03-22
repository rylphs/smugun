var OAuth = require('oauth');

var oauth = new OAuth.OAuth(
      'http://api.smugmug.com/services/oauth/1.0a/getRequestToken',
      'http://api.smugmug.com/services/oauth/1.0a/getAccessToken',
      'N98SRGkT8sgWBnstKwCPX7nj2Rwhd6K6',
      'bpvQdjcxgtrQQGGzjv6hCn8qJR6vCRV56rHH37Dm4dvkX9cqzLZqGhfPw2bH9f7B',
      '1.0A',
      null,
      'HMAC-SHA1'
    );

 oauth.getOAuthRequestToken({"oauth_callback":"oob"},function(error, oauth_token, oauth_token_secret, results){
     if(error) console.log('error :' + JSON.stringify(error))
     else { 
       console.log('oauth_token: ' + oauth_token)
       console.log('oauth_token_secret: ' + oauth_token_secret)
       console.log('requestoken results: ' , results)
       console.log("Requesting access token")
     }
    });

function accessTokenCallback(error, access_token, access_token_secret, results) {
        if (error) {
            console.log('error: ' + JSON.stringify(error));
        } else {
            console.log('oauth_access_token: ' + access_token);
            console.log('oauth_access_token_secret: ' + access_token_secret);
            console.log('accesstoken results: ' + sys.inspect(results));

            console.log('getting a list of all your calendars');
            var url = 'http://www.google.com/calendar/feeds/default/allcalendars/full?v=2&alt=jsonc';
            var request = oa.get(url, access_token, access_token_secret, function(error, data) {
                if (error) {
                    console.log(error);
                } else {
                    var calendars = JSON.parse(data).data.items;
                    for (var i = 0; i < calendars.length; ++i) {
                        console.log(calendars[i].title);
                    }
                }
            });
        }
    }