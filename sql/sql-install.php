<?php
// Init
$sql = array();


//depth x width x height = packing from EDC XML
$sql['mongoose_supplier_config'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_supplier_config`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_supplier_config` (
		`id_mongoose_supplier_config` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_ps_supplier` int(10) unsigned NOT NULL,
		`src_file` varchar(255),
		`src_line_total` int(10),
		`src_current_line` int(10),
		`src_id_lang` int(10),
		PRIMARY KEY (`id_mongoose_supplier_config`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;
	INSERT INTO `'._DB_PREFIX_.'mongoose_supplier_config` (
		`id_mongoose_supplier_config`, `id_ps_supplier`, `src_file`, `src_line_total`, `src_current_line`, `src_id_lang`) 
	VALUES (
		NULL, \''.$the_id_supplier.'\', \'\', \'0\', \'0\', \'0\'
	);';

//Create intermediate table for the products
$sql['mongoose_product'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_product`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_product` (
		`id_mongoose_product` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_product_supplier` int(10) NOT NULL,
		`id_default_category_supplier` int(10) NOT NULL,
		`id_manufacturer_supplier`int(10) NOT NULL,
		`reference` varchar(32) DEFAULT NULL,
		`ean13` varchar(13) DEFAULT NULL,
		`date_add` datetime NOT NULL,
		`date_upd` datetime NOT NULL,
		`price` decimal(20,6) NOT NULL DEFAULT \'0.000000\',
		`wholesale_price` decimal(20,6) NOT NULL DEFAULT \'0.000000\',
		`quantity` int(10) NOT NULL DEFAULT \'0\',
		`pics_list` text NOT NULL,
		`depth` DECIMAL(20, 6) NOT NULL DEFAULT \'0\',
		`width` DECIMAL(20, 6) NOT NULL DEFAULT \'0\',
		`height` DECIMAL(20, 6) NOT NULL DEFAULT \'0\',
		`weight` DECIMAL(20, 6) NOT NULL DEFAULT \'0\',
		`supplier` varchar(55),
		`id_ps_supplier` int(10) NOT NULL,
		`do_update` tinyint(1) NOT NULL DEFAULT \'0\',
  		PRIMARY KEY (`id_mongoose_product`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
$sql['mongoose_product_shop'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_product_shop`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_product_shop` (
		`id_mongoose_product` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_shop` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id_mongoose_product`,`id_shop`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
$sql['mongoose_product_lang'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_product_lang`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_product_lang` (
		`id_mongoose_product` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_lang` int(10) unsigned NOT NULL,
		`name` varchar(255) NOT NULL,
		`description` text,
		`link_rewrite` varchar(128) NOT NULL,
		PRIMARY KEY (`id_mongoose_product`,`id_lang`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql['mongoose_category_product'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_category_product`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_category_product` (
		`id_mongoose_category` int(10) unsigned NOT NULL,
		`id_mongoose_product` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id_mongoose_category`,`id_mongoose_product`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

//Create intermediate table for the category
$sql['mongoose_category'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_category`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_category` (
		`id_mongoose_category` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_category_supplier` int(10) NOT NULL,
		`id_category_parent` int(11),
		PRIMARY KEY (`id_mongoose_category`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
$sql['mongoose_category_shop'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_category_shop`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_category_shop` (
		`id_mongoose_category` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_shop` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id_mongoose_category`,`id_shop`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
$sql['mongoose_category_lang'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_category_lang`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_category_lang` (
		`id_mongoose_category` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_lang` int(10) unsigned NOT NULL,
		`title` varchar(255) NOT NULL,
		PRIMARY KEY (`id_mongoose_category`,`id_lang`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

//Create intermediate table for the Manufacturers
$sql['mongoose_manufacturer'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_manufacturer`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_manufacturer` (
		`id_mongoose_manufacturer` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_manufacturer_supplier` int(10) NOT NULL,
		PRIMARY KEY (`id_mongoose_manufacturer`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
$sql['mongoose_manufacturer_shop'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_manufacturer_shop`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_manufacturer_shop` (
		`id_mongoose_manufacturer` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_shop` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id_mongoose_manufacturer`,`id_shop`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
$sql['mongoose_manufacturer_lang'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_manufacturer_lang`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_manufacturer_lang` (
		`id_mongoose_manufacturer` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_lang` int(10) unsigned NOT NULL,
		`title` varchar(255) NOT NULL,
		PRIMARY KEY (`id_mongoose_manufacturer`,`id_lang`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';


//Create intermediate attribute table
// $sql['mongoose_attribute'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_attribute`;
// 	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_attribute` (
// 		`id_mongoose_attribute` int(10) unsigned NOT NULL AUTO_INCREMENT,
// 		`id_mongoose_attribute_group` int(10) NOT NULL,
// 		PRIMARY KEY (`id_mongoose_attribute`)
// 	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
// $sql['mongoose_attribute_shop'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_attribute_shop`;
// 	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_attribute_shop` (
// 		`id_mongoose_attribute` int(10) unsigned NOT NULL AUTO_INCREMENT,
// 		`id_shop` int(10) NOT NULL,
// 		PRIMARY KEY (`id_mongoose_attribute`,`id_shop`)
// 	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
// $sql['mongoose_attribute_lang'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_attribute_lang`;
// 	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_attribute_lang` (
// 		`id_mongoose_attribute` int(10) unsigned NOT NULL AUTO_INCREMENT,
// 		`id_lang` int(10) NOT NULL,
// 		`name` varchar(255) NOT NULL,
// 		PRIMARY KEY (`id_mongoose_attribute`,`id_lang`)
// 	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

// //Create intermdiate attribute group table
// $sql['mongoose_attribute_group'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_attribute_group`;
// 	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_attribute_group` (
// 		`id_mongoose_attribute_group` int(10) unsigned NOT NULL AUTO_INCREMENT,
// 		`group_type` enum(\'select\', \'radio\', \'color\')  DEFAULT \'select\',
// 		PRIMARY KEY (`id_mongoose_attribute_group`)
// 	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
// $sql['mongoose_attribute_group_shop'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_attribute_group_shop`;
// 	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_attribute_group_shop` (
// 		`id_mongoose_attribute_group` int(10) unsigned NOT NULL AUTO_INCREMENT,
// 		`id_shop` int(10) unsigned NOT NULL,
// 		PRIMARY KEY (`id_mongoose_attribute_group`,`id_shop`)
// 	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
// $sql['mongoose_attribute_group_lang'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_attribute_group_lang`;
// 	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_attribute_group_lang` (
// 		`id_mongoose_attribute_group` int(10) unsigned NOT NULL AUTO_INCREMENT,
// 		`id_lang` int(10) unsigned NOT NULL,
// 		`name` varchar(128) NOT NULL,
// 		`public_name` varchar(64) NOT NULL,
// 		PRIMARY KEY (`id_mongoose_attribute_group`,`id_lang`)
// 	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql['mongoose_product_attribute'] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'mongoose_product_attribute`;
	CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mongoose_product_attribute` (
		`id_mongoose_product_attribute` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_product_supplier` int(10) NOT NULL,
		`id_mongoose_product` int(10) NOT NULL,
		`id_ps_attribute` int(10) NOT NULL,
		`reference` varchar(32) DEFAULT NULL,
		`ean13` varchar(13) DEFAULT NULL,
		`quantity` int(10) NOT NULL DEFAULT \'0\',
		PRIMARY KEY (`id_mongoose_product_attribute`)
	) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';