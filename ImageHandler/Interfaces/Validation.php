<?php

namespace ImageHandler\Interfaces;

use ImageHandler\Entity\Image as ImageEntity;

interface Validation
{
	/**
	 * Checks if the image is valid
	 * @param array $image The image array from $_FILES
	 * @param array $external_options These are the options set in config, since some of the are needed in validation
	 * @return boolean
	 */
	public function valid(Array $image, Array $external_options);
	
	/**
	 * Returns the errors that got set upon validation
	 * @return array
	 */
	public function get_errors();
}