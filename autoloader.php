<?php

/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

spl_autoload_register(function($class){
   
    $class = ltrim($class, '\\');
    //$namespace = 'XCore';
    
    $tmp = explode('\\', $class);
    $namespaces = $tmp[0];
    $classname = $tmp[count($tmp)-1];
    
    if ($namespaces !== 'MangaReader') {
    	$dir = __DIR__ . '/libs';
    	
    	if(strpos($class, $namespaces) === 0)
    {
        // $class = substr($class, strlen($namespaces));
        $path = '';
        if(($pos = strripos($class, '\\')) !== FALSE)
        {
            $path = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
            $class = substr($class, $pos + 1);
            
        }
        $path .= str_replace('__', '/', $class) . '.php';
        $dir .= '/' . $path;
        //echo "$dir<br>";
        
        // echo $dir;
        
        if(file_exists($dir))
        {
            include $dir;
            return true;
        }
        
        return false;
    }
    
    } else {
    	$dir = __DIR__;
    	
    	if(strpos($class, $namespaces) === 0)
    {
        
        $path = strtolower($classname) . '.php';
        $dir .= '/' . $path;
        //echo "$dir<br>";
        // echo $dir;
        
        if(file_exists($dir))
        {
            include $dir;
            return true;
        }
        
        return false;
    }
    
    }
    
    //$namespaces = substr($class, 0, strlen($classname)+1); // join($tmp, '\\');
    
    /* *
    print_r($tmp);
    echo " _ <br> _ namespace | ";
    print_r($namespaces);
    echo " _ <br> _ classname | ";
    print_r($classname);
    echo "<br>";
    * */
    
    return false;

});
