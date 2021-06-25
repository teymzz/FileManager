<?php


class Resource{

    private static $exts  = array("css","js","php","html");
    private static $extTags  = array("css","js");
    private static $extFils  = array("php","html");    
    private static $cpath = '';
    private static $error = [];
    private static $resources = [];
    private static $resourceName;  
    private static $storeImports; 
    private static $currentImports = [];
    private static $resource_path;
    private static $prepend;

    private static function make_request($url,$store=false){

   	  	if(!self::processUrl($url,$ext,$store)){ return false; }
   	 
   	    if($script = self::call_script($url,$ext)){
   	      
   	      if(self::is_script($script)){ 
   	        return $script;
   	      }else{ 
   	        return $url;
   	      }

   	    }else{
   	      self::call_error("Resource :$url: was not found"); 
   	      return false;
   	    }

    }

    private static function getExt(&$url,&$colons = null){
        
        $ext = '';
        $split = explode(":::",$url);
        $url = $split[0];

        if(isset($split[1])){
           $ext = $split[1];
           $colons = ":::".$ext;
        }else{
           $ext = pathinfo($split[0],PATHINFO_EXTENSION);
        }
        return $ext;
    }

    private static function processUrl(&$url, &$ext = null,$store = false){
        
          $path = self::$resource_path; //get resource path

          //split url
          $ext = self::getExt($url,$colons);

    	  //store url and path forms (save memory)
    	  $isProtoUrl  = self::is_protocol($url);
    	  $isProtoPath = self::is_protocol($path);
    	  $isDotUrl    = self::is_dot($url);
    	  $isDotPath   = self::is_dot($path);    	
      
        //append resource path if neccessary
        if($path != null){
      	   $reurl = ltrim($url,"/");
      	   $repath = ($path == '/')? "/" : rtrim($path,"/");

           //append resource path before url is processed
      	   if($isProtoPath && !$isProtoUrl && !$isDotUrl){
      		  $url = $repath.DS.$reurl;
      	   }elseif($isDotPath && !$isProtoUrl){
         	  $url = $repath.DS.$reurl;
           }
        }

        //url: apply document root where necessary
        $xurl = $url;
       	if(!$isDotUrl && !$isProtoUrl){
           $xurl = docRoot.DS.$url;
       	}

       	//validate url supplied
        if($isProtoUrl){ 
		  if(!filter_var($url,FILTER_VALIDATE_URL)){ 
		  	self::call_error("Resource path :$url: is not valid"); 
		  	return false;
		  } 
        }else{
        	if(!is_file($xurl)){ 
        		self::call_error("Resource path :$url: is not found"); 
        		return false; 
        	}        
        	$nurl = self::domify($url,$ext);
        }
               	   
        //resource: store url if neccessary
        if(self::$resourceName != null){
          if($store == true){self::$resources[self::$resourceName][] = $url.$colons;}
        }else{
          if($store == true){self::$resources[] = $url.$colons;}
        }

        if(isset($nurl)){ $url = $nurl; }

 
        //validate extension
        $files = self::$exts;
        if(!in_array($ext,$files)){ 
        	self::call_error("Resource file with $ext extension is not allowed"); 
        	return false; 
        }
        
        $urlExt = self::getExt($url);
        if(!self::is_protocol($url) and self::is_ext($urlExt,self::$extFils)){ $url = str_replace("\\",'/',docRoot).DS.$url; }
        if(!self::is_protocol($url) and !self::is_dot($url) and self::is_ext($urlExt,self::$extTags)){ $url = srcRoot.$url; }

        return true; //return true if all checks is successfully done

    }

    private static function is_protocol($url){
    	 return ((substr($url, 0,4) == 'http') || (substr($url, 0,3) == 'www'))? true : false;
    }

    private static function is_dot($url){
    	 return (substr($url, 0,2) == '..')? true : false;
    }    

    private static function is_script($script){
      return ($script != strip_tags($script))? true : false;
    }


    private static function domify($url,$ext){

      if(online === true and in_array($ext, self::$extTags)){ 
        //get domain folder
         $url = ltrim($url,'/');
        //replace urls starting with domain folder
        $expUrl = explode('/',$url,2);
        if(basename($_SERVER['DOCUMENT_ROOT']) == $expUrl[0]){
          return isset($expUrl[1])? $expUrl[1] : $url ;
        }
      }

      return $url;

    }

