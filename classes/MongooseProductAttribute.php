<?php

class MongooseProductAttribute extends ObjectModel
{
	public $id;
	public $id_mongoose_product_attribute;
	public $id_product_supplier;
	public $id_mongoose_product;
	public $id_ps_attribute;
	public $reference;
	public $ean13;
	public $quantity;

	public static $definition = array(
		'table' => 'mongoose_product_attribute',
		'primary' => 'id_mongoose_product_attribute',
		'multilang' => false,
		'fields' => array(
			'id_product_supplier' => 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'id_mongoose_product' => 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'id_ps_attribute' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'reference' => 				array('type' => self::TYPE_STRING, 'validate' => 'isReference'),
			'ean13' =>					array('type' => self::TYPE_STRING, 'validate' => 'isEan13'),
			'quantity' => 				array('type' => self::TYPE_INT, 'validate' => 'isInt'),
		)
	);

	public static function getIdMongooseProductAttributeByIdSupplier($id_product_supplier)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_mongoose_product_attribute`
		FROM `'._DB_PREFIX_.'mongoose_product_attribute`
		WHERE `id_product_supplier` = \''.(int)$id_product_supplier.'\'');

		if (isset($result['id_mongoose_product_attribute']))
			return (int)$result['id_mongoose_product_attribute'];

		return false;
	}

	public static function getByIdMgProduct($id_mg_product)
	{
		$results = Db::getInstance()->executes('
			SELECT *
			FROM `'._DB_PREFIX_.'mongoose_product_attribute`
			WHERE `id_mongoose_product` = \''.(int)$id_mg_product.'\'');
		if($results)
			return $results;

		return false;
	}
}