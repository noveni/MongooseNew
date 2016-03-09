<?php

require_once (dirname(__file__) . '/../../classes/application/MongooseApplicationService.php');
require_once (dirname(__file__) . '/../../classes/MongooseProduct.php');
require_once (dirname(__file__) . '/../../classes/MongooseCategory.php');
require_once (dirname(__file__) . '/../../classes/MongooseManufacturer.php');
require_once (dirname(__file__) . '/../../classes/MongooseProductAttribute.php');
require_once (dirname(__file__) . '/../../classes/MongooseSupplierConfig.php');


class AdminMongooseImportController extends ModuleAdminController
{
	private $current_step;
	private $src_file;
	private $src_line_total;
	private $src_current_line;
	private $src_content;
	private $src_id_lang;
	private $id_ps_supplier;
	private $mongoose_product_current_line;

	private $importer_errors = array();

	public function __construct()
	{
		parent::__construct();
		$this->bootstrap = true;

		$this->getConfig();
	}

	public function setMedia()
	{
		parent::setMedia();
		//$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/js/mongooseimport1.js');
		$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/js/mongooseimport.js');
	}

	public function renderList()
	{
		// $this->importLineFromFile($this->src_content->product[7269]);
		// d('bye');
		// $this->transfertLineFromDb();
		// d('bye');
		$_html = "";

		if (isset($_POST["submitStep0Mongoose"]) && $_POST["submitStep0Mongoose"] == 1)
		{
			$_html = $this->stepZeroExecute();
		}
		elseif (isset($_POST["step1form-nextstep"]) && $_POST["step1form-nextstep"] == 1)
		{
			$this->current_step = 2;
			$this->updateConfig();
		}
		//d($this->current_step);

		if ($this->current_step == 0)
			$_html .= $this->stepZeroHTML();
		if ($this->current_step == 1)
			$_html .= $this->stepOneHTML();
		if ($this->current_step == 2)
			$_html .= $this->stepTwoHTML();

		return $_html.parent::renderList();
	}

	private function updateConfig()
	{
		$config = new MongooseSupplierConfig(1);
		$config->src_file = $this->src_file;
		$config->src_line_total = $this->src_line_total;
		$config->src_current_line = $this->src_current_line;
		$config->src_id_lang = $this->src_id_lang;
		$config->save();
		Configuration::updateValue('MONGOOSE_CURRENT_IMPORT_STEP', $this->current_step);
		Configuration::updateValue('MONGOOSE_CURRENT_PRODUCT_LINE', $this->mongoose_product_current_line);
	}

	private function getConfig()
	{
		$this->mongoose_product_current_line = (int)Configuration::get('MONGOOSE_CURRENT_PRODUCT_LINE');
		$this->current_step = (int)Configuration::get('MONGOOSE_CURRENT_IMPORT_STEP');
		// We need to check if there is a supplier
		$config = new MongooseSupplierConfig(1);
		$this->src_file = $config->src_file;
		$this->src_line_total = $config->src_line_total;
		$this->src_current_line = $config->src_current_line;
		$this->src_id_lang = $config->src_id_lang;
		$this->id_ps_supplier = $config->id_ps_supplier;
		if($this->src_file != '')
			$this->src_content = simplexml_load_file($this->getPath($this->src_file));
	}

	private function stepZeroExecute()
	{
		$_html = "";
		//On importe le fichier
		$result = $this->uploadXml();
		if(isset($result['file']['error']) && !empty($result['file']['error']))
			$_html .= $this->module->displayError($result['file']['error']);
		else
		{
			$_html .= $this->module->displayConfirmation($result['file']['filename']);
			$this->current_step = 1;

			$this->src_file = $result['file']['filename'];
			$this->src_content = simplexml_load_file($this->getPath($this->src_file));
			$this->src_line_total = count($this->src_content);
			$this->src_current_line = 0;
			$this->src_id_lang = (int)$_POST['xml_lang'];
			//d((int)$_POST['xml_lang']);

			$this->updateConfig();
		}
		return $_html;
	}

