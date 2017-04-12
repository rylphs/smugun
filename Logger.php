<?php

class Logger{
    const LOG = "main.log";
    const UPLOAD_ERRORS = "upload-errors.log";
    const PROCESSED = "processed.log";
    const SKIP = "skip.log";
    const TIME_FORMAT = "Y-m-d:H:i:s";

    private $level = 0;
    private $logLocation;
    private $startTime;

    public function __construct(){
        if(func_num_args() > 0){
            $this->logLocation = rtrim(func_get_arg(0), '/') . "/";
        }
        else $this->logLocation = "./";
        $this->startTime = $this->getTime("Y-m-d_H-i-s_");
    }

    private function getLogname($baseName){
        return $this->logLocation . $this->startTime . $baseName;
    }

    private function getTime($format){
        $date = new DateTime(null, new DateTimeZone('America/Sao_Paulo'));
        return $date->format($format);
    }

    private function writeLog($txt){
        $txt = str_repeat("   ", $this->level).$txt . "\n";
        file_put_contents($this->getLogname(self::LOG), $this->getTime(self::TIME_FORMAT) . " $txt", FILE_APPEND);
    }

    private function decreaseLevel(){
        $this->level = max(0, ($this->level - 1));
    }

    public function info($txt){
        $this->writeLog("INFO: $txt");
        $this->level++;
    }

    public function error($txt){
         $this->decreaseLevel();
        $this->writeLog("ERROR: $txt");
    }

    public function infoOk(){
        $this->decreaseLevel();
        if(func_num_args() == 0) return;
        $txt = func_get_arg(0);
        $this->writeLog("INFO: $txt");
    }

    public function infoProcessed($file){
        $this->info("Processing file $file...");
        file_put_contents($this->getLogName(self::PROCESSED), "$file\n", FILE_APPEND);
    }

    public function infoSkip($file){
        $this->infoOk("File $file has not changed and will be skiped.");
        file_put_contents($this->getLogName(self::SKIP), "$file\n", FILE_APPEND);
    }

    public function errorUpload($file){
        $this->error("Error during file upload($file), file will be skiped.");
        file_put_contents($this->getLogName(self::UPLOAD_ERRORS), "$file\n", FILE_APPEND);
    }
}