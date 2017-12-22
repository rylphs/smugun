<?php
require_once 'vendor/autoload.php';

class AlreadExistsException extends Exception{
    public $isAlbum;
    public $nodeInfo;

    public function __construct($message, $isAlbum, $nodeInfo){
        parent::__construct($message);
        $this->isAlbum = $isAlbum;
        $this->nodeInfo = $nodeInfo;
    }
}

class SmugmugClient{
    const TOKEN = "gnXh7ZH5X77tSVkqgKXXfpkj9xpmXm6T";
    CONST TOKEN_SEC = "BWPJTxNQDt9DdKJ9FWzGx4Bk3NZc9GjMQBVBMz247j8d88xpJG2QkPQ5VDk835JT";
    const APP_NAME = 'myApp';
    const VERBOSITY = 1;
    const OAUTH_SEC = 'bpvQdjcxgtrQQGGzjv6hCn8qJR6vCRV56rHH37Dm4dvkX9cqzLZqGhfPw2bH9f7B';
    const ACCESS = 'Full';
    const PERMISSION = 'Modify';
    const API_KEY = 'N98SRGkT8sgWBnstKwCPX7nj2Rwhd6K6';
    const FOLDER_URI = "folder/user/rapha/Uploads";
    const FOLDER_TYPE = 2;
    const ALBUM_TYPE = 4;

    private $client = null;
    private $md5Cache = [];

    public function connect(){
        $options = [ 'AppName' => self::APP_NAME, 
            '_verbosity' => self::VERBOSITY, 
            'OAuthSecret' => self::OAUTH_SEC
        ];
        $this->client = new phpSmug\Client(self::API_KEY, $options);
        $this->setToken();
    }

    public function createNode($path, $type, $options){
        try{
            
            $path = $this->separeNodeFromPath($path);
            $nodeName = $path['node'];
            
            $nodeId = $this->getNodeId($this->toUriPath($path['path']));
            $uri = "node/$nodeId!children";
            
            $options = array_merge([
                "Type" => $type,
                "Name" => $nodeName,
                "UrlName" => $this->formatUrl($nodeName)
            ], $options);

            return $this->client->post($uri, $options);
        }catch(GuzzleHttp\Exception\ClientException $e){
            $code = $e->getResponse()->getStatusCode();
            if($code == 409){
                $conflicting = $this->getConflicting($e);
                $nodeInfo = $this->client->get($conflicting->Uri);
                $isAlbum = $nodeInfo->Node->Type == "Album";
                throw new AlreadExistsException("Node $nodeName already exists", $isAlbum, $conflicting);
            }
            else throw $e;
        }
    }

    public function createFolderAndMoveAlbum($path, $uri){
        try{
            $path = $this->separeNodeFromPath($path);

            $nodeName = $path['node'];
            $parentId = $this->getNodeId($this->toUriPath($path['path']));
            
            $options = [
                "Type" => self::FOLDER_TYPE,
                "Name" => $nodeName,
                "UrlName" =>  $this->formatUrl($nodeName)."TMP"
            ];
            $folderInfo = $this->client->post("node/$parentId!children", $options);

            $folderId = $this->getNodeId($this->toUriPath($path['path']."/".$options["UrlName"]));
            $this->client->post("node/$folderId!movenodes", [
                "Async" => false,
                "AutoRename" => false,
                "MoveUris" => [
                   "$uri"
                ]
            ]);
            $this->client->patch("node/$folderId", [
                "UrlName" => $this->formatUrl($nodeName)
            ]);
            return $folderInfo;

        }catch(GuzzleHttp\Exception\ClientException $e){
            $code = $e->getResponse()->getStatusCode();
            if($code == 409){
                $conflicting = $this->getConflicting($e);
                $nodeInfo = $this->client->get($conflicting->Uri);
                $isAlbum = $nodeInfo->Node->Type == "Album";
                throw new AlreadExistsException("Node $nodeName already exists", $isAlbum, $conflicting);
            }
            else throw $e;
        }
    }

    public function createFolder($path){
        return $this->createNode($path, self::FOLDER_TYPE, []);
    }

    public function createAlbum($path){
        $tags = $this->generateTags($path);
        return $this->createNode($path, self::ALBUM_TYPE, ["Keywords" => $tags]);
    }

    public function createAlbumInsideFolder($path){
        $albumName = $this->separeNodeFromPath($path)['node'];
        $path = $path."/".$albumName;
        $tags = $this->generateTags($path);
        return $this->createNode($path, self::ALBUM_TYPE, ["Keywords" => $tags]);
    }

    public function getMd5Sums($albumUri){
        if(!array_key_exists($albumUri, $this->md5Cache)){
            $photosUri = "$albumUri!images?start=1&count=5000";
            $photosInfo = $this->client->get($photosUri);
            if(!isset($photosInfo->AlbumImage)){
                $this->md5Cache[$albumUri] = [];
                return [];
            };
            $photosInfo = $photosInfo->AlbumImage;
            $md5 = [];
            foreach($photosInfo as $info){
                $md5[$info->FileName] = $info->ArchivedMD5;
            }
            $this->md5Cache[$albumUri] = $md5;
        }
        
        return $this->md5Cache[$albumUri];
    }

     public function upload($file, $albumUri){
        $tags = $this->generateTags(dirname($file));
        $this->client->upload($albumUri, $file, [
            'FileName' => $file,
            'Keywords' => $tags
        ]);
    }

    private function formatUrl($nodeName){
        $url = preg_replace('/_/', ' ', $nodeName);
        $url = preg_replace('/\s/', '', ucwords($url));
        return $url;
    }

    private function getConflicting($e){
        $body = $e->getResponse()->getBody()->getContents();
        $body = json_decode($body); 
        $uri = $body->Response->Uri;
        return $body->Conflicts->$uri;
    }

    private function generateTags($path){
        $tags = explode('/', $path);
        $filter = function($value) {
            if($value == '.' || $value == "") return false;
            return true;
        };
        $tags = array_filter($tags, $filter);
        $tags = array_slice($tags, 1);
        return implode(",", $tags);
   }

    private function separeNodeFromPath($path){
        $path = trim($path, "/");
        $exploded = explode("/", $path);
        return [
            'path' => implode("/", array_slice($exploded, 0, count($exploded)-1)),
            'node' => $exploded[count($exploded) - 1]
        ];
    }

    private function getNodeInfo($path){
        $path = $this->toUriPath($path);
        if($path != "") $path = "/$path";
        $nodeInfo = $this->client->get(self::FOLDER_URI . $path);
        return $nodeInfo;
    }

    private function getNodeId($path){
        $nodeInfo = $this->getNodeInfo($path);
        return $nodeInfo->Folder->NodeID;
    }

    private function setToken(){
        $authtoken = array(
            "oauth_token" => self::TOKEN,
            "oauth_token_secret" => self::TOKEN_SEC
        );
        $this->client->setToken($authtoken['oauth_token'], $authtoken['oauth_token_secret']);
    }

    private function toUriPath($path){
        $path = trim($path, "/");
        $path = explode("/", $path);
        $path = array_map(function($value){
            return$this->formatUrl($value);
        }, $path);
        return implode("/", $path);
    }
}