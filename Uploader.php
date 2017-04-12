<?php
// This file is generated by Composer
require_once 'vendor/autoload.php';
require_once 'SmugmugClient.php';
require_once 'Logger.php';

 function notImplemented(){
        $trace = debug_backtrace();
        $caller = $trace[1];
        $m = "";
        if(isset($caller['class'])) $m = $caller['class'] . "::";
        $m = $m . $caller['function'];
        throw new Exception("$m not implemented yet!");
 }

class Uploader {
    const MEDIA_PATTERN = "{jpg,JPG,png,PNG}";

    private $smugClient;
    private $numberOfFilesProcessed = 0;
    private $numberOfFilesUploaded = 0;
    private $numberOfFilesSkiped = 0;
    private $numberOfFilesWithError = 0;
    private $numberOfTotalFiles = 0;
    private $logger;
    private $dir;

    public function __construct($folder){
        $this->dir = $folder;
        $this->smugClient = new SmugmugClient();
        $this->logger = new Logger($folder);
    }

    private function connect() {
        try{
            $this->logger->info("Connecting to smugmug...");
            $this->smugClient->connect();
            $this->logger->infoOk("Connected");
        }catch(Exception $e){
            $this->logger->error("Conection error " . $e->getMessage());
            throw($e);
        }
   }

