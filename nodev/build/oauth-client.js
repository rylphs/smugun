"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var OAuth = require('oauth-1.0a');
var request = require('request');
var path = require('path');
var crypto = require('crypto');
var OAuthClient = /** @class */ (function () {
    function OAuthClient(baseURL, consumer, token) {
        this.baseURL = baseURL;
        this.consumer = consumer;
        this.token = token;
        this.oauth = OAuth({
            consumer: consumer,
            signature_method: 'HMAC-SHA1',
            hash_function: function (base_string, key) {
                return crypto.createHmac('sha1', key).update(base_string).digest('base64');
            }
        });
    }
    OAuthClient.prototype.get = function (urlPath) {
        return this.request(urlPath, "GET");
    };
    OAuthClient.prototype.post = function (urlPath, data, requestHeader) {
        if (requestHeader === void 0) { requestHeader = false; }
        return this.request(urlPath, "POST", data, requestHeader);
        /*var url = `${this.baseURL}/${urlPath}`;
        const requestData = {
            url:url, method:'POST', data:data
        }
        return new Promise((resolve, reject) => {
            const oauthData = this.oauth.authorize(requestData, this.token);
            request({
                method: requestData.method,
                form: requestHeader ? undefined : this.oauth.authorize(requestData, this.token),
                url: requestData.url,
                json:true,
                headers: this.oauth.toHeader(this.oauth.authorize({url:url, method:'GET'}, this.token))
              }, function(error, response, body) {
                  if(error) reject(error);
                  else resolve(body);
              });
        });*/
    };
    OAuthClient.prototype.request = function (urlPath, method, data, requestHeader) {
        var _this = this;
        requestHeader = requestHeader || (method == "GET");
        var url = this.baseURL + "/" + urlPath;
        var requestData = {
            url: url, method: method, data: data
        };
        return new Promise(function (resolve, reject) {
            var oauthData = _this.oauth.authorize(requestData, _this.token);
            request({
                method: requestData.method,
                form: requestHeader ? undefined : oauthData,
                url: requestData.url,
                json: true,
                headers: requestHeader ? _this.oauth.toHeader(oauthData) : undefined
            }, function (error, response, body) {
                if (error)
                    reject(error);
                else
                    resolve(body);
            });
        });
    };
    return OAuthClient;
}());
exports.OAuthClient = OAuthClient;
var o = new OAuthClient('https://api.smugmug.com/api/v2', { key: 'N98SRGkT8sgWBnstKwCPX7nj2Rwhd6K6',
    secret: 'bpvQdjcxgtrQQGGzjv6hCn8qJR6vCRV56rHH37Dm4dvkX9cqzLZqGhfPw2bH9f7B' }, { key: 'gnXh7ZH5X77tSVkqgKXXfpkj9xpmXm6T',
    secret: 'BWPJTxNQDt9DdKJ9FWzGx4Bk3NZc9GjMQBVBMz247j8d88xpJG2QkPQ5VDk835JT' });
o.get('folder/user/rapha/Uploads!albumlist').then(console.log);
/*o.post('folder/user/rapha/Uploads!folders', {
    "Type": 2,
    "Name": "testeFolder23",
    "UrlName" : "TesteFolder23"
}).then(console.log);*/ 
//# sourceMappingURL=oauth-client.js.map