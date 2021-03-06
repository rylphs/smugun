#!/usr/bin/php

<?php
//require_once "Uploader.php";
require_once "SmugmugClient.php";

function getCaller(){
        $trace = debug_backtrace();
        $caller = $trace[1];
        $m = "";
        if(isset($caller['class'])) $m = $caller['class'] . "::";
        $m = $m . $caller['function'];
        throw new Exception("$m not implemented yet!");
 }

class TestBase{
    private $asExpected;

    protected function exec(){
        $args = func_get_args();
        $method = $args[1];
        $obj = $args[0];
        $cls = get_class($obj);
        $margs = [];
        if(count($args) > 2){
            $margs = array_splice($args, 2);
        }
        $class = new ReflectionClass($cls);
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $margs);
    }

    protected function get($obj, $name){
        $cls = get_class($obj);
        $cls = get_class($obj);
        $class = new ReflectionClass($cls);
        $prop = $class->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($obj);
    }

    protected function expect($expected, $result){
        if(!isset($this->asExpected)) $this->asExpected = true;
        $equals = $expected == $result;
        if(!$equals) $this->asExpected = false;
        echo "\n\t\t";
        if(is_bool($result)) $result = ($result) ? 'TRUE' : 'FALSE';
        if($equals) echo "[$result AS EXPTECED]";
        else echo "[FAILED - expected: $expected but was $result]";
    }

    public function run(){
        $class = new ReflectionClass(get_class($this));
        $methods = $class->getMethods(ReflectionMethod::IS_PRIVATE);
        echo "\n\n";
        echo "Starting tests for " . get_class($this) . " ...\n";
        foreach($methods as $method){
            try{
                $this->asExpected = null;
                echo "\tRunning test " . $method->name . "...";
                $method->setAccessible(true);
                $result = $method->invokeArgs($this, []);
                if(is_string($result)){
                    echo "\t[result: $result]\n";
                    continue;
                }
                if(is_bool($result)){
                    if($result) echo "\t[OK]\n";
                    else echo "\t[FAILED]\n";
                    continue;
                }
                if(isset($this->asExpected)){
                    if($this->asExpected) echo "\n\t[OK]\n";
                    else echo "\n\t[FAILED]\n";
                    continue;
                }

                echo "\t[OK]\n";
            }catch(Exception $e){
                echo "[FAILED, Exception: '" . $e->getMessage() . "']\n";
            } 
        }
        echo "Done!\n\n";
    }
}


class TestSmugClient extends TestBase{
    
    private function mustSepareteNode(){
        $smug = new SmugmugClient();
        $split = $this->exec($smug, "separeNodeFromPath", "foo/bar/node");
        $this->expect("foo/bar", $split['path']);
        $this->expect("node", $split['node']);
    }

    private function mustConnect(){
        try{
            $c = new SmugmugClient();
            $c->connect();
            return true;
        } catch(Exception $e){
            return false;
        }
    }

    private function mustConvertToUri(){
        $uploader = new SmugmugClient();
        $converted = $this->exec($uploader, "toUriPath", "teste/folder");
        $this->expect("Teste/Folder", $converted);
    }

    private function mustNotCreateSameNodeTwice(){
        try{
            $smug = new SmugmugClient();
            $this->exec($smug, "connect");
            $nodeId = $this->exec($smug, "createNode", "testNode", 2, []);
            $nodeId = $this->exec($smug, "createNode", "testNode", 2, []);
            return false;
        }catch(AlreadExistsException $e){
            return true;
        }
    }

    private function mustGenerateTags(){
        $smug = new SmugmugClient("fotosTeste");
        $tags = $this->exec($smug, "generateTags", "fotosTeste/Brasilia/Ultimo");
        $this->expect("Brasilia,Ultimo", $tags);
    }

    private function musGetNodeId(){
      /*  $smug = new SmugClient();
        $this->exec($smug, "connect");
        $nodeId = $this->exec($smug, "getNodeId", "aaaaa");
        $this->expect("Rc9kbM", $nodeId);

        $nodeId = $this->exec($smug, "getNodeId", "aaaaa/abc");
        $this->expect("NjKfHM", $nodeId);*/
    }

    

    private function mustCreateFolder(){
      /*  $uploader = new Uploader("fotosTeste");
        $this->exec($uploader, "connect");
        $exists = $this->exec($uploader, "folderExists", "testeNovo");
        $this->expect(false, $exists);

        $this->exec($uploader, "createFolder", "testeNovo");
        $this->exec($uploader, "connect");
        $exists = $this->exec($uploader, "folderExists", "testeNovo");
        $this->expect(true, $exists);*/
    }


}

class TestUploader extends TestBase{
    private function mustConnect(){
        $uploader = new Uploader("fotosTeste");
        $this->exec($uploader, "connect");
    }

    private function mustCountFiles(){
        $uploader = new Uploader("fotosTeste");
        $success = true;

        $this->exec($uploader, "countFiles", "fotosTeste/RaphaeEJess");
        $n = $this->get($uploader, "numberOfTotalFiles");
        $this->expect(3, $n);

         $this->exec($uploader, "countFiles", "fotosTeste/RaphaeEJess/bus");
        $n = $this->get($uploader, "numberOfTotalFiles");
        $this->expect(4, $n);

        $this->exec($uploader, "countFiles", "fotosTeste/Brasilia");
        $n = $this->get($uploader, "numberOfTotalFiles");
        $this->expect(7, $n);

        $this->exec($uploader, "countFiles", "fotosTeste/Pedro");
        $n = $this->get($uploader, "numberOfTotalFiles");
        $this->expect(11, $n);

        $this->exec($uploader, "countFiles", "fotosTeste");
        $n = $this->get($uploader, "numberOfTotalFiles");
        $this->expect(21, $n);
    }

    private function mustGetSubfolders(){
        $uploader = new Uploader("fotosTeste");
        $subFodlers = $this->exec($uploader, "getSubfolders", "fotosTeste");
        $this->expect(3, count($subFodlers));
    }

    private function mustFindMedia(){
        $uploader = new Uploader("fotosTeste");
        $this->exec($uploader, "connect");
        $files = $this->exec($uploader, "searchForMedia", "fotosTeste/Brasilia");
        $this->expect(3, count($files));
    }

    private function mustGenerateTags(){
        $uploader = new Uploader("fotosTeste");
        $this->exec($uploader, "connect");
        $tags = $this->exec($uploader, "generateTags", "fotosTeste/Brasilia/Ultimo");
        $this->expect("Brasilia,Ultimo", $tags);
    }

    private function mustProcess(){
        return;
        $files = glob('fotosTeste/*.log', GLOB_BRACE);
        foreach($files as $file){
            unlink($file);
        }
        $uploader = new Uploader("fotosTeste");
        $uploader->startProcessing();
    }

    private function testeQueNuncaFalha(){return true;}
}


$teste = new TestSmugClient();
$teste->run();
 
