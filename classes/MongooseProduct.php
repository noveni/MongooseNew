<?php

class MongooseProduct extends ObjectModel
{
	public $id;
	public $id_mongoose_product;
	public $id_product_supplier;
	public $id_default_category_supplier;
	public $id_manufacturer_supplier;
	public $reference;
	public $ean13;
	public $date_add;
	public $date_upd;
	public $price = 0;
	public $wholesale_price = 0;
	public $quantity = 0;
	public $pics_list;
	public $depth = 0;
	public $width = 0;
	public $height = 0;
	public $weight = 0;
	public $supplier;
	public $id_ps_supplier;
	public $do_update = true;
	public $name;
	public $description;
	public $link_rewrite;

	public static $definition = array(
		'table' => 'mongoose_product',
		'primary' => 'id_mongoose_product',
		'multilang' => TRUE,
		'fields' => array(
			'id_product_supplier' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'id_default_category_supplier' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'id_manufacturer_supplier' => 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'reference' => 					array('type' => self::TYPE_STRING,'validate' => 'isReference'),
			'ean13' => 						array('type' => self::TYPE_STRING, 'validate' => 'isEan13'),
			'date_add' => 					array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
			'date_upd' => 					array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
			'price' => 						array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
			'wholesale_price' =>			array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'quantity' =>					array('type' => self::TYPE_INT,'validate' => 'isInt'),
			'pics_list' => 					array('type' => self::TYPE_STRING,'validate' => 'isString'),
			'depth' => 						array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
			'width' => 						array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
			'height' => 					array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
			'weight' => 					array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
			'id_ps_supplier' => 			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'supplier' => 					array('type' => self::TYPE_STRING, 'validate' => 'isString'),
			'do_update' =>					array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),

			/* Lang fields */
			'name' =>						array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true),
			'description' =>				array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
			'link_rewrite' =>				array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite',  'required' => true)
		)
	);

	public static function count(){
		return Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'mongoose_product`');
	}

	public static function getIdMongooseProductByIdSupplier($id_product)
	{
		$query = new DbQuery();
		$query->select('mp.id_mongoose_product');
		$query->from('mongoose_product', 'mp');
		$query->where('mp.id_product_supplier =\''.(int)$id_product.'\'');
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
	}

	public function toggleDoUpdate()
	{
		// Object must have a variable called 'active'
		if (!array_key_exists('do_update', $this))
			throw new PrestaShopException('property "do_update" is missing in object '.get_class($this));

		// Update only do_update field
		$this->setFieldsToUpdate(array('do_update' => true));
		// Update do_update status on object
		$this->do_update = !(int)$this->do_update;
		// Change status to do_update/don't do update
		return $this->update(false);
	}
	
}