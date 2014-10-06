<?php

use ImageHandler\Interfaces\Storage as StorageInterface;
use ImageHandler\Config as Config;
use ImageHandler\ImageException as ImageException;
use ImageHandler\Entity\Image as ImageEntity;
use ImageHandler\Interfaces\Validation as ValidationInterface;
use ImageHandler\Validation\ImageValidation as ImageValidation;
use ImageHandler\Storage\DB as DBStorage;
use ImageHandler\Vendor\image\classes\Image as Image;

class ImageHandler
{	
	private $_storage;
	
	private $_config;
	
	private $_validation;
	
	private $_errors = array();
	
	/**
	 * Constructor.
	 * @param array $config Can overwrite default config upon creation
	 * If no storage class is provided, the config default will be used
	 * @param ServerMessage\Interfaces\Storage $storage
	 * @param ServerMessage\Interfaces\Validation $validation The validation class that validates the message, can be set externally
	 */
	public function __construct(Array $config = array(), StorageInterface $storage = null, ValidationInterface $validation = null)
	{
		$this->_config = new Config($config);
		
		if ( $storage == null )
		{
			$this->_storage = new DBStorage($this->_config->db);
		}
		else
		{
			$this->_storage = $storage;
		}
		
		if ( $validation == null )
		{
			$this->_validation = new ImageValidation();
		}
		else
		{
			$this->_validation = $validation;
		}
	}

	public function get_image_by(Array $params, $compare_name = null)
	{
		if ( empty($params) )
		{
			throw new ImageException('Parameter array cannot be empty.');
		}
		
		$correct_keys = array('object_id', 'object_type', 'id', 'name', 'path', 'type');
		
		foreach ( $params as $key => $value )
		{
			if ( !in_array($key, $correct_keys) )
			{
				unset($params[$key]);
			}
		}
		
		$results = $this->_storage->get($params, $compare_name);
		
		for ( $i = 0, $m = count($results); $i < $m; $i++ )
		{
			$results[$i]->meta = unserialize ( base64_decode( $results[$i]->meta ) );
		}
		
		return $results;
	}
	
	public function validate_image($image)
	{	
		return $this->_validation->valid($image, (array)$this->_config);
	}
	
	public function save_image($image, $object_id, $object_type, $path, $meta = null)
	{
		if ( $object_id != null )
		{
			if ( !is_numeric($object_id) || $object_type == '' )
			{
				throw new ImageException('Inconsistent object data. Please provide both object ID and object type.');
			}
		}
		
		$base = $this->_check_folder($path);
		
		$object_id = (int)$object_id;
		$object_type = ''.$object_type;
		
		$insert_data = array(
			'object_id' => $object_id,
			'object_type' => $object_type,
			'path' => $path,
			'meta' => base64_encode(serialize($meta)),
			'name' => '',
			'type' => $image['type'],
			'size' => 0
		);
		
		try
		{
			$this->_storage->begin_transaction();
			
			// Save original first
			$base_filename = time().'_'.$image['name'];
			
			$original_image = Image::factory($image['tmp_name']);
			$insert_data['name'] = $filename = 'original_'.$base_filename;
			$original_image->save($base.'/'.$filename);
			
			$insert_data['size'] = filesize($base.'/'.$filename);
			
			$entity = new ImageEntity();
			$entity->inject_data($insert_data);
			
			$this->_storage->add($entity);
			
			foreach( $this->_config->sizes as $key => $size )
			{
				$save_image = Image::factory($image['tmp_name']);
				
				if ( $save_image->width >= $size['width'] || $save_image->height >= $size['height'] )
				{
					$insert_data['name'] = $filename = $size['width'].'x'.$size['height'].'_'.$key.'_'.$base_filename;
					
					if ($this->_config->is_crop)
					{
						$save_image->resize($size['width'],$size['height'], Image::INVERSE);					
						$save_image->crop($size['width'],$size['height']);
					}
					else
					{
						$save_image->resize($size['width'],$size['height']);			
					}
					
					$save_image->save($base.'/'.$filename);
					
					$insert_data['size'] = filesize($base.'/'.$filename);
					
					$entity->inject_data($insert_data);
			
					$this->_storage->add($entity);
				}
			}
				
			$this->_storage->commit_transaction();
		}
		catch( Exception $e )
		{
			$this->_storage->rollback_transaction();
			throw $e;
		}
	}
		
	public function delete_image_by(Array $params)
	{
		$results = $this->get_image_by($params);
		
		for ( $i = 0, $m = count($results); $i < $m; $i++ )
		{
			$this->_storage->delete($results[$i], array('id'));
			@unlink($this->_config->base_path.'/'.$results[$i]->path.'/'.$results[$i]->name);
			@rmdir($this->_config->base_path.'/'.$results[$i]->path);
		}
	}
	
	/**
	 * Checks if storage exists and can be used
	 * @return boolean
	 */
	public function storage_exists()
	{
		return $this->_storage->exists();
	}
	
	/**
	 * Creates storage if that does not exist
	 */
	public function install_storage()
	{
		$this->_storage->create_storage();
	}
	
	/**
	 * Clear out the storage created for messages
	 */
	public function remove_storage()
	{
		$this->_storage->destroy_storage();
	}
	
	public function get_errors()
	{
		return $this->_validation->get_errors();
	}
	
	public function base_path()
	{
		return $this->_config->base_path;
	}
	
	private function _check_folder($path)
	{
		$path = str_replace('\\','/', $path);
		$folders = explode('/', $path);
		
		$base = $this->_config->base_path;
		
		if ( !file_exists($base) )
		{
			mkdir($base, 0755);
		}
		
		foreach ( $folders as $folder )
		{
			if ( !file_exists($base.'/'.$folder) )
			{
				mkdir($base.'/'.$folder, 0755);
			}
			$base .= '/'.$folder;
		}
		
		return $base;
	}
}