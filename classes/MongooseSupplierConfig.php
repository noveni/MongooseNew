<?php

class MongooseSupplierConfig extends ObjectModel
{
	public $id;
	public $id_mongoose_supplier_config;
	public $id_ps_supplier;
	public $src_file;
	public $src_line_total;
	public $src_current_line;
	public $src_id_lang;
	public static $definition = array(
		'table' => 'mongoose_supplier_config',
		'primary' => 'id_mongoose_supplier_config',
		'multilang' => false,
		'fields' => array(
			'id_ps_supplier' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'src_file' => 				array('type' => self::TYPE_STRING, 'validate' => 'isString'),
			'src_line_total' => 		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'src_current_line' => 		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'src_id_lang' => 			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
		)
	);
}