<?php

class AlreadExistsException extends Exception{
    public $isAlbum;
    public $uri;

    public function __construct($message, $isAlbum, $uri){
        parent::__construct($message);
        $this->isAlbum = $isAlbum;
        $this->uri = $uri;
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
                "UrlName" => ucfirst($nodeName)
            ], $options);

            $this->client->post($uri, $options);
        }catch(GuzzleHttp\Exception\ClientException $e){
            $code = $e->getResponse()->getStatusCode();
            if($code == 409){
                $conflicting = $this->getConflicting($e);
                $nodeInfo = $this->client->get($conflicting->Uri);
                $isAlbum = $nodeInfo->Node->Type == "Album";
                throw new AlreadExistsException("Node $nodeName already exists", $isAlbum, $conflicting->Uri);
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
                "UrlName" => ucfirst($nodeName)."TMP"
            ];
            $this->client->post("node/$parentId!children", $options);

            $folderId = $this->getNodeId($this->toUriPath($path['path']."/".ucfirst($nodeName)."TMP"));
            $this->client->post("node/$folderId!movenodes", [
                "Async" => false,
                "AutoRename" => false,
                "MoveUris" => [
                   "$uri"
                ]
            ]);
            $this->client->patch("node/$folderId", [
                "UrlName" => ucfirst($nodeName)
            ]);

        }catch(GuzzleHttp\Exception\ClientException $e){
            $code = $e->getResponse()->getStatusCode();
            if($code == 409){
                $conflicting = $this->getConflicting($e);
                $nodeInfo = $this->client->get($conflicting->Uri);
                $isAlbum = $nodeInfo->Node->Type == "Album";
                throw new AlreadExistsException("Node $nodeName already exists", $isAlbum, $conflicting->Uri);
            }
            else throw $e;
        }
    }

    public function createFolder($path){
        $this->createNode($path, self::FOLDER_TYPE, []);
    }

    public function createAlbum($path){
        $tags = $this->generateTags($path);
        $this->createNode($path, self::ALBUM_TYPE, ["Keywords" => $tags]);
    }

    public function createAlbumInsideFolder($path){
        $albumName = $this->separeNodeFromPath($path)['node'];
        $path = $path."/".$albumName;
        $tags = $this->generateTags($path);
        $this->createNode($path, self::ALBUM_TYPE, ["Keywords" => $tags]);
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
            return ucfirst($value);
        }, $path);
        return implode("/", $path);
    }

    

/*    private $client = null;
    private $uploadFolderInfo = null;
    private $fileInfo = [];

    private function splitPath($path){
        $path = trim($path, "/");
        $exploded = explode("/", $path);
        return [
            'path' => implode("/", array_slice($exploded, 0, count($exploded)-1)),
            'object' => $exploded[count($exploded) - 1]
        ];
    }

    private function getNodeId($path){
        $folderInfo = $this->get("user/rapha/folder/Uploads/" . $path);
        return $folderInfo->Folder->NodeID;
    }

    private function setToken(){
        $authtoken = array(
            "oauth_token" => self::TOKEN,
            "oauth_token_secret" => self::TOKEN_SEC
        );
        $this->client->setToken($authtoken['oauth_token'], $authtoken['oauth_token_secret']);
    }

    private function getFolderUploads($force){
        if($this->uploadFolderInfo == null || $force){
            $this->uploadFolderInfo = $this->client->get("folder/user/rapha/Uploads!albums");
        }
        return $this->uploadFolderInfo;
    }

    private function getAlbumInfo($name){
        $uploadsInfo = $this->getFolderUploads(false);
        if(!isset($uploadsInfo->Album) || count($uploadsInfo->Album) == 0){
            return false;
        } 
            
        $albums = $uploadsInfo->Album;
        foreach($albums as $album){
            if($album->Name == ucfirst($name)) return $album;
        }
        return null;
    }

    private function getImageInfo($album) {
        if(!array_key_exists($album, $this->fileInfo) || 
            $this->fileInfo[$album] == null){
            $albumKey = $this->getAlbumInfo($album)->AlbumKey;
            $this->fileInfo[$album] = $this->client->get("album/$albumKey!images");
        }
        return $this->fileInfo[$album];
    }

    public function connect(){
        $options = [ 'AppName' => self::APP_NAME, 
            '_verbosity' => self::VERBOSITY, 
            'OAuthSecret' => self::OAUTH_SEC
        ];
        $this->client = new phpSmug\Client(self::API_KEY, $options);
        $this->setToken();
    }

    public function getMd5Sums($path, $imageName){
        $path = $this->splitPath($path);
        
        $imageInfo = $this->getImageInfo($album);
        if(!array_key_exists("AlbumImage", $imageInfo)) return null;
        foreach($imageInfo->AlbumImage as $info){
            if($info->FileName == $imageName) return $info->ArchivedMD5;
        }
        return null;
    }

    public function albumExists($path){
        try{
            $path = $this->splitPath($path);
            $albumInfo = $this->client->get("folder/user/rapha/Uploads/".$path->path."!albums");
            if(!isset($albumInfo->Album) || count($albumInfo->Album)){
                return false;
            }
            foreach($albumInfo->Album as $album){
                if($album->Nome == $path->object) return true;
            }
            return false;
        }catch(Exception $e){
            if($e->getResponse()->getStatusCode() == 404){
                return false;
            }
        }
    }

    public function createNode($path, $type, $tags){
        $path = $this->splitPath($path);
        $nodeId = $this->getNodeId($path->path);
        $urlCreate = 'node/$nodeId!children';
        $albumOptions = [
            "Name" => ucfirst($name),
            "Type" => $type,
            "Keywords" => $tags,
            "Privacy" => 3,
            "UrlName" => ucfirst($name),
            "Watermark" => false,
        ];
        try{
            $this->client->post($urlCreate, $albumOptions);
            $uploads = $this->getFolderUploads(true);
        }catch(Exception $e){
            print($e->getResponse()->getBody(true));
        }
    }

    public function upload($file, $album, $tags){
        $albumKey = $this->getAlbumInfo($album)->AlbumKey;
        $this->client->upload("album/$albumKey", $file, [
            'FileName' => $file,
            'Keywords' => $tags
        ]);
    }*/
}