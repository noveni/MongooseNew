<?php

class MongooseManufacturer extends ObjectModel
{
	public $id;
	public $id_mongoose_manufacturer;
	public $id_manufacturer_supplier;
	public $title;

	public static $definition = array(
		'table' => 'mongoose_manufacturer',
		'primary' => 'id_mongoose_manufacturer',
		'multilang' => TRUE,
		'fields' => array(
			'id_manufacturer_supplier' => 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			/* Lang fields */
			'title' =>						array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true),
		)
	);

	public static function getIdMongooseSupplierByIdSupplier($id_manufacturer)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_mongoose_manufacturer`
		FROM `'._DB_PREFIX_.'mongoose_manufacturer`
		WHERE `id_manufacturer_supplier` = \''.(int)$id_manufacturer.'\'');

		if (isset($result['id_mongoose_manufacturer']))
			return (int)$result['id_mongoose_manufacturer'];

		return false;
	}
}