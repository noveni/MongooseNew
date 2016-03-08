<?php

class MongooseAttributeGroup extends ObjectModel
{
	public $id;
	public $group_type;
	public $name;
	public $public_name;

	public static $defintion = array(
		'table' => 'mongoose_attribute_group',
		'primary' => 'id_mongoose_attribute_group',
		'multilang' => true,
		'fields' => array(
			'group_type' => 	array('type' => self::TYPE_STRING, 'required' => true),
			// Lang fields
			'name' => 			array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isName', 'required' => true),
			'public_name' => 	array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isName', 'required' => true)
		)
	);
}