<?php
require_once (dirname(__file__) . '/../../classes/application/ApplicationService.php');
require_once (dirname(__file__) . '/../../classes/MongooseProduct.php');

class AdminMongooseImportController extends ModuleAdminController
{
	private $current_import_step;
	private $current_key_in_xml;
	private $xml_filename;
	private $xml_line_count;
	private $xml_content;

	private $xml_lang;

	private $mg_errors = array(); //MonGoose errors Tools::displayError();

	private $mongoose_product_current_row;

	
	public function __construct()
	{
		parent::__construct();
		$this->bootstrap = true;

		//Check the step value;
		$this->current_import_step = (int)Configuration::get('MONGOOSE_CURRENT_IMPORT_STEP');
		$this->current_key_in_xml = (int)Configuration::get('MONGOOSE_CURRENT_KEY_IN_XML');
		$this->xml_filename = Configuration::get('MONGOOSE_XML_FILENAME');
		$this->xml_line_count = Configuration::get('MONGOOSE_XML_LINECOUNT');
		$this->xml_lang = Configuration::get('MONGOOSE_XML_LANG');
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/js/mongooseimport1.js');
	}

	public function renderList()
	{
		$_html = "";

		if(isset($_POST["submitStep1Mongoose"]) && $_POST["submitStep1Mongoose"]==1) //Step 1 Submit
		{
			//On importe le fichier
			$result = $this->uploadXml();
			if(isset($result['file']['error']) && !empty($result['file']['error']))
				$_html .= $this->module->displayError($result['file']['error']);
			else
			{
				$_html .= $this->module->displayConfirmation($result['file']['filename']);
				$this->changeStep(1);
				$this->xml_lang = $_POST['xml_lang'];
				Configuration::updateValue('MONGOOSE_XML_LANG',$this->xml_lang);
				$this->xml_filename = $result['file']['filename'];
				Configuration::updateValue('MONGOOSE_XML_FILENAME',$this->xml_filename);
				Configuration::updateValue('MONGOOSE_CURRENT_KEY_IN_XML',0);
				$this->xml_content = simplexml_load_file($this->getPath($this->xml_filename));
				$this->xml_line_count = count($this->xml_content);
				Configuration::updateValue('MONGOOSE_XML_LINECOUNT',(int)$this->xml_line_count);
			}
		}

		if(isset($_POST['submitTestImporter']) && $_POST['submitTestImporter']==1)
		{
			$this->xml_content = simplexml_load_file($this->getPath($this->xml_filename));
			$this->importProductFromXml($this->xml_content->product[21]);

		}

		if(isset($_POST['submitGoToStep3']) && $_POST['submitGoToStep3']==1)
		{
			$this->changeStep(2);
		}

		// Get the current step
		$this->checkCurrentStep();
		if(!$this->checkTheXMLExist())
		{
			if($this->xml_filename != '')
				$_html .= $this->module->displayError('The xml file doesn\'t exist. You need to re-upload it.');
		}

		$_html .= $this->module->display(_MODULE_DIR_.'mongoose', 'views/templates/admin/importStepBreadcrumb.tpl');

		if($this->current_import_step === 0)
			$_html .= $this->stepOne();
		elseif($this->current_import_step === 1)
			$_html .= $this->stepTwo();
		elseif($this->current_import_step === 2)
			$_html .= $this->stepThree();

		return $_html.parent::renderList();
	}

