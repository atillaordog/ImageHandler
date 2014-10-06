<?php

namespace ImageHandler\Entity;

use ImageHandler\Entity\Base as BaseEntity;

/**
 * Simple data class holding everything an image has to have
 */
class Image extends BaseEntity
{
	public $id = null;
	public $object_id = null;
	public $object_type = '';
	public $name = '';
	public $path = '';
	public $type = '';
	public $size = 0;
	public $meta = '';
}