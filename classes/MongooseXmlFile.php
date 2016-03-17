<?php

class MongooseXmlFile extends ObjectModel
{
	public $id;
	public $id_mongoose_xml_file;
	public $src_file;
	public $src_line_total;
	public $src_current_line;
	public $src_id_lang;
	public static $definition = array(
		'table' => 'mongoose_xml_file',
		'primary' => 'id_mongoose_xml_file',
		'multilang' => false,
		'fields' => array(
			'src_file' => 				array('type' => self::TYPE_STRING, 'validate' => 'isString'),
			'src_line_total' => 		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'src_current_line' => 		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'src_id_lang' => 			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
		)
	);


	public static function getIdByIdLang($id_lang)
	{
		$result = Db::getInstance()->getRow('
			SELECT `id_mongoose_xml_file`
			FROM `'._DB_PREFIX_.'mongoose_xml_file`
			WHERE `src_id_lang` = '.(int)$id_lang);

		if (isset($result['id_mongoose_xml_file']))
			return (int)$result['id_mongoose_xml_file'];

		return false;
	}

	public static function getAllFiles()
	{
		$sql = 'SELECT * FROM '._DB_PREFIX_.'mongoose_xml_file WHERE 1=1';
		if ($rows = Db::getInstance()->ExecuteS($sql))
			return $rows;
		return array();
	}
}