	public function stepOne()
	{
		$lang_options = array();
		foreach(Language::getLanguages(true) as $lang)
		{
			$lang_options[] = array(
				'id' => (string)$lang['iso_code'],
				'name' => (string)$lang['name']
			);
		}
		//Ici on va créer une form
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Settings'),
			),
			'input' => array(
				array(
					'type' => 'file',
					'label' => $this->l('Votre fichier'),
					'name' => 'file',
					'required' => true,
				),
				array(
					'type' => 'select',
					'label' => $this->l('La langue du fichier'),
					'name' => 'xml_lang',
					'required' => true,
					'options' => array(
						'query' => $lang_options,
						'id' => 'id',
						'name' => 'name'
					)
				)
				
			),
			'submit' => array(
				'title' => $this->l('	Save 	'),
				'class' => 'btn btn-default pull-right'
			)
		);
		$helper = new HelperForm();
		$helper->module = $this->module;
		$helper->token = Tools::getValue('token');
		//$helper->currentIndex = AdminController::$currentIndex;

		$helper->title = 'Import StepOne';
		$helper->submit_action = 'submitStep1Mongoose';
		$helper->fields_value['thefeedfile'] = '';
		$helper->fields_value['xml_lang'] = '';
		return $helper->generateForm($fields_form);
	}

	public function stepTwo()
	{
		//Ici on va demander au client de cliquer sur un bouton pour démarrer un import vers une table intermédiaire
		$this->context->smarty->assign(array(
			'xml_lang' => $this->xml_lang,
			'xml_line_count' => $this->xml_line_count,
			'xml_filename' => $this->xml_filename,
			'xml_current_key' => $this->current_key_in_xml
		));
		return $this->context->smarty->fetch(_PS_MODULE_DIR_.'mongoose/views/templates/admin/steptwo.tpl');
	}
	public function stepThree()
	{
		if(Configuration::get('MONGOOSE_PRODUCT_CURRENT_ROW') === false)
		{
			if(Configuration::get('MONGOOSE_PRODUCT_CURRENT_ROW') === 0)
				$this->mongoose_product_current_row = 0;
			else
			{
				p('on crée');
				$this->mongoose_product_current_row = 0;
				Configuration::updateValue('MONGOOSE_PRODUCT_CURRENT_ROW',$this->mongoose_product_current_row);
			}
		} else {
			$this->mongoose_product_current_row = Configuration::get('MONGOOSE_PRODUCT_CURRENT_ROW');
		}
		
		//$this->addProductFromMongoose();

		//Configuration::updateValue('MONGOOSE_PRODUCT_CURRENT_ROW',0);
		//ici on va proposer au client d'importer des produits de la table intermediare vers les produits de prestashop
		$sql = 'SELECT COUNT(*) FROM '. _DB_PREFIX_ . 'mongoose_product';
		$totalShop = Db::getInstance()->getValue($sql);
		
		$this->context->smarty->assign(array(
			'xml_lang' => $this->xml_lang,
			'mongoose_products_total' => $totalShop,
			'current_mongoose_product_row' => $this->mongoose_product_current_row
		));

		return $this->context->smarty->fetch(_PS_MODULE_DIR_.'mongoose/views/templates/admin/stepthree.tpl');
	}


	public function displayAjaxresetXmlCurrentKey()
	{
		$this->current_key_in_xml = 0;
		Configuration::updateValue('MONGOOSE_CURRENT_KEY_IN_XML',$this->current_key_in_xml);
		$return = array(
			'current_key_in_xml' => $this->current_key_in_xml
		);
		die(json_encode($return));
	}

	public function displayAjaxtransfertXml()
	{
		$return = array(
			'message' => 'transfering XML',
			'status' => 'looping_on_xml',
			'current_key_in_xml' => $this->current_key_in_xml
		);
		if($this->current_key_in_xml < $this->xml_line_count)
			$return = array_merge($return,$this->loopOnXml());
		else {
			$return = array_merge($return, array('message' => 'On arrive est à la fin du fichier', 'status' => 'loop_end'));
		}
		die(json_encode($return));
	}

	private function loopOnXml()
	{
		$return = array();
		$this->xml_content = simplexml_load_file($this->getPath($this->xml_filename));
		if($product_imp = $this->importProductFromXml($this->xml_content->product[(int)$this->current_key_in_xml])){
			++$this->current_key_in_xml;
			$return['product'] = $product_imp;
			$return['current_key_in_xml'] = $this->current_key_in_xml;
		} else {
			$return['current_key_in_xml'] = $this->current_key_in_xml;
		}
		
		Configuration::updateValue('MONGOOSE_CURRENT_KEY_IN_XML',$this->current_key_in_xml);
		return $return;
	}

	public function displayAjaxtransfertProducts()
	{
		$sql = 'SELECT COUNT(*) FROM '. _DB_PREFIX_ . 'mongoose_product';
		$total_row = Db::getInstance()->getValue($sql);
		$this->mongoose_product_current_row = Configuration::get('MONGOOSE_PRODUCT_CURRENT_ROW');
		$return = array(
			'message' => 'transfering mongoose_product to product',
			'status' => 'looping_on_products'
		);
		if((int)$this->mongoose_product_current_row < (int)$total_row)
			$return = array_merge($return,$this->loopOnProducts());
		else
			$return = array_merge($return, array('message' => 'Fin des produits', 'status' => 'loop_end'));
		
		die(json_encode($return));
	}

	private function loopOnProducts()
	{
		$return = array();
		if($product_added = $this->addProductFromMongoose()){
			++$this->mongoose_product_current_row;
			$return['product'] = $product_added;
		}
		$return['current_mongoose_product_row'] = $this->current_mongoose_product_row;
		Configuration::updateValue('MONGOOSE_PRODUCT_CURRENT_ROW',$this->mongoose_product_current_row);
		// $return['row_b'] = $this->mongoose_product_current_row;

		return $return;
	}

	private function addProductFromMongoose()
	{
		$id_lang = $this->getLang();
		$this->current_mongoose_product_row = Configuration::get('MONGOOSE_PRODUCT_CURRENT_ROW');
		$row = $this->current_mongoose_product_row;
		$sql = 'SELECT `id_mongoose_product` FROM `'. _DB_PREFIX_ . 'mongoose_product` LIMIT '.$row.',1';
		if ($results = Db::getInstance()->ExecuteS($sql)){
			$id_mongoose_product = $results[0]['id_mongoose_product'];
			$mongoose_product = new MongooseProduct($id_mongoose_product);
			$id_product = (int)Db::getInstance()->getValue('SELECT id_product FROM '._DB_PREFIX_.'product WHERE reference = \''.pSQL($mongoose_product->reference).'\'');
			$product = $id_product ? new Product((int)$id_product, true) : new Product();
			$product->reference = $mongoose_product->reference;
			$product->price = (float)$mongoose_product->price;
			$product->wholesale_price = (float)$mongoose_product->wholesale_price;
			$product->active = 0;
			// :TODO: change to be correct
			$product->id_category_default = 2;

			$product->name = $mongoose_product->name;
			$product->description = $mongoose_product->description;
			$product->link_rewrite = $mongoose_product->link_rewrite;

			if (!isset($product->date_add) || empty($product->date_add))
				$product->date_add = date('Y-m-d H:i:s');
			$product->date_upd = date('Y-m-d H:i:s');
			$id_product ? $product->updateCategories(array(2)) : $product->addToCategories(array(2));
		
			if(!$product->save())
				ApplicationService::log('Add product in ps','[pid:'.(int)$mongoose_product->id_mongoose_product.'] Cannot save product.');
			
		}
		return $product;
	}

	private function importProductFromXml($xml_product)
	{	
		
		$id_lang = $this->getLang();
		//p(strlen((string)$xml_product->title));
		$product = new MongooseProduct();
		$product->id_product_from_supplier = (int)$xml_product->id;
		$product->reference = (string)$xml_product->artnr;
		// :TODO: Checker le format des dates 
		$product->date_add = (string)$xml_product->date;
		$product->date_upd = (string)$xml_product->modifydate;
		$product->price = (float)number_format($xml_product->price->b2c / (1 + 21 / 100), 6, '.', '');
		$product->wholesale_price = (float)number_format((float)$xml_product->price->b2b, 6, '.', '');
		// :TODO: système pour transformer les Y en une quantitée
		$product->quantity = 0;
		$product->pics_list = serialize((array)$xml_product->pics->pic);
		// :TODO: convert the packing measure and test if exist from EDC XML
		// depth x width x height = packing 
		// $product->depth = 
		// $product->width = 
		// $product->height = 
		$product->supplier = 'EDC';
		$product->id_importer_category = 0;
		$product->id_importer_manufacturer = 0;
		$product->name = array((int)$id_lang => (string)$xml_product->title);
		//$product->name[(int)$id_lang] = (string)$xml_product->title;
		$product->description = array((int)$id_lang => (string)$xml_product->description);

		$product->link_rewrite = array((int)$id_lang => Tools::link_rewrite((string)$xml_product->title));
		
		
		if($product->add())
		{
			ApplicationService::log('Add product','[pid:'.(int)$xml_product->id.'] Product Added.');
			//Add other stuff
			// Add cat product
			// Add image
			// Add supplier
			// Add variant
		}
		else
		{

			ApplicationService::log('Add product','[pid:'.(int)$xml_product->id.'] Cannot save product.');
			$this->errors[] = Tools::displayError('An error occurred while creating product in mongoose_product table').' <b>'.$xml_product.'</b';
			$this->mg_errors[] = 'EDC-Product '.(int)$xml_product->id.' - '.Tools::displayError(' Cannot save product');
			
		}
		
		return $product;
	}

	private function uploadXml()
	{
		$filename_prefix = date('YmdHis').'-';

		if (isset($_FILES['file']) && !empty($_FILES['file']['error']))
		{
			switch ($_FILES['file']['error']) 
			{
				case UPLOAD_ERR_INI_SIZE:
					$_FILES['file']['error'] = Tools::displayError('The uploaded file exceeds the upload_max_filesize directive in php.ini. If your server configuration allows it, you may add a directive in your .htaccess.');
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$_FILES['file']['error'] = Tools::displayError('The uploaded file exceeds the post_max_size directive in php.ini.
						If your server configuration allows it, you may add a directive in your .htaccess, for example:')
					.'<br/><a href="'.$this->context->link->getAdminLink('AdminMeta').'" >
					<code>php_value post_max_size 20M</code> '.
					Tools::displayError('(click to open "Generators" page)').'</a>';
					break;
				break;
				case UPLOAD_ERR_PARTIAL:
					$_FILES['file']['error'] = Tools::displayError('The uploaded file was only partially uploaded.');
					break;
				break;
				case UPLOAD_ERR_NO_FILE:
					$_FILES['file']['error'] = Tools::displayError('No file was uploaded.');
					break;
				break;
			}
		}
		elseif (!preg_match('/.*\.xml$/i', $_FILES['file']['name']))
			$_FILES['file']['error'] = Tools::displayError('The extension of your file should be .xml.');
		elseif (!@filemtime($_FILES['file']['tmp_name']) || 
			!move_uploaded_file($_FILES['file']['tmp_name'], AdminMongooseImportController::getPath().$filename_prefix.str_replace("\0", '', $_FILES['file']['name'])))
			$_FILES['file']['error'] = $this->l('An error occurred while uploading / copying the file.');
		else
		{
			@chmod(AdminMongooseImportController::getPath().$filename_prefix.$_FILES['file']['name'], 0664);
			$_FILES['file']['filename'] = $filename_prefix.str_replace('\0', '', $_FILES['file']['name']);
		}

		return $_FILES;
	}

	public function getLang()
	{
		foreach (Language::getLanguages(true) as $languages)
		{
			if($languages['iso_code'] == $this->xml_lang)
				$id_lang = $languages['id_lang'];
		}
		return $id_lang;
	}

	public static function getPath($file = '')
	{
		return (_PS_MODULE_DIR_.'mongoose'.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR.$file);
	}

	private function checkCurrentStep()
	{
		$this->current_import_step = (int)Configuration::get('MONGOOSE_CURRENT_IMPORT_STEP');
	}

	private function checkTheXMLExist()
	{
		if (file_exists($this->getPath($this->xml_filename)))
		{
			return true;
		}
		else 
		{	
			$this->changeStep(0);
			return false;
		}
	}

	private function changeStep($nStep)
	{
		Configuration::updateValue('MONGOOSE_CURRENT_IMPORT_STEP',(int)$nStep);
		$this->current_import_step = (int)$nStep;
	}
}