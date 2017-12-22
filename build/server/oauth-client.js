"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var oauth_1_0a_1 = require("oauth-1.0a");
var request_1 = require("request");
var path_1 = require("path");
var OAuthClient = /** @class */ (function () {
    function OAuthClient(baseURL, consumer, token) {
        this.baseURL = baseURL;
        this.consumer = consumer;
        this.token = token;
        this.oauth = oauth_1_0a_1.default({
            consumer: consumer,
            signature_method: 'HMAC-SHA1',
            hash_function: function (base_string, key) {
                return crypto.createHmac('sha1', key).update(base_string).digest('base64');
            }
        });
    }
    OAuthClient.prototype.get = function (urlPath) {
        var url = path_1.default.join(this.baseURL, path_1.default);
        request_1.default({
            method: "GET",
            url: url,
            json: true,
            headers: this.oauth.toHeader(this.oauth.authorize({ url: url, method: 'GET' }, this.token))
        }, function (error, response, body) {
            console.log(response.statusCode, body);
            // Process your data here
        });
    };
    return OAuthClient;
}());
exports.OAuthClient = OAuthClient;
var o = new OAuthClient('https://api.smugmug.com/api/v2/', { key: 'N98SRGkT8sgWBnstKwCPX7nj2Rwhd6K6',
    secret: 'bpvQdjcxgtrQQGGzjv6hCn8qJR6vCRV56rHH37Dm4dvkX9cqzLZqGhfPw2bH9f7B' }, { key: 'gnXh7ZH5X77tSVkqgKXXfpkj9xpmXm6T',
    secret: 'BWPJTxNQDt9DdKJ9FWzGx4Bk3NZc9GjMQBVBMz247j8d88xpJG2QkPQ5VDk835JT' });
o.get('folder/user/rapha/Uploads!albumlist');
//# sourceMappingURL=oauth-client.js.map