    private static function call_script($url,$ext){

      if($ext == "css"){
        return "<link rel='stylesheet' type='text/css' href='".$url."' media='all'>";
      }

      if($ext == "js"){
        return "<script type='text/javascript' src='".$url."'></script>";
      }

      if($ext == "php" || $ext = 'html'){
        return $url;
      }    
      self::call_error("Resource path :$url: is not found");
      return false;
    }

    private static function is_ext($ext,$exts){
      $exts = (array) $exts;
      if(in_array($ext, $exts)){
        return true;
      }
      return false;
    }

    private static function refresh($param = false){
      if($param === true){
        self::$resource_path = null;
        self::$error = [];  
        self::$resources = [];
        self::$resourceName = null; 
      }else{
        self::$resource_path = null;
      }  
    }

    private static function execute($script,$type=false){
       
        if($type === true){
        	if(self::is_script($script)){
        	 	print $script;
        	}elseif($script != null){
        	 	include_once($script);
        	}else{
        	 	return false;
        	}
        }else{

        	if(self::is_script($script)){
        	 	return $script;
        	}elseif($script != null){
        	 	return "include_once(".$script.")";
        	}else{
        	 	return false;
        	}

        }

    }

    private static function call_error($error){
      	self::$error[] = $error;
    }

    public static function Path($path = false){

      if(is_dot($path)){ 
      	if(is_dir($path)){ 
      	  self::call_error("Resource path :$path: is not found");
      	  return false;
        } 
      }

      self::$resource_path = $path;
    }


    public static function getFile($url,$store=false,$execute = true){

      $store = (bool) $store;
      $script = self::make_request($url,$store);

      if(self::$storeImports === true){ self::$currentImports[] = $script; }
    
      if(!$execute){ return self::execute($script,$execute); }
      self::execute($script,$execute);

    }


    public static function callFile($url,$store=true){
    	$store = (bool) $store;
    	$script = self::make_request($url,$store);
     	return $script;
    }

    public static function addFile($name,$url=null){

        //few tweak settings
        if(count(func_get_args()) == 1 and trim($name) != null){
          $url  = $name;
          $name = "";
        }

        //execute method with tweaks
        if($url != null){
         if(isset(self::$resources[$name])){
           self::$resourceName = $name;
         }
         if(trim($name) != "" and !isset(self::$resources[$name])){
           return false;
         }
         self::callFile($url);
         self::$resourceName = '';
        }
    }

    public static function name($name=null){
      	self::$resourceName = $name; 
      	if(!isset(self::$resources[$name])){
      		self::$resources[$name] = [];
      	}	
    }

    public static function getUrl($url){

      $script = self::make_request($url);

      if(!self::is_script($script)){
		    return "include_once(".ltrim($script).")";
      }else{
		    return htmlentities($script);
      }

    }

    public static function export($dpath=null,$usename=null){
    	$values = ':values';
    	if(count(func_get_args()) < 2){
    	  $usename  = $dpath;
    	  $dpath = null;
    	}
    	return self::import($dpath,$usename,$values);
    }
    

    private static function prefixer($dpath=null,$usename=null,$execute=true){

    	if($usename == "" || $usename == null){ $usename = ":"; }
    	if($usename[0] != ":"){ return false; }
    	
    	$strlen  = strlen($usename);
    	$usename = ltrim($usename, ":");
    	$scripts = [];

    	if($usename != null && isset(self::$resources[$usename])){
    	  $selfResources = self::$resources[$usename];
    	}else{
    	  $selfResources = self::$resources;
    	}

    	foreach ($selfResources as $key => $resource){
    	  if(!is_array($resource)){
    	  	
    	  	$url = trim($resource);
    	    
    	    if(!self::processUrl($url,$ext)){ return false; /*import prefixing failed!!!*/ }

    	    if(self::is_dot($dpath) and is_dir($dpath) and !self::is_protocol($url)){
    	    	$dpath = rtrim($dpath,"/");
    	    	$url = ltrim($url,"/");
    	    	$url = self::is_ext($ext,self::$extTags)? $dpath.DS.$url : $url;
    	    }elseif(self::is_protocol($dpath) and !self::is_protocol($url)){
    	    	$url = str_replace("../", '' , $url);
    	    	$dpath = rtrim($dpath,"/");
    	    	$url = ltrim($url,"/");
    	    	$url =  self::is_ext($ext,self::$extTags)? $dpath.DS.$url : $url;
    	    }
            $script    = self::call_script($url,$ext); 
            $scripts[] = self::execute($script,$execute);
    	  }
    	}
    	if(!$execute){ return $scripts; }
    }


