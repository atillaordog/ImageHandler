<?php

namespace ImageHandler\Validation;

use ImageHandler\Interfaces\Validation as ValidationInterface;
use ImageHandler\Entity\Image as ImageEntity;
use ImageHandler\Vendor\image\classes\Image as Image;
use ImageHandler\Vendor\image\classes\Image_GD as Image_GD;

class ImageValidation implements ValidationInterface
{
	private $_errors = array();
	
	private $_image_types = array('jpg', 'png', 'gif');
	
	private $types = array
	(
		'jpeg' => array('format' => 'H4', 'marker' => 'ffd8'),
		'jpg' => array('format' => 'H4', 'marker' => 'ffd8'),
		
		'png' => array('format' => 'H32', 'marker' => '89504e470d0a1a0a0000000d49484452'),
		
		'gif' => array
		(
			0 => array('format' => 'H8', 'marker' => '47494638'),
			1 => array('format' => 'H12', 'marker' => '474946383761'),
			2 => array('format' => 'H12', 'marker' => '474946383961')
		)
	);
	
	private $_options = array();
	
	public function valid(Array $image, Array $external_options)
	{
		$this->_options = $external_options;
		
		if ( !isset($image['error'])
			OR !isset($image['name'])
			OR !isset($image['type'])
			OR !isset($image['tmp_name'])
			OR !isset($image['size']))
		{
			$this->_errors['image'] = 'Uploaded image is not valid.';
			return false;
		}
		
		if ( $image['error'] != 0 )
		{
			$this->_errors['image'] = 'Uploaded image has error with code: '.$image['error'];
			return false;
		}
		
		$file_size = filesize($image['tmp_name']);
		
		if ( $file_size > $this->_options['max_upload_size'] )
		{
			$this->_errors['image'] = 'Uploaded image is too large.';
			return false;
		}
		
		$type_check = false;
		foreach ( $this->_image_types as $t )
		{
			if ( $this->is_file_type($image['tmp_name'], $t) )
			{
				$type_check = true;
				break;
			}
		}
	
		if ( !$type_check )
		{
			$this->_errors['image'] = 'File type is incorrect, can be .png, .jpg or .gif.';
			return false;
		}
		
		if ( $this->_options['validate_base_size'] )
		{
			if ( !array_key_exists('normal', $this->_options['sizes']) || !array_key_exists('width', $this->_options['sizes']['normal']) || !array_key_exists('height', $this->_options['sizes']['normal']) )
			{
				$this->_errors['size'] = 'No size set for relevance.';
				return false;
			}
			
			$width = $this->_options['sizes']['normal']['width'];
			$height = $this->_options['sizes']['normal']['height'];
			
			$check_image = Image::factory($image['tmp_name']);
		
			if ( $check_image->width < $width || $check_image->height < $height )
			{
				$this->_errors['size'] = 'The size of the photo has to be at least'.' '.$width.'x'.$height.'.';
				return false;
			}
		}
		
		return true;
	}
	
	public function get_errors()
	{
		return $this->_errors;
	}
	
	private function is_file_type($file = null, $type = null)
	{
		// Nothing to work with
		if ( ($file === null) || ($type === null) || (! isset($this->types[$type])) )
		{
			return false;
		}

		$type = $this->types[$type];

		// Determine the bytes to read from the file		
		$bytes_to_read = 0;
		
		// We have multiple formats / markers for the same file type
		if ( isset($type[0]) )
		{
			for ($i=0, $mi=count($type); $i<$mi; $i++)
			{
				if ( $bytes_to_read < (strlen($type[$i]['marker']) / 2) )
				{
					$bytes_to_read = (strlen($type[$i]['marker']) / 2);
				}
			}
		}
		else
		{
			$bytes_to_read = (strlen($type['marker']) / 2);
		}
		
		// Open the file and read 16 bytes
		$fp = fopen($file, 'r');
		$data = fread($fp, $bytes_to_read);
		fclose($fp);
			
		// We have multiple formats / markers for the same file type
		if ( isset($type[0]) )
		{
			for ($i=0, $mi=count($type); $i<$mi; $i++)
			{
				$x = unpack($type[$i]['format'], $data);
				if ( (isset($x[1])) && ($x[1] == strtolower($type[$i]['marker'])) )
				{
					return true;
				}
			}
		}
		else
		{
			$x = unpack($type['format'], $data);			
			if ( (isset($x[1])) && ($x[1] == strtolower($type['marker'])) )
			{
				return true;
			}
		}

		return false;
	}
}