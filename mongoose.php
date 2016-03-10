<?php

require_once (dirname(__file__) . '/classes/application/MongooseApplicationService.php');

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
			$parentTab = MongooseApplicationService::createTab(0,$this->name,'Mongoose - Dropshipping ','AdminMongoose');
		//MongooseApplicationService::createTab($parentTab, $this->name, 'Products import', 'AdminMongooseImport');
		//self::createTab($parentTab, $this->name, 'Products list', 'AdminMongooseSupplierProduct');

		initTabsStuff();
		// $list_tab = array(
		// 	array(
		// 		'name' => 'Products import',
		//  		'class_name' => 'AdminMongooseImport',
		//  		'active' => 0
		// 	),
		// 	array(
		// 		'name' => 'Products list',
		//  		'class_name' => 'AdminMongooseSupplierProduct',
		//  		'active' => 0
		// 	)
		// );


		//Configuration::updateValue('MONGOOSE_TAB_LIST', serialize($list_tab));
		//MongooseApplicationService::installAllTabs($parentTab,$this->name,$this->list_tab);
		

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

		MongooseApplicationService::uninstallModuleTab('AdminMongoose');
		
		//MongooseApplicationService::uninstallModuleTab('AdminMongooseImport');
		//$this->uninstallModuleTab('AdminMongooseSupplierProduct');

		//On va chercher la liste des onglet enregistré et on les supprimes
		MongooseApplicationService::uninstallAllTabs(unserialize(Configuration::get('MONGOOSE_TAB_LIST')));
		
		Configuration::deleteByName('MONGOOSE_TAB_LIST');
		Configuration::deleteByName('MONGOOSE_CURRENT_IMPORT_STEP');
		Configuration::deleteByName('MONGOOSE_CURRENT_PRODUCT_LINE');

		if (!parent::uninstall() ||
			!$this->uninstallDb()
		)
			return false;

		return true;
	}

	public function getContent()
	{
		$_html = null;
		// Make a small verification to see if the table exist, if is there a table in, and if not we need to install all the crap
		$this->initTabsStuff();

		if (Tools::isSubmit('installSupPro'.$this->name))
		{
			// Ici on fait on peut générer un nouvel onglet
			//$this->installProductSupplierCtrl();

		}




		$list_tab_original = unserialize(Configuration::get('MONGOOSE_TAB_LIST'));
		$list_tab_to_tpl = $list_tab_original;
		$nlist_tab = count($list_tab_to_tpl);
		for($i = 0; $i < $nlist_tab; ++$i){
			$list_tab_to_tpl[$i]['link'] = AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules').'&configure='.$this->name.'&install'.$list_tab_to_tpl[$i]['class_name'].$this->name;
			//On va tester si y a un submit
			if (Tools::isSubmit('install'.$list_tab_to_tpl[$i]['class_name'].$this->name))
			{
				$list_tab_original[$i]['active'] = 1;
				$list_tab_to_tpl[$i]['active'] = 1;
				Configuration::updateValue('MONGOOSE_TAB_LIST', serialize($list_tab_original));
				MongooseApplicationService::createTab(Tab::getIdFromClassName('AdminMongoose'), $this->name,$list_tab_original[$i]['name'],$list_tab_original[$i]['class_name']);
				//d('ok on install '.$list_tab[$i]['class_name']);

			}

		}
		
		//We need to make loop to check wether or not the controller tab is installed
		$this->context->smarty->assign(
			array(
				'list_tab' => $list_tab_to_tpl 
			)
		);

		$_html .= $this->display(__FILE__, 'views/templates/admin/configpanel.tpl');

		return $_html;
	}

	private function installProductSupplierCtrl()
	{
		// We do a small job verification to see a configuration array exist
		if(!ConfigurationCore::get('MONGOOSE_TAB_LIST'))
		{
			Configuration::updateValue('MONGOOSE_TAB_LIST','');
		} 
		
		// else the job is to retrieve the data and unserialize it,
		// And if a tab info if is not present
		$tab_existing = false;
		$tab_list = unserialize(Configuration::get('MONGOOSE_TAB_LIST'));
		$ntab_list = count($tab_list);
		for($i = 0; $i < $ntab_list; ++$i)
		{
			//Si on trouve une occurence de l'onglet dans la liste, on notifie la variable $tab_existing qu'il faudrat ajouter l'onglet
			if($tab_list[$i]['class_name'] == 'AdminMongooseSupplierProduct')
				$tab_existing = true;
		}
		if(!$tab_existing){
			$tab_list[] = array(
				'name' => 'Products list',
		 		'class_name' => 'AdminMongooseSupplierProduct'
			);
			Configuration::updateValue('MONGOOSE_TAB_LIST',serialize($tab_list));
			self::createTab(Tab::getIdFromClassName('AdminMongoose'), $this->name, 'Product list', 'AdminMongooseSupplierProduct');
		}
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

	private function initTabsStuff()
	{
		$list_tab = array(
			array(
				'name' => 'Products import',
		 		'class_name' => 'AdminMongooseImport',
		 		'active' => 0
			),
			array(
				'name' => 'Products list',
		 		'class_name' => 'AdminMongooseSupplierProduct',
		 		'active' => 0
			)
		);

		$nlist_tab = count($list_tab);
		for ($i = 0; $i < $nlist_tab; ++$i)
		{
			$idTab = Tab::getIdFromClassName($list_tab[$i]['class_name']);
			if ($idTab) // Si l'onglet existe on change sa valeur active dans le tableau list_tab
				$list_tab[$i]['active'] = 1;
		}
		Configuration::updateValue('MONGOOSE_TAB_LIST', serialize($list_tab));
		$parentTab = Tab::getIdFromClassName('AdminMongoose');
		MongooseApplicationService::installAllTabs($parentTab,$this->name,$list_tab);
	}
}