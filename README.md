#Image Handler

This is a tool that is stand-alone and is used for image manipulation.

It has a very simple logic, it contains image validation and image saving.

It has an easy to use settings array in which you can define the sizes you want to crop to, the way you want to crop, folder structure where to save the data, etc.

It uses MySqli to handle DB requests

It uses Image class system from Kohana, a bit modified of course

Here is a full example of how to use ImageHandler

```php

include_once('ImageHandler/autoload.php');

// Initialize the class, it loads config from the config file, but configs can be overwritten from here
$imageHandler = new ImageHandler(
	array(
		'sizes' => array(
			'normal' => array('width' => 50, 'height' => 50)
		),
		'db' => array(
			'server' => 'localhost',
			'user' => 'root',
			'pass' => '',
			'database' => 'test',
			'table_name' => 'images'
		),
		'base_path' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'test_images'
	)
);

// Check if storage exists and create it if does not
if ( !$imageHandler->storage_exists() )
{
	$imageHandler->install_storage();
}

// Check if we have to delete
if ( array_key_exists('delete_image', $_GET) )
{
	$imageHandler->delete_image_by(array('object_id' => 1, 'object_type' => 'test'));
}

// Handle POST
if ( $_POST )
{
	if ( array_key_exists('image', $_FILES) )
	{
		// Validate and save image
		if ( $imageHandler->validate_image($_FILES['image']) )
		{
			$imageHandler->save_image($_FILES['image'], 1, 'test', 'test_images/1');
		}
		else
		{
			var_dump($imageHandler->get_errors());
		}
	}
}

echo 'Current image:';

$images = $imageHandler->get_image_by(array('object_id' => 1, 'object_type' => 'test'));

var_dump($images);
```