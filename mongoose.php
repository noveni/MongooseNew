<?php

if (!defined('_PS_VERSION_'))
	exit;

class Mongoose extends Module
{
	private $id_ps_supplier;
	public function __construct()
	{
		$this->name = 'mongoose';
		$this->tab = 'quick_bulk_update';
		$this->version = '1.0.0';
		$this->author = 'noveni';
		$this->need_instance = 0; // indicates wether to load the module's class when "Modules" page is call in back-office
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Mongoose - Dropshipping module');
		$this->description = $this->l('This module handle the dropshipping feature from external supplier');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
	}

	public function install()
	{
		if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);

		//Il faut crée le fournisseur EDC;
		$supplier = new Supplier();
		$supplier->name = 'EDC';
		$supplier->active = true;
		$supplier->add();
		$this->id_ps_supplier = $supplier->id;

		//Ajout d'un onglet à la racine du site
		$parentTab = Tab::getIdFromClassName('AdminMongoose');
		if (empty($parentTab))
			$parentTab = self::createTab(0,$this->name,'Mongoose - Dropshipping ','AdminMongoose');
		self::createTab($parentTab, $this->name, 'Products import', 'AdminMongooseImport');

		Configuration::updateValue('MONGOOSE_CURRENT_IMPORT_STEP', 0);
		Configuration::updateValue('MONGOOSE_CURRENT_PRODUCT_LINE', 0);
		

		if (!parent::install() || 
			!$this->installDb()
		)
			return false;
		return true;
	}


	public function uninstall()
	{
		$this->uninstallModuleTab('AdminMongoose');
		$this->uninstallModuleTab('AdminMongooseImport');

		Configuration::deleteByName('MONGOOSE_CURRENT_IMPORT_STEP');
		Configuration::deleteByName('MONGOOSE_CURRENT_PRODUCT_LINE');

		if (!parent::uninstall() ||
			!$this->uninstallDb()
		)
			return false;

		return true;
	}

	/* Création des tables */
	public function installDb()
	{
		$return = true;
		$the_id_supplier = $this->id_ps_supplier;
		// Install SQL
		include (dirname(__FILE__).'/sql/sql-install.php');
		foreach($sql as $s)
		{

			Db::getInstance()->execute($s);
			$erreur_sql = Db::getInstance()->getMsgError();
			if (!empty($erreur_sql))
			{
				$this->uninstall();
				$return = false;
				throw new Exception('erreur SQL : '.Db::getInstance()->getMsgError());
			}
		}
		return $return;
	}

	/* Suppression des tables */
	public function uninstallDb()
	{
		include(dirname(__FILE__).'/sql/sql-install.php');
		foreach ($sql as $name => $v)
			Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.$name.';');
		return true;
	}

	static function createTab($id_parent, $module, $name, $class_name)
	{
		$Tab = new Tab();
		$Tab->module = $module;
		foreach (Language::getLanguages(true) as $languages)
		{
			$Tab->name[$languages["id_lang"]] = $name;
		}

		$Tab->id_parent = $id_parent;
		$Tab->class_name = $class_name;
		$r = $Tab->save();

		if ($r == false)
			return false;

		return $Tab->id;
	}

	private function uninstallModuleTab($tabClass)
	{
		$idTab = Tab::getIdFromClassName($tabClass);
		if ($idTab != 0)
		{
			$tab = new Tab($idTab);
			$tab->delete();
			return true;
		}
		return false;
	}
}