    private static function importer($dpath=null,$usename=null,$execute = true){
    	
    	if($usename == "" || $usename == null){ $usename = ":"; }
    	if($usename[0] != ":"){ return false; }
    	
    	$strlen = strlen($usename);
    	$usename = ltrim($usename, ":");
    	$script = [];

    	if($usename != null && isset(self::$resources[$usename])){
    	  $selfResources = self::$resources[$usename];
    	}else{
    	  $selfResources = self::$resources;
    	}
    	
    	foreach ($selfResources as $key => $resource) {
        
    	  if(!is_array($resource)){ 
    	    if(self::is_protocol($resource)){
    	      $resource = trim($resource);
    	    }else{
    	      if(strlen($dpath) > 0 and !is_dir($dpath)){ continue; }
    	      $resource = trim($dpath.$resource);
    	    }     

    	    if(!$execute){ 
    	    	$script[] = self::getFile($resource,"",false); 
    	    }else{
            self::getFile($resource,"",true); 
    	    }
    	    
    	  }
    	}



    	if(!empty($script)){ return $script; }
    } 

    public static function parent($path = false){
    	self::$prepend = $path;
    }

    public static function import($dpath=null,$usename=null,&$imports=false){
      	self::$resource_path  = null;
      	self::$storeImports   = null;
      	self::$currentImports = null;    

      	$exec = ($imports == ":values")? false : true; 
     	$scripts = [];

      	if($imports !== false and $imports != ":values"){ self::$storeImports = true; }

      	if(count(func_get_args()) < 2){
      	  $usename  = $dpath;
      	  $dpath = null;
      	}else{
      		if(is_array($dpath)){
      			return ("import error: first arg must be a null or string (upper directory or domain url) if arg > 1");
      		}
      	}

        if(self::$prepend != null and substr($dpath, 0,5) != "pre::"){ 
           $dpath = $prep = "pre::".self::$prepend; 
        }

      	if($dpath != null and !is_array($dpath)){       
      	    $url = str_replace("pre::", '', $dpath);
      		$url = trim($url);
      		if(!self::is_protocol($url) and !is_dir($url)){ 
      			self::call_error("path of '$dpath' does is not found");
      			return false;
      		}
      	}

      	if(substr($dpath, 0,5) == "pre::"){
      	  $dExp = explode("pre::", $dpath);
      	  $dpath = $dExp[1];
      	}

      	if(is_array($usename)){
      		foreach ($usename as $grpName) {
      		 $gpName = str_replace(":", '', $grpName);
      		 if($gpName == null || isset(self::$resources[$gpName])){
      		 	$scripts[$grpName] = isset($dExp)? self::prefixer($dpath,$grpName,$exec) : self::importer($dpath,$grpName,$exec) ;
      		 }
      		}
      	}else{

          $usName = str_replace(":", '', $usename);
      		if(isset(self::$resources[$usName]) || $usName == null){
      			$scripts[$usename] = isset($dExp)? self::prefixer($dpath,$grpName,$exec) : self::importer($dpath,$usename,$exec);
      		}

      	}

      	$imports = self::$currentImports;

      	self::$storeImports = null;
      	self::$currentImports = null; 

      	if(!$exec){ return $scripts; } 
    }

    public static function used(){
      return json_encode(self::$resources);
    }

    public static function error($point=null){
       if($point == 'first'){
        echo isset(self::$error[0])? self::$error[0] : "no error at $point" ;
       }elseif($point == "last"){
        $count = count(self::$error);
        $npos  = $count - 1;
        echo isset(self::$error[$npos])? self::$error[$npos] : "no error at $point" ;
       }elseif($point == 'all'){
          for($i=0; $i<count(self::$error); $i++){
            echo self::$error[$i]."<br>";
          }
       }else{
        echo isset(self::$error[$point])? self::$error[$point] : "error index $point not found" ;
       }     
    }

