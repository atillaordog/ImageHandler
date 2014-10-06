<?php

namespace ImageHandler;

/**
 * Config class that loads configuration from file and overwrites defaults as necessary
 */
class Config
{	
	public $storage = 'db';
	public $db = array(
		'server' => '',
		'user' => '',
		'pass' => '',
		'database' => '',
		'table_name' => ''
	);
	public $sizes = array(
		'normal' => array( 'width' => 50, 'height' => 50 )
	);
	public $validate_base_size = true;
	public $is_crop = true;
	public $max_upload_size = 5242880;
	public $base_path = '';
	
	public function __construct(Array $config = array())
	{
		
		$file = IMAGEHANDLER_ROOT.'config.php';
		
		if ( file_exists($file) )
		{
			$file_config = include($file);
		}
		
		$self_config = get_object_vars($this);
		
		foreach ( $self_config as $key => $value )
		{
			if ( array_key_exists($key, $file_config) )
			{
				$this->$key = $file_config[$key];
			}
			
			if ( array_key_exists($key, $config) )
			{
				$this->$key = $config[$key];
			}
		}
	}
}