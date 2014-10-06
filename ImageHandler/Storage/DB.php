<?php

namespace ImageHandler\Storage;

use ImageHandler\Interfaces\Storage as StorageInterface;
use ImageHandler\ImageException as ImageException;
use ImageHandler\Entity\Image as ImageEntity;
use mysqli;

/**
 * The DB storage extension of the image handler, uses mysqli PHP extension
*/ 
class DB implements StorageInterface
{
	private $_config = array(
		'server' => '',
		'user' => '',
		'pass' => '',
		'database' => '',
		'table_name' => 'images'
	);
	
	private $_db = null;
	
	public function __construct(Array $config = array())
	{
		foreach ( $this->_config as $key => $value )
		{
			if ( array_key_exists($key, $config) )
			{
				$this->_config[$key] = $config[$key];
			}
		}
			
		if ( !class_exists('mysqli') )
		{
			throw new ImageException('Mysqli module not installed.');
		}
		
		if (strpos($this->_config['server'], ':') !== false)
		{
			list($server, $port) = explode(':', $this->_config['server']);
			$this->_db = @new mysqli($server, $this->_config['user'], $this->_config['pass'], $this->_config['database'], $port);
		}
		else
		{
			$this->_db = @new mysqli($this->_config['server'], $this->_config['user'], $this->_config['pass'], $this->_config['database']);
		}
		
		if ($this->_db->connect_errno) {
			throw new ImageException("Failed to connect to MySQL: (" . $this->_db->connect_errno . ") " . $this->_db->connect_error);
		}
	}
	
	public function create_storage()
	{
		$sql = 'CREATE TABLE IF NOT EXISTS `'.$this->_config['table_name'].'` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `object_id` int(10) unsigned NOT NULL,
			  `object_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  `name` text COLLATE utf8_unicode_ci NOT NULL,
			  `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  `size` float unsigned DEFAULT NULL,
			  `meta` text COLLATE utf8_unicode_ci,
			  PRIMARY KEY (`id`)
			)';
		
		if ( $this->_db->query($sql) )
		{
			return true;
		}
		
		return false;
	}
	
	public function add(ImageEntity $image)
	{
		$data = (array)$image;
		
		unset($data['id']);
		
		$sql = 'INSERT INTO '.$this->_config['table_name'].'(`'.implode('`,`', array_keys($data)).'`) VALUES(';
		
		foreach( $data as $key => $value )
		{
			if ( is_numeric($value) )
			{
				$sql .= $value.',';
			}
			else
			{
				$sql .= '"'.$value.'",';
			}
		}
		
		$sql = rtrim($sql, ',');
		
		$sql .= ')';
		
		if ( $this->_db->query($sql) )
		{
			return $this->_db->insert_id;
		}
		
		return 0;
	}
	
	public function update(ImageEntity $image, Array $fields, Array $by_fields)
	{
		$sql = 'UPDATE '.$this->_config['table_name'].' SET ';
		
		$where = ' WHERE (1 = 1) ';
		$data = (array)$image;
		
		foreach( $data as $key => $value )
		{
			if ( in_array($key, $fields) )
			{
				$sql .= '`'.$key.'` = '.((is_numeric($value))? $value : '"'.$value.'"').',';
			}
			
			if ( in_array($key, $by_fields) )
			{
				if ( !is_array($value) )
				{
					$where .= ' AND `'.$key.'` = '.((is_numeric($value))? $value : '"'.$value.'"').',';
				}
				else
				{
					$are_numbers = array_filter($value, 'is_numeric');
					if ( count($are_numbers) == count($value) )
					{
						$in = '('.implode(',', $value).')';
					}
					else
					{
						$in = '("'.implode('", "', $value).'")';
					}
					
					$where .= ' AND `'.$key.'` IN '.$in.',';
				}
			}
		}
		
		$sql = rtrim($sql, ',');
		$where = rtrim($where, ',');
		
		if ( $this->_db->query($sql.$where) )
		{
			return true;
		}
		
		return false;
	}
	
	public function delete(ImageEntity $image, Array $by_fields)
	{
		$sql = 'DELETE FROM '.$this->_config['table_name'].' WHERE (1 = 1) ';
		
		$data = (array)$image;
		
		foreach( $data as $key => $value )
		{
			if ( in_array($key, $by_fields) )
			{
				if ( !is_array($value) )
				{
					$sql .= ' AND `'.$key.'` = '.((is_numeric($value))? $value : '"'.$value.'"').',';
				}
				else
				{
					$are_numbers = array_filter($value, 'is_numeric');
					if ( count($are_numbers) == count($value) )
					{
						$in = '('.implode(',', $value).')';
					}
					else
					{
						$in = '("'.implode('", "', $value).'")';
					}
					
					$where .= ' AND `'.$key.'` IN '.$in.',';
				}
			}
		}
		
		$sql = rtrim($sql, ',');
		
		if ( $this->_db->query($sql) )
		{
			return true;
		}
		
		return false;
	}
	
	public function get(Array $by_params, $name_like = null, $limit = null, $offset = null)
	{
		$sql = 'SELECT * FROM '.$this->_config['table_name'].' WHERE (1 = 1) ';
		
		foreach( $by_params as $key => $value )
		{
			$sql .= ' AND `'.$key.'` = '.((is_numeric($value))? $value : '"'.$value.'"');
		}
		
		if ( $name_like != null )
		{
			$sql .= ' AND name LIKE "%'.$name_like.'%"';
		}
		
		if ( $limit != null )
		{
			$sql .= ' LIMIT '.(int)$limit;
			if ( $offset != null )
			{
				$sql .= ' OFFSET '.(int)$offset;
			}
		}
		
		$res = $this->_db->query($sql);
		
		$tmp = array();
		
		$res->data_seek(0);
		while ( $row = $res->fetch_assoc() ) 
		{
			$image = new ImageEntity();
			$image->inject_data($row);
			
			$tmp[] = $image;
		}
		
		return $tmp;
	}
	
	public function get_total(Array $by_params, $name_like = null)
	{
		$sql = 'SELECT COUNT(id) as nr FROM '.$this->_config['table_name'].' WHERE (1 = 1) ';
		
		foreach( $by_params as $key => $value )
		{
			$sql .= ' AND `'.$key.'` = '.((is_numeric($value))? $value : '"'.$value.'"');
		}
		
		if ( $name_like != null )
		{
			$sql .= ' AND name LIKE "%'.$name_like.'%"';
		}
		
		$sql .= ' LIMIT 1';
		
		$res = $this->_db->query($sql);
		
		$row = $res->fetch_row();
		
		return (int)$row[0];
	}
	
	public function exists()
	{
		$res = $this->_db->query('SHOW TABLES LIKE "'.$this->_config['table_name'].'"');
		
		return ($res->num_rows == 1);
	}
	
	public function destroy_storage()
	{
		$this->_db->query('DROP TABLE IF EXISTS '.$this->_config['table_name']);
	}
	
	public function begin_transaction()
	{
		$this->_db->begin_transaction();
	}
	
	public function commit_transaction()
	{
		$this->_db->commit();
	}
	
	public function rollback_transaction()
	{
		$this->_db->rollback();
	}
}