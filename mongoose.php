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

		// Create or update the supplier if it already exist
		$id_supplier = Supplier::getIdByName('EDC');
		if ($id_supplier === FALSE){
			$supplier = new Supplier();
			$supplier->name = 'EDC';
		}
		else
			$supplier = new Supplier($id_supplier);

		$supplier->active = true;
		$supplier->save();
		
		$this->id_ps_supplier = $supplier->id;

		//Ajout des onglet à la racine du site
		$this->initTabsStuff();

		// Configuration::updateValue('MONGOOSE_CURRENT_IMPORT_STEP', 0);
		Configuration::updateValue('MONGOOSE_CURRENT_PRODUCT_LINE', 0);
		Configuration::updateValue('MONGOOSE_PRODUCT_CURRENT_ROW', 0);
		Configuration::updateValue('MONGOOSE_ERASE_DB', 0);
		
		$this->checkTable();

		// if (!parent::install() || 
		// 	!$this->installDb()
		// )
		if (!parent::install())
			return false;
		return true;
	}


	public function uninstall()
	{

		MongooseApplicationService::uninstallModuleTab('AdminMongoose');
		//On va chercher la liste des onglet enregistré et on les supprimes
		MongooseApplicationService::uninstallAllTabs(unserialize(Configuration::get('MONGOOSE_TAB_LIST')));
		
		Configuration::deleteByName('MONGOOSE_TAB_LIST');
		// Configuration::deleteByName('MONGOOSE_CURRENT_IMPORT_STEP');
		Configuration::deleteByName('MONGOOSE_CURRENT_PRODUCT_LINE');
		Configuration::deleteByName('MONGOOSE_PRODUCT_CURRENT_ROW');




		if(Configuration::get('MONGOOSE_ERASE_DB'))
			$this->uninstallDb();
		
		Configuration::deleteByName('MONGOOSE_ERASE_DB');

		if (!parent::uninstall())
			return false;
		return true;
	}
	/**
	* This function provie a way to complete the installation even if we have add the module to prestashop before
	*
	*/
	private function checkTable()
	{
		$erreur = array();
		include (dirname(__FILE__).'/sql/sql-install.php');
		// the array is called $sql;

		foreach ($sql as $key => $s)
		{
			// We check if the table allready exist
			$table_exist_sql = 'SHOW TABLES LIKE \''._DB_PREFIX_.$key.'\';';
			if (!Db::getInstance()->executeS($table_exist_sql))
			{
				//If the table not exist we install it
				Db::getInstance()->execute($s);
				$erreur_sql = Db::getInstance()->getMsgError();
				if (!empty($erreur_sql))
					$erreur[] = $erreur_sql;

			}	
			else
			{
				// If the table exist we verify the structure
				preg_match_all('/`(\w+)`/mi', $s, $matches); // We catch all the columns name of the table
				$final_columns = array_unique($matches[1]); // We erase the duplicate entry
				// We search in the array an entry who could be the name of the table
				$key_to_unset = array_search(_DB_PREFIX_.$key,$final_columns);
				if ($key_to_unset !== FALSE)
					unset($final_columns[(int)$key_to_unset]); // We unset it
				$final_columns = array_values($final_columns);

				$describe_sql = 'DESCRIBE '._DB_PREFIX_.$key;
				$existing_columns = Db::getInstance()->executeS($describe_sql);
				$existing_columns = array_column($existing_columns, 'Field');
				$nfinal_columns = count($final_columns);
				for ($i = 0; $i < $nfinal_columns; $i++)
				{
					if (!in_array($final_columns[$i],$existing_columns)) // If we don't find the item in existing table, we create it
					{
						$pattern = '/`'.(string)$final_columns[$i].'`(.+),\n/i';
						preg_match($pattern, $s, $columns_spec);
						$sql_append_fields = 'ALTER TABLE `'._DB_PREFIX_.$key.'` ADD `'.(string)$final_columns[$i].'` '.$columns_spec[1];
						Db::getInstance()->execute($sql_append_fields);
						$erreur_sql = Db::getInstance()->getMsgError();
						if (!empty($erreur_sql))
							$erreur[] = $erreur_sql;
					}
				}
				// We check if in the existing table is there columns that need to be removed
				$nexisting_columns = count($existing_columns);
				for ($j = 0; $j < $nexisting_columns; $j++)
				{
					if (!in_array($existing_columns[$j],$final_columns)) // If an existing column is not in the futur table, we delete this field form the db
					{
						$sql_erase_fields = 'ALTER TABLE `'._DB_PREFIX_.$key.'` DROP `'.(string)$existing_columns[$j].'`';
						Db::getInstance()->execute($sql_erase_fields);
						$erreur_sql = Db::getInstance()->getMsgError();
						if (!empty($erreur_sql))
							$erreur[] = $erreur_sql;
					}
				}

			}
		}
	}

	public function getContent()
	{
		$_html = null;

		if (Tools::isSubmit('submit'.$this->name))
		{
			$erase_db = Tools::getValue('erase_db_on') ? 1 : 0;
			Configuration::updateValue('MONGOOSE_ERASE_DB', (int)$erase_db);
		}
		$_html .= $this->_configForm();

		//We need to make loop to check wether or not the controller tab is installed
		// $this->context->smarty->assign(
		// 	array(
		// 		'list_tab' => $list_tab_to_tpl,
		// 		'module_link' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules').'&configure='.$this->name.'&checkinstall'.$this->name
		// 	)
		// );

		// $_html .= $this->display(__FILE__, 'views/templates/admin/configpanel.tpl');

		return $_html;
	}

	private function _configForm()
	{
		$options = array(
	  		array(
			    'id_option' => 0,                 // The value of the 'value' attribute of the <option> tag.
			    'name' => 'Method 1'              // The value of the text content of the  <option> tag.
		  	),
		  	array(
			    'id_option' => 1,
			    'name' => 'Method 2'
		  	),
		);
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Configuration du module'),
				'icon' => 'icon-cogs'
			),
			'input' => array(
				array(
					'type' => 'checkbox',
					//'label' => $this->l('Effacer la table à la désinstallation du module'),
					'name' => 'erase_db',
					//'desc' => $this->l('En cochant la case, vous permettez au module d\'éffacer les tables et tout ce qu\'elles contiennent.'),
					'values' => array(
					    'query' => array(
					    	array(
					    		'id' => 'on',
					    		'name' => $this->l('Effacer la table à la désinstallation du module'),
					    		'val' => '1'
					    	),
					    ),
					    'id'    => 'id',                        // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
					    'name'  => 'name'                              // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
					  ),
				)
			),
			'submit' => array(
				'title' => $this->l('	Save	'),
				'class' => 'btn btn-default pull-right'
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Title and Toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = false;
		$helper->toolbar_scroll = true;
		$helper->submit_action = 'submit'.$this->name;

		$helper->fields_value['erase_db_on'] = (int)Configuration::get('MONGOOSE_ERASE_DB');
		return $helper->generateForm($fields_form);
	}
	// private function installProductSupplierCtrl()
	// {
	// 	// We do a small job verification to see a configuration array exist
	// 	if(!ConfigurationCore::get('MONGOOSE_TAB_LIST'))
	// 	{
	// 		Configuration::updateValue('MONGOOSE_TAB_LIST','');
	// 	} 
		
	// 	// else the job is to retrieve the data and unserialize it,
	// 	// And if a tab info if is not present
	// 	$tab_existing = false;
	// 	$tab_list = unserialize(Configuration::get('MONGOOSE_TAB_LIST'));
	// 	$ntab_list = count($tab_list);
	// 	for($i = 0; $i < $ntab_list; ++$i)
	// 	{
	// 		//Si on trouve une occurence de l'onglet dans la liste, on notifie la variable $tab_existing qu'il faudrat ajouter l'onglet
	// 		if($tab_list[$i]['class_name'] == 'AdminMongooseSupplierProduct')
	// 			$tab_existing = true;
	// 	}
	// 	if(!$tab_existing){
	// 		$tab_list[] = array(
	// 			'name' => 'Products list',
	// 	 		'class_name' => 'AdminMongooseSupplierProduct'
	// 		);
	// 		Configuration::updateValue('MONGOOSE_TAB_LIST',serialize($tab_list));
	// 		self::createTab(Tab::getIdFromClassName('AdminMongoose'), $this->name, 'Product list', 'AdminMongooseSupplierProduct');
	// 	}
	// }

	// /* Création des tables */
	// public function installDb()
	// {
	// 	$return = true;
	// 	$the_id_supplier = $this->id_ps_supplier;
	// 	// Install SQL
	// 	include (dirname(__FILE__).'/sql/sql-install.php');
	// 	foreach($sql as $s)
	// 	{

	// 		Db::getInstance()->execute($s);
	// 		$erreur_sql = Db::getInstance()->getMsgError();
	// 		if (!empty($erreur_sql))
	// 		{
	// 			$this->uninstall();
	// 			$return = false;
	// 			throw new Exception('erreur SQL : '.Db::getInstance()->getMsgError());
	// 		}
	// 	}
	// 	return $return;
	// }

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
		$parentTab = Tab::getIdFromClassName('AdminMongoose');
		if (empty($parentTab))
			$parentTab = MongooseApplicationService::createTab(0,$this->name,'Mongoose - Dropshipping ','AdminMongoose');

		$list_tab = array(
			array(
				'name' => 'Products import',
		 		'class_name' => 'AdminMongooseImport',
		 		'active' => 1
			),
			array(
				'name' => 'Products list',
		 		'class_name' => 'AdminMongooseSupplierProduct',
		 		'active' => 1
			)
		);

		$nlist_tab = count($list_tab);
		for ($i = 0; $i < $nlist_tab; ++$i)
		{
			$idTab = Tab::getIdFromClassName($list_tab[$i]['class_name']);
			if ($idTab) // Si l'onglet existe on change sa valeur 'active' to 1 dans le tableau list_tab
				$list_tab[$i]['active'] = 1;
		}
		Configuration::updateValue('MONGOOSE_TAB_LIST', serialize($list_tab)); //On a plus besoin de ça je pense 
		MongooseApplicationService::installAllTabs($parentTab,$this->name,$list_tab);
	}
}