	public function stepZeroHTML() //Initial form to add file and choose lang
	{
		$lang_options = array();
		foreach(Language::getLanguages(false) as $lang)
		{
			$lang_options[] = array(
				'id' => (string)$lang['id_lang'],
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
		$helper->title = 'Import StepOne';
		$helper->submit_action = 'submitStep0Mongoose';
		$helper->fields_value['xml_lang'] = '';
		return $helper->generateForm($fields_form);
	}

	public function stepOneHTML()
	{
		//Ici on va demander au client de cliquer sur un bouton pour démarrer un import vers une table intermédiaire
		$this->context->smarty->assign(array(
			'src_lang' => Language::getLanguage($this->src_id_lang)['name'],
			'src_line_total' => $this->src_line_total,
			'src_file' => $this->src_file,
			'src_current_line' => $this->src_current_line
		));
		return $this->context->smarty->fetch(_PS_MODULE_DIR_.'mongoose/views/templates/admin/stepone.tpl');
	}
	
	public function stepTwoHTML()
	{
		$this->context->smarty->assign(array(
			'mongoose_products_total' => MongooseProduct::count(),
			'current_mongoose_product_row' => $this->mongoose_product_current_line
		));
		return $this->context->smarty->fetch(_PS_MODULE_DIR_.'mongoose/views/templates/admin/steptwo.tpl');
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

	public static function getPath($file = '')
	{
		return (_PS_MODULE_DIR_.'mongoose'.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR.$file);
	}

	public function displayAjaximportSrcLine()
	{
		//$this->getConfig();
		$return = array(
			'status' => 'looping_src_line',
			'message' => 'transfering file',
			'current_line_in_src' => $this->src_current_line
		);

		if($this->src_current_line < $this->src_line_total){
			//Import

			if ($line_imported = $this->importLineFromFile($this->src_content->product[(int)$this->src_current_line])){
				++$this->src_current_line;
				$return['product'] = $line_imported;
				$return['current_line_in_src'] = $this->src_current_line;
			}
		}
		else
		{
			$this->current_step = 2;
			$return = array_merge($return, array('message' => 'On arrive est à la fin du fichier', 'status' => 'end_file'));
		}
		$this->updateConfig();
		die(json_encode($return));
	}

	public function displayAjaxtransfertMongooseLine()
	{
		$total_row = MongooseProduct::count();
		//$this->mongoose_product_current_row = Configuration::get('MONGOOSE_PRODUCT_CURRENT_ROW');
		$return = array(
			'message' => 'transfering mongoose_product to product',
			'status' => 'looping_on_products',
			'current_mongoose_product_line' => $this->mongoose_product_current_line
		);
		if((int)$this->mongoose_product_current_line < (int)$total_row){
			// transfer
			if($line_transfered = $this->transfertLineFromDb()){
				++$this->mongoose_product_current_line;
				$return['product'] = $line_transfered;
				$return['current_mongoose_product_line'] = $this->mongoose_product_current_line;
			}
			//$return = array_merge($return,$this->loopOnProducts());
		}	
		else
		{
			$this->current_step = 3;
			$return = array_merge($return, array('message' => 'Fin des produits', 'status' => 'loop_end'));
		}
		$this->updateConfig();
		die(json_encode($return));
	}

	private function transfertLineFromDb()
	{
		
		$return = array();	
		$id_lang_fr = (int)Language::getIdByIso('fr');//$this->src_id_lang
		//$id_lang = $this->getLang();
		//$this->current_mongoose_product_row = Configuration::get('MONGOOSE_PRODUCT_CURRENT_ROW');
		//$row = $this->current_mongoose_product_row;
		$sql = 'SELECT `id_mongoose_product` FROM `'. _DB_PREFIX_ . 'mongoose_product` ORDER BY `id_mongoose_product` LIMIT '.$this->mongoose_product_current_line.',1';
		if ($results = Db::getInstance()->ExecuteS($sql)){
			$id_mongoose_product = $results[0]['id_mongoose_product'];
			$mongoose_product = new MongooseProduct($id_mongoose_product);
			$id_product = (int)Db::getInstance()->getValue('SELECT id_product FROM '._DB_PREFIX_.'product WHERE reference = \''.pSQL($mongoose_product->reference).'\'');
			
			if ($id_product)
				$return['maj'] = 'Mise à jour du produit';
			else
				$return['add'] = 'Rajout du produit';

			$product = $id_product ? new Product((int)$id_product, true) : new Product();
			$product->reference = $mongoose_product->reference;
			$product->price = (float)$mongoose_product->price;
			$product->id_supplier = (float)$mongoose_product->id_ps_supplier;
			$product->supplier_reference = $mongoose_product->reference;
			$product->wholesale_price = (float)$mongoose_product->wholesale_price;
			$product->active = 0;
			$product->image = unserialize($mongoose_product->pics_list);
			// :TODO: change to be correct
			$product->id_category_default = 2;

			$product->name = $mongoose_product->name;
			$product->description = $mongoose_product->description;
			$product->link_rewrite = $mongoose_product->link_rewrite;

			if (!isset($product->date_add) || empty($product->date_add))
				$product->date_add = date('Y-m-d H:i:s');
			$product->date_upd = date('Y-m-d H:i:s');
			$id_product ? $product->updateCategories(array(2)) : $product->addToCategories(array(2));
		

			// Manufacturer
			$mongoose_manufacturer = new MongooseManufacturer($mongoose_product->id_manufacturer_supplier);
			if (isset($mongoose_manufacturer) && is_string($mongoose_manufacturer->title[$id_lang_fr]) && !empty($mongoose_manufacturer->title[$id_lang_fr]))
			{
				if ($manufacturer = Manufacturer::getIdByName($mongoose_manufacturer->title[$id_lang_fr]))
					$product->id_manufacturer = (int)$manufacturer;
				else
				{
					$manufacturer = new Manufacturer();
					$manufacturer->name = (string)$mongoose_manufacturer->title[$id_lang_fr];
					$manufacturer->active = true;
					if (($field_error = $manufacturer->validateFields(UNFRIENDLY_ERROR, true)) === true &&
						($lang_field_error = $manufacturer->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true && $manufacturer->add())
						$product->id_manufacturer = (int)$manufacturer->id;
					else
					{
						$this->errors[] = sprintf(
							Tools::displayError('%1$s (ID: %2$s) cannot be saved'),
							$manufacturer->name,
							(isset($manufacturer->id) && !empty($manufacturer->id))? $manufacturer->id : 'null'
						);
						$this->errors[] = ($field_error !== true ? $field_error : '').(isset($lang_field_error) && $lang_field_error !== true ? $lang_field_error : '').
							Db::getInstance()->getMsgError();
					}
				}
			}

			//Add Categories
			$category_path_lang = array();
			$category_path = array();
			$sql_mg_categories = '
				SELECT `id_mongoose_category` 
				FROM `'._DB_PREFIX_.'mongoose_category_product`
				WHERE `id_mongoose_product` = \''.(int)$mongoose_product->id.'\'';
			if ($results = Db::getInstance()->ExecuteS($sql_mg_categories)){
				$nmg_categories = count($results);
				for($i = 0;$i<$nmg_categories;++$i)
				{
					$mongoose_category = new MongooseCategory($results[$i]['id_mongoose_category']);
					if((int)$mongoose_category->id_category_parent !=0)
					{
						$mongoose_parent_category = new MongooseCategory((int)$mongoose_category->id_category_parent);
						foreach ($mongoose_category->title as $key => $value) {
							$category_path_lang[$key] = (string)$mongoose_parent_category->title[$key].'/'.(string)$value;
						}
						$category_path[] = $category_path_lang;
					}
					
				}
			}
			
			if (isset($category_path) && is_array($category_path) && count($category_path))
			{
				$product->id_category = array(); // Reset default values array
				foreach ($category_path as $category_path_lang) 
				{
					foreach ($category_path_lang as $key => $value) {
						if (is_string($value) && !empty($value))
						{
							$category = Category::searchByPath($key, trim($value), 'AdminImportControllerCore', 'productImportCreateCat');
							if ($category['id_category'])
								$product->id_category[] = (int)$category['id_category'];
							else
								$this->errors[] = sprintf(Tools::displayError('%1$s cannot be saved'), trim($value));
						}
					}
				}
				$product->id_category = array_values(array_unique($product->id_category));
				
			}
	
			if(!$product->save())
				MongooseApplicationService::log('Add product in ps','[pid:'.(int)$mongoose_product->id_mongoose_product.'] Cannot save product.');

			// Attribute
			$mongoose_product_attribute = MongooseProductAttribute::getByIdMgProduct($mongoose_product->id);

			//p($mongoose_product_attribute);
			$nmg_product_attribute = count($mongoose_product_attribute);
			for($j = 0; $j<$nmg_product_attribute; ++$j)
			{
				$upd_prod = false;
				$attribute_combination = $product->getAttributeCombinations($id_lang_fr);
				// p($attribute_combination);
				$nattribute_combination = count($attribute_combination);
				for($k = 0;$k<$nattribute_combination; ++$k)
				{
					// p($attribute_combination[$k]['id_attribute']);
					// p($mongoose_product_attribute[$j]['id_ps_attribute']);
					// p('ee');
					// p($attribute_combination[$k]['supplier_reference']);
					// p($mongoose_product_attribute[$j]['reference']);
					if($attribute_combination[$k]['id_attribute'] == $mongoose_product_attribute[$j]['id_ps_attribute'] && 
						$attribute_combination[$k]['supplier_reference'] == $mongoose_product_attribute[$j]['reference'])
					{
						// p('ok');
						$product->updateAttribute($attribute_combination[$k]['id_product_attribute'], 0, 0, 0, 0, 0,
							0, $mongoose_product_attribute[$j]['reference'], $mongoose_product_attribute[$j]['ean13'],true);
						$upd_prod = true;
					}
				}
				
				if(!$upd_prod){
					$attribute_object = $product->addAttribute(0,0,0,0,0,$mongoose_product_attribute[$j]['reference'],$mongoose_product_attribute[$j]['ean13'],
							true);
					$product->addAttributeCombinaison((int)$attribute_object,array($mongoose_product_attribute[$j]['id_ps_attribute']));
					//p($attribute_object);
				}


				
	
			}


			//Category
			if (isset($product->id_category) && is_array($product->id_category))
				$product->updateCategories(array_map('intval', $product->id_category));

			//Association au supplier
			//$productSupplier 
			//Supplier
			if (isset($product->id) && $product->id && isset($product->id_supplier) && property_exists($product, 'supplier_reference'))
			{

				$id_product_supplier = (int)ProductSupplier::getIdByProductAndSupplier((int)$product->id, 0, (int)$product->id_supplier);
				if ($id_product_supplier)
					$product_supplier = new ProductSupplier($id_product_supplier);
				else
					$product_supplier = new ProductSupplier();
				$product_supplier->id_product = (int)$product->id;
				$product_supplier->id_product_attribute = 0;
				$product_supplier->id_supplier = (int)$product->id_supplier;
				$product_supplier->product_supplier_price_te = $product->wholesale_price;
				$product_supplier->product_supplier_reference = $product->supplier_reference;
				$product_supplier->save();
			}


			//Image
			$base_url = 'http://cdn.edc-internet.nl/500/';
			if (isset($product->image) && is_array($product->image) && count($product->image))
			{
				foreach ($product->image as $key => $url)
				{
					$url = $base_url.$url;
					$url = trim($url);
					$error = false;
					if (!empty($url))
					{
						$url = str_replace(' ', '%20', $url);
						$image = new Image();
						$image->id_product = (int)$product->id;
						$image->position = Image::getHighestPosition($product->id) + 1;
						$image->cover = true;
						// file_exists doesn't work with HTTP protocol
						if ($image->add())
						{
							$image->associateTo($shops);
							if (!AdminImportController::copyImg($product->id,$image->id,$url,'products',!Tools::getValue('regenerate')))
							{
								$image->delete();
								$this->warnings[] = sprintf(Tools::displayError('Error copying image: %s'), $url);
							}
						}
						else
							$error = true;
					}
					else
						$error = true;
					if ($error)
						$this->warnings[] = sprintf(Tools::displayError('Product #%1$d: the picture (%2$s) cannot be saved.'), $image->id_product, $url);
				}
			}
			
		}
		//p($product);

		$return['id_product'] = $product->id;
		$return['product'] = $product;
		
		return $return;
	}
	
	private function importLineFromFile($src_line)
	{
		$id_lang_fr = (int)Language::getIdByIso('fr');

		if(empty($src_line->title))
			$src_line->title = (string)$src_line->artnr;
		// Adding product
		if($id_mongoose_product = MongooseProduct::getIdMongooseProductByIdSupplier((int)$src_line->id))
		{
			$product = new MongooseProduct($id_mongoose_product);
			if(empty($product->name[Configuration::get('PS_LANG_DEFAULT')]))
				$product->name[Configuration::get('PS_LANG_DEFAULT')] = (string)$src_line->title;
			$product->name[(int)$this->src_id_lang] = (string)$src_line->title;
			if(empty($product->link_rewrite[Configuration::get('PS_LANG_DEFAULT')]))
				$product->link_rewrite[Configuration::get('PS_LANG_DEFAULT')] = (string)$src_line->title;
			$product->link_rewrite[(int)$this->src_id_lang] = Tools::link_rewrite((string)$src_line->title);
			//$product->name = array((int)$this->src_id_lang => (string)$src_line->title);
		}
		else
		{
			$product = new MongooseProduct();
			$product->id_product_supplier = (int)$src_line->id;
			//If title is empty we use the artnr value

			// If it's a new product without and if the default language is not the same as the actual lang
			if(Configuration::get('PS_LANG_DEFAULT') != (int)$this->src_id_lang){
				$product->name = array((int)$this->src_id_lang => (string)$src_line->title,
										Configuration::get('PS_LANG_DEFAULT') => (string)$src_line->title);
				$product->link_rewrite = array((int)$this->src_id_lang => Tools::link_rewrite((string)$src_line->title),
										Configuration::get('PS_LANG_DEFAULT') => Tools::link_rewrite((string)$src_line->title) );
			} else {
				$product->name = array((int)$this->src_id_lang => (string)$src_line->title);
				$product->link_rewrite = array((int)$this->src_id_lang => Tools::link_rewrite((string)$src_line->title));
			}
		}

		$product->reference = (string)$src_line->artnr;
		//$product->name = array((int)$this->src_id_lang => (string)$src_line->title);

		$product->description[$this->src_id_lang] = (string)$src_line->description;
		//$product->link_rewrite = array((int)$this->src_id_lang => Tools::link_rewrite((string)$src_line->title));
		//$product->date_add = (string)$src_line->date;
		//$product->date_upd = (string)$src_line->modifydate;
		$product->price = (float)number_format($src_line->price->b2c / (1 + 21 / 100), 6, '.', '');
		$product->wholesale_price = (float)number_format((float)$src_line->price->b2b, 6, '.', '');
		$product->quantity = 0;
		//Length x width x height.
		if (isset($src_line->measures->packing) && !empty($src_line->measures->packing)){
			$packing = explode("x",(string)$src_line->measures->packing);
			$npacking = count($packing);
			for($n=0;$n<$npacking;++$n)
				$packing[$n] = (float)$packing[$n];
			list($product->depth,$product->width,$product->height) = $packing;
		}
		if (isset($src_line->measures->weight) && !empty($src_line->measures->weight))
			$product->weight = (float)$src_line->measures->weight;
		$product->supplier = 'edc';
		$product->id_ps_supplier = $this->id_ps_supplier;
		$product->do_update = true;
		$product->pics_list = serialize((array)$src_line->pics->pic);;
		
		//Adding manufacturer
		if($id_mongoose_manufacturer = MongooseManufacturer::getIdMongooseSupplierByIdSupplier($src_line->brand->id))
		{
			$manufacturer = new MongooseManufacturer($id_mongoose_manufacturer,(int)$this->src_id_lang);
		} 
		else 
		{
			$manufacturer = new MongooseManufacturer();
		}
		$manufacturer->id_manufacturer_supplier = (int)$src_line->brand->id;
		
		if(Configuration::get('PS_LANG_DEFAULT') != (int)$this->src_id_lang)
		{
			 $manufacturer_title = array((int)$this->src_id_lang => (string)$src_line->brand->title,
										Configuration::get('PS_LANG_DEFAULT') => (string)$src_line->brand->title);
		}
		else
		{
			$manufacturer_title = array((int)$this->src_id_lang => (string)$src_line->brand->title);
		}
		$manufacturer->title = $manufacturer_title;

		if(!$manufacturer->save())
			MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$src_line->id.'] Manufacturer can t be updated.');
		
		$product->id_manufacturer_supplier = $manufacturer->id;

		if(!$product->save())
			MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$src_line->id.'] Product can t be saved.');

		// Now i've got the id product
		// I can do the rest of the install

		//Adding category
		$ncategory = count($src_line->categories->category);
		
		for($l = 0;$l < $ncategory; ++$l)
		{
			$nundercat = count($src_line->categories->category[$l]->cat);

			for($m = 0;$m < $nundercat; ++$m)
			{
				if($m == 0)
					$id_parent = 0;
				else
				{
					$id_parent = MongooseCategory::getIdMongooseCategoryByIdSupplier((int)$src_line->categories->category[$l]->cat[$m-1]->id);
				}
				//p($src_line->categories->category[$l]->cat[$m]);
				//Tester si la categories existe déjà
				if ($id_mongoose_category = MongooseCategory::getIdMongooseCategoryByIdSupplier((int)$src_line->categories->category[$l]->cat[$m]->id))
				{
					$category = new MongooseCategory($id_mongoose_category);
					$category->title[(int)$this->src_id_lang] = (string)$src_line->categories->category[$l]->cat[$m]->title;
					//p($category);
					//$category->title = array((int)$this->src_id_lang => (string)$src_line->categories->category[$l]->cat[$m]->title);
				}
				else
				{
					$category = new MongooseCategory();
					$category->id_category_supplier = (int)$src_line->categories->category[$l]->cat[$m]->id;
					if(Configuration::get('PS_LANG_DEFAULT') != (int)$this->src_id_lang)
					{
						$category->title = array((int)$this->src_id_lang => (string)$src_line->categories->category[$l]->cat[$m]->title,
													(int)Configuration::get('PS_LANG_DEFAULT') => (string)$src_line->categories->category[$l]->cat[$m]->title);
					}
					else 
					{
						$category->title = array((int)$this->src_id_lang => (string)$src_line->categories->category[$l]->cat[$m]->title);
					}
					
				}
				
				$category->id_category_parent = (int)$id_parent;
				if(!$category->save())
					MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$src_line->id.'] Categories '.$category->title.' can t be added.');
				if($l == 0 && $m == 1)
					$id_default_category = $category->id;


				//Need to test if already exist
				$count_id_cat_id_prod_sql = '
						SELECT COUNT(*) 
						FROM '._DB_PREFIX_.'mongoose_category_product 
						WHERE id_mongoose_category='.(int)$category->id.' && id_mongoose_product='.(int)$product->id;
				$total_entry = Db::getInstance()->getValue($count_id_cat_id_prod_sql);
				
				if($total_entry == 0)
					if(Db::getInstance()->insert('mongoose_category_product', array('id_mongoose_category' => (int)$category->id,'id_mongoose_product' => (int)$product->id)))
						MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$src_line->id.'] Categories And product can t be added.');
					
			}
		}
		$product->id_default_category_supplier = $id_default_category;
		

		//Need to check the variants
		$nvariant = count($src_line->variants->variant);
		if ($nvariant == 1){
			//Only one variant - no attribute
			$product->ean13 = (string)$src_line->variants->variant[0]->ean;
			if((string)$src_line->variants->variant[0]->stock == "Y")
				$product->quantity = 100;
			else
				$product->quantity = 0;
		} 
		elseif ($nvariant > 1)
		{
			//Multi variant
			//Go to check if is there, attribute and if size exist
			$attribute_group = AttributeGroup::getAttributesGroups(Language::getIdByIso('fr'));
			$natttibute_group = count($attribute_group);
			if($natttibute_group > 0)
			{
				for($j = 0; $j < $natttibute_group; $j++)
				{
					if (strtolower($attribute_group[$j]['name']) == 'taille')
						$id_attribute_group = $attribute_group[$j]['id_attribute_group'];
					else
					{
						// Add group attribute
						$new_attribute_group = new AttributeGroup();
						$new_attribute_group->name = array($id_lang_fr => 'Taille');
						$new_attribute_group->is_color_group = false;
						$new_attribute_group->position = AttributeGroup::getHigherPosition() + 1;
						$new_attribute_group->group_type = 'select';
						$new_attribute_group->public_name = array($id_lang_fr => 'Taille');
						$new_attribute_group->add();
						$id_attribute_group = $new_attribute_group->id;
					}
				}
			}
			else
			{
				// Add group attribute
				$new_attribute_group = new AttributeGroup();
				$new_attribute_group->name = array($id_lang_fr => 'Taille');
				$new_attribute_group->is_color_group = false;
				$new_attribute_group->position = AttributeGroup::getHigherPosition() + 1;
				$new_attribute_group->group_type = 'select';
				$new_attribute_group->public_name = array($id_lang_fr => 'Taille');
				if($new_attribute_group->add())
					$id_attribute_group = $new_attribute_group->id;
				else
					MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$product->id_product_supplier.'] Can\'t add attribute_group.');
			}

			$attr = Attribute::getAttributes($id_lang_fr);
			$nattr = count($attr);
			

			for ($i = 0; $i < $nvariant; ++$i)
			{
				if((int)$src_line->variants->variant[$i]->type != 'S')
					MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$product->id_product_supplier.'] attribute group different from Y.');
				else
				{
					$variant_size = (string)$src_line->variants->variant[$i]->title;
					// Si l'attribut existe déjà ok on prend son ID,
					if(Attribute::isAttribute($id_attribute_group, $variant_size, $id_lang_fr))
					{
						
						
						for($k = 0; $k<$nattr;++$k)
						{
							if( ($attr[$k]['id_attribute_group'] == (int)$id_attribute_group) && (strtolower($attr[$k]['name']) == (int)$variant_size)){
								$id_attribute = $attr[$k]['id_attribute'];
							}
						}
					}
					else
					{
						// Si l'attribut n'exsite pas on le crée
						$new_attribute = new Attribute();
						$new_attribute->id_attribute_group = $id_attribute_group;
						if(Configuration::get('PS_LANG_DEFAULT') != (int)$this->src_id_lang)
							$new_attribute->name = array((int)$this->src_id_lang => $variant_size, (int)Configuration::get('PS_LANG_DEFAULT') => $variant_size);
						else
							$new_attribute->name = array((int)$this->src_id_lang => $variant_size);
						$new_attribute->position = Attribute::getHigherPosition($id_attribute_group);
						if ($new_attribute->add())
							$id_attribute = $new_attribute->id;
						else
							MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$product->id_product_supplier.'] Can\'t add attribute.');
					} 
				}

				//On test pr voir si l'attribut existe déjà
				if($product_attribute_id = MongooseProductAttribute::getIdMongooseProductAttributeByIdSupplier((int)$src_line->variants->variant[$i]['id']))
					$product_attribute = new MongooseProductAttribute((int)$src_line->variants->variant[$i]['id']);
				else
					$product_attribute = new MongooseProductAttribute();
				//Mtn on peut rajouter l'ajouter/l'updater
				
			
				$product_attribute->id_product_supplier = (int)$src_line->variants->variant[$i]->id;
				$product_attribute->id_mongoose_product = (int)$product->id;
				$product_attribute->id_ps_attribute = $id_attribute;
				$product_attribute->reference = (string)$src_line->variants->variant[$i]->subartnr;
				$product_attribute->ean13 = (string)$src_line->variants->variant[$i]->ean;

				if((string)$src_line->variants->variant[$i]->stock == "Y")
					$product_attribute->quantity = 100;
				else
					$product_attribute->quantity = 0;

				$product_attribute->save();
			}

		}
		$product->save();
		return $product;
	}
}