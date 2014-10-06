<?php

namespace ImageHandler\Interfaces;

use ImageHandler\Entity\Image as ImageEntity;

/**
 * We define an interface with the functionalities we need for storage
 */
interface Storage
{
	/**
	 * Generates the storage requirements for images
	 * @return boolean
	 */
	public function create_storage();
	
	/**
	 * Add an image to the storage
	 * @param ImageHandler\Entity\Image $image
	 * @return int Unique id of the newly added image
	 */
	public function add(ImageEntity $image);
	
	/**
	 * Updates image(s) by provided field(s)
	 * @param ImageHandler\Entity\Image $image
	 * @param Array $fields The fields to update, all if left empty, mapping is get from the entity
	 * ex. array('body', 'subject', 'sender_type')
	 * @param Array $by_fields The name(s) of the field(s) we want to update by. Allows the update of multiple items at once.
	 * @return boolean
	 */
	public function update(ImageEntity $image, Array $fields, Array $by_fields);
	
	/**
	 * Deletes image(s) by given field(s)
	 * @param ImageHandler\Entity\Image $image
	 * @param Array $by_fields The name(s) of the field(s) we want to delete by. Allows the deletion of multiple items at once.
	 * @return boolean
	 */
	public function delete(ImageEntity $image, Array $by_fields);
	
	/**
	 * Gets image(s) by given field-value pairs
	 * @param Array $by_params An associative array with field-value pairs. Ex. array('sender_id' => 23, 'sender_type' => 'support')
	 * @param string $name_like If set, the name is compared using this string
	 * @param int $limit Limit for pagination purposes
	 * @param int $offset Offset for pagination purposes
	 * @return Array returns an array with the found results, every element being of type ImageHandler\Entity\Image
	 */
	public function get(Array $by_params, $name_like, $limit, $offset);
	
	/**
	 * Gets the total number of images by given params
	 * @param Array $by_params An associative array with field-value pairs. Ex. array('sender_id' => 23, 'sender_type' => 'support')
	 * @param string $name_like If set, the name is compared using this string
	 * @return int
	 */
	public function get_total(Array $by_params, $name_like);
	
	/**
	 * Checks if the storage exists and can be used
	 * @return boolean
	 */
	public function exists();
	
	/**
	 * Destroys the created storage if needed
	 */
	public function destroy_storage();
	
	/**
	 * Begins transaction
	 */
	public function begin_transaction();
	
	/**
	 * Commits the changes made
	 */
	public function commit_transaction();
	
	/**
	 * Rolls back the changes if something went wrong
	 */
	public function rollback_transaction();
}