   private function countFiles($folder){
       $subs = glob($folder . '/*' , GLOB_ONLYDIR);
       foreach($subs as $subDir){
           $this->countFiles($subDir);
       }
       $files = glob("$folder/*.". self::MEDIA_PATTERN, GLOB_BRACE);
       $this->numberOfTotalFiles += count($files);
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

  /*  private function getFolderName($path){
        return basename($path);
    }

    private function getTags($path){
        $tags = explode('/', $path);
        $filter = function($value) {
            if($value == '.' || $value == "") return false;
            return true;
        };
        $tags = array_filter($tags, $filter);
        $tags = array_slice($tags, 1);
        return implode(",", $tags);
    }

    private function createAlbumIfNotExists($path, $tags){
        $albumName = $this->getFolderName($path);
        $this->logger->info("Searching for $albumName album...");
        if($this->smugClient->albumExists($path)){
            $this->logger->infoOk("Album already exists");
        }
        else{
            $this->logger->info("Album not found, creating it...");
            $this->smugClient->createAlbum($path, $tags);
            $this->logger->infoOk("OK");
        }
    }

    private function getFolders($parent){
        $this->logger->info("Getting subdirectories from $parent...");
        $dirs = glob($parent . '/*' , GLOB_ONLYDIR);
        $this->logger->infoOk();
        return $dirs;
    }
    
    private function getPhotos($dir){
        $this->logger->info("Searching for media in $dir directory ...");
        $this->logger->infoOk();
        return glob("$dir/*.". self::MEDIA_PATTERN, GLOB_BRACE);
    }


    private function sendFiles($path){
        $albumName = $this->getFolderName($path);
        $files = $this->getPhotos($path);
        $tags = $this->getTags($path);
        if(count($files)== 0){
            $this->logger->infoOk("Directory $path has no media files, nothing to do here!");
            return;
        }

        $this->createAlbumIfNotExists($path, $tags);
        foreach($files as $file){
            $this->logger->infoProcessed($file);
            $this->numberOfFilesProcessed++;
            $md5 = $this->smugClient->getMd5Sums($albumName, $this->getFolderName($file));
            if($md5 != null && md5_file($file) == $md5){
                $this->logger->infoSkip($file);
                $this->numberOfFilesSkiped++;
                continue;
            }
            
            $this->logger->info("Uploading $file...");
            try{
                $this->smugClient->upload($file, $albumName, $tags);
                $this->numberOfFilesUploaded++;
                $this->logger->infoOk();
            }catch(Exception $e){
                $this->numberOfFilesWithError++;
                $this->logger->errorUpload($file);
            }
        }
    }

    private function processDir($path) {
        $this->logger->info("Processing folder $path...");
        $subFolders = $this->getFolders($path);

        if(count($subFolders) > 0){
            $this->logger->info("Checking if folder exists");

            if($this->smugClient->folderExists($path)){
                $this->logger->infoOk("Folder already exists");
            }
            else{
                $this->logger->infoOk("Folder not exists, creating it...");
            }
            $this->logger->info("Processing $path children...");
        }

        foreach($subFolders as $child){
            $this->processDir($child);
        }

        $this->sendFiles($path);
        $this->logger->infoOk();
    }*/

    private function searchForMedia($dir){
        $this->logger->info("Searching for media in $dir directory ...");
        $this->logger->infoOk();
        return glob("$dir/*.". self::MEDIA_PATTERN, GLOB_BRACE);
    }

    private function getSubfolders($path){
        $this->logger->info("Getting subdirectories from $path...");
        $folders = glob($path . '/*' , GLOB_ONLYDIR);
        $this->logger->infoOk();
        return $folders;
    }

    private function createAlbum($path){
       try{
            $this->logger->infoOk("Creating album if not exists.");
            $this->smugClient->createAlbum($path);
        }catch(AlreadExistsException $e){}
    }

    private function createFolder($path){
        try{
            $this->logger->infoOk("Creating folder if not exists.");
            $this->smugClient->createFolder($path);
        }catch(AlreadExistsException $e){
            if($e->isAlbum){
                //TODO: parei aqui
                $this->smugClient->move($e->uri);
            }
        }
    }

    private function getMd5Sums($albumUri){}

    private function uploadFile($algumUri, $file){
        $this->logger->info("Uploading $file...");
        try{
            $this->smugClient->upload($file, $albumName, $tags);
            $this->numberOfFilesUploaded++;
            $this->logger->infoOk();
        }catch(Exception $e){
            $this->numberOfFilesWithError++;
            $this->logger->errorUpload($file);
        }
    }

    private function processFile($albumUri, $file){
        $this->logger->infoProcessed($file);
        $this->numberOfFilesProcessed++;
        $md5 = $this->getMd5Sums($albumUri);
        if($md5 != null && md5_file($file) == $md5){
            $this->logger->infoSkip($file);
            $this->numberOfFilesSkiped++;
            return;
        }
        
        $this->uploadFile($algumUri, $file);
    }

    private function processFiles($path, $isFolder){
        $albumName = $this->separeNodeFromPath($path)['node'];
        if($isFolder) $albumUri = "$path/$albumName";
        else $albumUri = $path;
        $files = $this->searchForMedia($path);
        $tags = $this->generateTags($path);
        if(count($files)== 0){
            $this->logger->infoOk("Directory $path has no media files, nothing to do here!");
            return;
        }

        $this->createAlbum($albumUri);

        foreach($files as $file){
            $this->processFile($albumUri, $file);
        }
    }

    private function processFolder($path){
        $this->logger->info("Processing folder $path...");
        $subFolders = $this->getSubfolders($path);
        $isFolder = (count($subFolders) > 0);

        if($isFolder){
            $this->createFolder($path);
            $this->logger->info("Processing $path children...");
            foreach($subFolders as $child){
                $this->processFolder($child);
            }
        }

        $this->processFiles($path, $isFolder);
        $this->logger->infoOk();
    }

    public function startProcessing(){
        $dir = $this->dir;
        $this->countFiles($dir);
        $this->logger->info("Start processing $dir.");
        $this->connect();
        $this->processFolder($dir);
        $this->logger->infoOk();
        $this->logger->infoOk("Total files: " . $this->numberOfTotalFiles);
        $this->logger->infoOk("Total files processed: " . $this->numberOfFilesProcessed);
        $this->logger->infoOk("Total files uploaded: " . $this->numberOfFilesUploaded);
        $this->logger->infoOk("Total files skiped: " . $this->numberOfFilesSkiped);
        $this->logger->infoOk("Total errors when uploading: " . $this->numberOfFilesWithError);
        $this->logger->infoOk("End processing.");
    }
   
}

/*try{
   $dir = rtrim($argv[1], "/");
   echo "Starting script at $dir ...\n";
   $uploader = new Uploader($dir);
   $uploader->startProcessing();
   echo "Success!\n";
}catch(Exception $e){
    echo "An error has ocurred. Check the main.log file.\n";
}*/