    public static function close($param = false){        

      if(self::$resourceName != "" and $param == false){      
      	Resource::$resource_path = null;
        self::$resourceName = false;
        return new self;
      }else{
        if($param === "/"){
          self::$resourceName = false;
          return new self;        
        }else{
          return self::refresh(true);
        }
      }  
    }

     public static function open($param = null){
        Resource::$resource_path = null;
        if(Resource::active()) { 
           Resource::close(false); 
        }elseif($param === true){
           Resource::close(true); 
        }

     }

     public static function active(){
       if(self::$resourceName != ""){
         return true;
       }else{
        return false;
       }
     }

}

/**
 *
 * RESOURCE METHODS
 *
 * UM (Url Methods)    => callFile(), addFile(), getFile()
 * CM (Control Methods) => Path(), name(), parent(), 
 * DM (Deploy Methods)  => import(), export()
 * OM (Other Methods)   => open(), close(), used(), active(), error(),
 *
 */
/**
 * METHODS AND EXPLANATION
 *
 * A) Resource::open(true | false)  //safely prepares the Resource class {optional}
 *  	@param (default): false
 *  	@param true   : referesh all urls / clears storage
 *  	@param false  : (1) safely exit selected group, (2) open resource in safe mode
 * 
 * B) Resource::Path($url | null)  // intialiazes or unsets a new directory for urls
 * 	 	@property: sets a general path or domain url to be used as prefix for any of the file methods
 *            
 *  	@param (default) null  : unset $url
 *  	@param $url   : pointer directory or domain url
 *
 *  	Note:: 1) This property ignores its $url if $url parameter supplied in #FM is a domain url
 *  	Note:: 2) The general url set by this property is exited by import(), close() and open() methods but not close('/')
 *
 * C) Resource::name($name) // creates new or selects/activate an existing group 
 * 	 	@param $name: group name to be created or selected
 * 
 * D) Resource::callFile($url), :getFile($url,$store), :addFile($grpname,$url) //adds url to be exectuted
 * 	 	
 *		@property callFile: stores a $url into existing group
 * 	 	@property getFile: executes $url and/or stores depending on $store[true|false] 
 * 	 	@property addFile: stores $url into an existing $grpname            
 *
 *  	@param $url   : local url from root folder space or domain url
 *
 *  	Note:: 1) domain url with no extension can be added using three colons + script extension e.g ($url.':::js'), ($url.':::css')
 *
 * E) Resource::close(true | '\' | null ) // closes selected group / clears resource class
 *		@property: stores a $url into existing group
 * 	 	
 *		Resource::close(true): // clears resource class
 * 	 	Resource::close('\') : // safely exits a selected group(s). same as Resource::open();
 *		Resource::close():     // if a group is selected use close('\') else close(true) 
 * 
 * F)  Resource::parent($url | (defaut))
 * 	 	
 *		@property: prepends / prefix $url to a stored url which is about to be imported
 *
 * 	 	@param (default): false => exits prefixing
 * 	 	@param $url: $url could be a pointer out of a directory or a domain url
 *
 *  	Note:: 1) any stored domain $url will be ignored during prefixing.
 *
 *
 * G)  Resource::import()  // imports urls stored in groups with their group name
 *		@method 1  :import($grpname) 			 		   => imports a group or lists of groups
 *
 * 	 	@method 2A  :import($prefixUrl,$grpname)           => import:storedUrl($prefixUrl.$url)
 * 	 			2B  :import("pre::".$prefixUrl,$grpname))  => import:($prefixUrl.storedUrl($url)) //same as using :parent() but is safely exited;
 *
 *           foreach $url found in $grpname:
 *
 *              2A  :imports ($prefixUrl + $url) as a single $newurl, where $newurl is validated
 *              2B  :imports $prefixUrl + (($url) as a single url), where $prefixUrl is only a prefix but only $url is validated
 *
 * 	 	@method = 3 (:import($prefixUrl,$grpname,$imports)) $url: $url could be a pointer out of a directory or a domain url
 *  	Note:: 1) any already stored domain $url will not be ignored during prefixing.
 *
 *
 * I)  Resource::export()  //  Takes similar parameters as Resource::import() and returns similar response in array format.
 * 
 * J) Resource::used()       // lists all stored urls
 * K) Resource::active()     // returns true if any group is currently selected or active
 * L) Resource::error('all') // returns errors found when url is being stored called  
 *
 */



