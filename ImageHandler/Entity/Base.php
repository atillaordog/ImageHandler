<?php
	
namespace ImageHandler\Entity;

class Base
{
	public function inject_data(Array $incoming)
	{
		$object_vars = get_object_vars($this);
		
		foreach ( $object_vars as $key => $value )
		{
			if ( array_key_exists($key, $incoming) )
			{
				$this->$key = $incoming[$key];
			}
		}
	}
	
	public function extract_data(Array $outgoing)
	{
		foreach ( $outgoing as $key => $value )
		{
			if ( property_exists($this, $key) )
			{
				$outgoing[$key] = $this->$key;
			}
		}
		
		return $outgoing;
	}
}