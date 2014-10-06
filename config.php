<?php
// Config file that returns a simple array with the desired configuration parameters

return array(
	'db' => array(
		'server' => '',
		'user' => '',
		'pass' => '',
		'database' => '',
		'table_name' => 'images'
	),
	'sizes' => array(
		'normal' => array( 'width' => 50, 'height' => 50 )
	),
	'validate_base_size' => true,
	'is_crop' => true,
	'max_upload_size' => 5242880,
	'base_path' => ''
);