<?php
/**
 * Autoload file that needs to be laoded to use ImageHandler
 * In this autoload type the file name has to match the class name
 * Since we use namespaces, we always have to add "use" if we want to use a class
 */

if (!defined('IMAGEHANDLER_ROOT')) {
    define('IMAGEHANDLER_ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);
}

spl_autoload_register('autoload');

function autoload($class)
{	
	if ( class_exists($class,FALSE) ) {
		// Already loaded
		return FALSE;
	}
	
	$class = str_replace('\\', '/', $class);

	if ( file_exists(IMAGEHANDLER_ROOT.$class.'.php') )
	{
		require(IMAGEHANDLER_ROOT.$class.'.php');
	}

	return false;
}