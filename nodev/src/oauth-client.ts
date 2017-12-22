const OAuth = require('oauth-1.0a');
const request = require('request');
const path = require('path');
const crypto = require('crypto');

export class OAuthClient {
    oauth:any;
    header:any;
    oauthData:any;

    constructor(
        private baseURL:string, 
        private consumer: {key:string, secret:string},
        private token: {key:string, secret:string}){    
            this.oauth = OAuth({
                consumer,
                signature_method: 'HMAC-SHA1',
                hash_function(base_string, key) {
                  return crypto.createHmac('sha1', key).update(base_string).digest('base64');
                }
              });
    }

    get(urlPath:string){
        return this.request(urlPath, "GET");
    }

    post(urlPath:string, data?:any, requestHeader:boolean = false){
        return this.request(urlPath, "POST", data, requestHeader);
    }

    private request(urlPath:string, method:"GET"|"POST", data?:any, requestHeader?:boolean){
        requestHeader = requestHeader || (method == "GET");
        var url = `${this.baseURL}/${urlPath}`;
        const requestData = {
            url:url, method: method, data:data
        };
        return new Promise((resolve, reject) => {
            const oauthData = this.oauth.authorize(requestData, this.token);
            request({
                method: requestData.method,
                form: requestHeader ? undefined : oauthData,
                url: requestData.url,
                json:true,
                headers: requestHeader ? this.oauth.toHeader(oauthData) : undefined
              }, function(error, response, body) {
                  if(error) reject(error);
                  else resolve(body);
              });    
        });
    }
}

const o = new OAuthClient('https://api.smugmug.com/api/v2', 
    {key: 'N98SRGkT8sgWBnstKwCPX7nj2Rwhd6K6',
    secret: 'bpvQdjcxgtrQQGGzjv6hCn8qJR6vCRV56rHH37Dm4dvkX9cqzLZqGhfPw2bH9f7B'}, 
    { key: 'gnXh7ZH5X77tSVkqgKXXfpkj9xpmXm6T',
    secret: 'BWPJTxNQDt9DdKJ9FWzGx4Bk3NZc9GjMQBVBMz247j8d88xpJG2QkPQ5VDk835JT'});
o.get('folder/user/rapha/Uploads!albumlist').then(console.log);


/*o.post('folder/user/rapha/Uploads!folders', {
    "Type": 2,
    "Name": "testeFolder23",
    "UrlName" : "TesteFolder23"
}).then(console.log);*/