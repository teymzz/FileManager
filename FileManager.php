<?php

/**
 * This is a simple class that helps to read the content of a directory or create a new directory
 */

class FileManager{

    private $url;
    private $response;


    /**
     * set a url for reading content
     * 
     * @return void
    */
    public function setUrl($url){
      if(file_exists($url)){
        $this->url = $url;
      }
    }

    /**
     * Get the folders in the url supplied
     *
     * @return array
    */
    public function getFolders(){
      if($this->url == null){ return $this->response('invalid url supplied'); }

      $url   = $this->url;

      $all   = glob($url."*");
      $dirs  = array_filter($all, 'is_dir'); //only directories

      return $dirs;
    }

    /**
     * Get the files in the url supplied
     *
     * @return array
    */
    public function getFiles($items = null){

      if($this->url == null){ return $this->response('invalid url supplied'); }
      
      $url   = $this->url;
      
      $all   = glob($url."*");
      $files = array_filter($all, 'is_file');

      return $files;
    }

    public function addDir($path){
      if(pathinfo($path,PATHINFO_EXTENSION) != ''){
        return $this->response('invalid name supplied!');
      }

      if(is_dir($dir)){ return false; }
      
      mkdir($path,0777,true);
      return is_dir($path)? true :false;
    }

    /**
     * Get the files in the url supplied
     *
     * @return error response
    */
    public function response($message = null){
      if (func_num_args() < 1) { 
        return $this->response; 
      }

      $this->response = $message;
      return false;
    }

}


/*
  $FileManager = new FileManager;

  $FileManager->setUrl('directory_path');
  $FileManager->getFolders(); // return folders in directory
  $FileManager->getFiles();   // return files in directory

   $FileManager->addDir('dir_path');   // creates a new non existing directory 
*/