/*

  A - RESOURCE SAMPLES 

  NOTE: /fol means root project folder and is automatically hidden for only tags like css or javascript files in online mode but not html or php.

  //ungrouped
  Resource::callFile("core/assets/css/design.css");          # /fol/core/assets/css/design.css   : (css tag)  *validated
  Resource::callFile("core/assets/css/colors.css");          # /fol/core/assets/css/colors.css   : (css tag)  *validated
  Resource::callFile("core/assets/css/global.css");          # /fol/core/assets/css/global.css   : (css tag)  *validated

  //grouped
  Resource::name('css');  //css sample - active group
  Resource::callFile("core/assets/css/design.css");          # /fol/core/assets/css/design.css   : (css tag)  *validated
  Resource::callFile("../core/assets/css/colors.css");       # /fol/core/assets/css/colors.css   : (css tag)  *validated
  Resource::callFile("https://www.somesite.com/item.css");   # https://www.file.com/item.css     : (css tag)  *not_validated 
  Resource::callFile("https://www.somesite.com/item:::css"); # https://www.file.com/item         : (css tag)  *not_validated

  //grouped
  Resource::name('js');  // javascript sample - active group
  Resource::callFile("core/assets/jquery/anime.js");         # /fol/core/assets/jquery/anime.js  : (js tag)   *validated  
  Resource::callFile("../core/assets/jquery/device.js");     # ../core/assets/jquery/device.js   : (js tag)   *validated
  Resource::callFile("https://www.somesite.com/popup.js");   # https://www.somesite.com/popup.js : (js tag)   *not_validated
  Resource::callFile("https://www.somesite.com/file:::js");  # https://www.somesite.com/file     : (js tag)   *not_validated 

  Resource::close('/') // safely exit out of active group (Note: any url stored after this line goes into ungrouped space)

  Resource::import(":css");                      // import urls stored inside css
  Resource::import([":",":css"]);                // import urls stored in ungrouped, css respectively
  Resource::import([":",":css",":js"]);          // import urls stored in ungrouped, css, js respectively
  var_dump(Resource::export([":",":css",":js"])) // export urls stored in ungrouped, css, js respectively
  
  Resource::import('../',[":",":css"]);      //add ../ to relative urls, validate and import
  Resource::import('pre::../',[":",":css"]); //add ../ to relative urls, import (affect only this import)

  Resource::parent("../") //add ../ to relative urls, import (affect all imports after this line). Unset this with Resource::parent() 
  Resource::import([":",":css"]); //add ../ to relative urls, import  
  Resource::import(":js"); //add ../ to relative urls, import
  Resource::parent(); // unsets ../

  B - IMPORTING STORED URLS

  //Importing Sample 1 #import items inside the groups  js, css using an upper directory
     
     Resource::import("../",[":js",":css"]); 

     //1) only urls starting with protocols {https://, http:// and www} will be ignored
     //2) the ../ directory supplied will be used for validating for files except 1 above


  //Importing Sample 2 #import items inside the groups  js, css using an domain url
     
     Resource::import('pre::https://www.site.ng',[":",':ahst']); 

     //1) only urls starting with protocols {https://, http:// and www} will be ignored
     //2) all pointers in urls staring with ../ will be replaced singly with the new https://www.site.ng


  //Importing Sample 3 
     
     Resource::import("pre::../",[":",":css"]); // export items inside ungrouped, css. Add a prefix of ../ to all items excluding domain urls

     // 1) ../ directory alone will be validated if it exists before being used as prefix else response will be null
     // 2) only stored urls NOT starting with protocols {https://, http:// and www} will be prefixed with ../


  //Declaring a general parent directory or prefix //same as Resource::import("pre::../",$param) but in a general scope

    //Resource::parent("../",[":",":css"]);  

    // 1) ../ will be prefixed to all append Resource::import() declared after unless Resource::parent() is used to unset the directory ;
    // 2) using Resource::import("pre::../",[":",":css"]); will overide  Resource::parent() but does not unset it;

*/


