<?php
session_start();
require_once 'config.php';

// autoload data types & other classes
function __autoload($className) {
    if(file_exists('classes/'.$className.'.php'))
        include 'classes/'.$className.'.php';
    else
        throw new Exception("Class $className does not exists");
}
?>