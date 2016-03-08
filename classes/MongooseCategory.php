<?php

class MongooseCategory extends ObjectModel
{
	public $id;
	public $id_mongoose_category;
	public $id_category_supplier;
	public $id_category_parent;
	public $title;


	public static $definition = array(
		'table' => 'mongoose_category',
		'primary' => 'id_mongoose_category',
		'multilang' => true,
		'fields' => array(
			'id_category_supplier' => 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'id_category_parent' => 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			/* Lang fields */
			'title' =>					array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true),

		)
	);

	public static function getIdMongooseCategoryByIdSupplier($id_category)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_mongoose_category`
		FROM `'._DB_PREFIX_.'mongoose_category`
		WHERE `id_category_supplier` = \''.(int)$id_category.'\'');

		if (isset($result['id_mongoose_category']))
			return (int)$result['id_mongoose_category'];

		return false;
	}
}