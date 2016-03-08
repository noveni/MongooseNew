<?php

class MongooseAttribute extends ObjectModel
{
	public $id;
	public $id_mongoose_attribute;
	public $id_mongoose_attribute_group;
	public $name;

	public static $definition = array(
		'table' => 'mongoose_attribute',
		'primary' =>  'id_mongoose_attribute',
		'multilang' => true,
		'fields' => array(
			'id_mongoose_attribute_group' => 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			/* Lang fields */
			'name' =>							array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true),
		)
	);
}