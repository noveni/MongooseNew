<?php

require_once (dirname(__file__) . '/../../classes/application/MongooseApplicationService.php');
require_once (dirname(__file__) . '/../../classes/MongooseProduct.php');
require_once (dirname(__file__) . '/../../classes/MongooseCategory.php');
require_once (dirname(__file__) . '/../../classes/MongooseManufacturer.php');
require_once (dirname(__file__) . '/../../classes/MongooseProductAttribute.php');
require_once (dirname(__file__) . '/../../classes/MongooseXmlFile.php');

class AdminMongooseImportController extends ModuleAdminController
{
	private $importer_xml_errors = array();
	private $importer_mg_prod_errors = array();

	public function __construct()
	{
		parent::__construct();
		$this->bootstrap = true;
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/js/mongoose-copyfeedv10.js');
	}

	public function renderList()
	{
		$this->tmp_deleteAllEmptyGroupAttribute();
		// $path = _PS_MODULE_DIR_.'mongoose'.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR;
		// $xml_content = simplexml_load_file($path.'feed_fr.xml');
		// $this->copyXmlLineToDb($xml_content->product[2],1);
		// d('bye');
		// $this->importMongooseProductToPSDb();
		// d('bye');
		$_html = "";

		if (isset($_POST["submitfile"]) && $_POST["submitfile"] == 1)
		{
			$_html .= $this->uploadFile();
		}

		$_html .= $this->panel_upload_file();
		$_html .= $this->panel_list_file();
		$_html .= $this->panel_import_to_product();
		return $_html.parent::renderList();
	}

	public function uploadFile()
	{
		$_html = "";
		$file_id_lang = Tools::getValue('xml_lang');
		$Language = Language::getLanguage($file_id_lang);
		$iso_code = $Language['iso_code'];
		$path = _PS_MODULE_DIR_.'mongoose'.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR;
		$filename = 'feed_'.$iso_code.'.xml';

		$upload_result = MongooseApplicationService::uploadXMLFile($_FILES['file'],$path,$filename);
		if (isset($upload_result['error']) && !empty($upload_result['error']))
			$_html .= $this->module->displayError($upload_result['error']);
		else
		{
			$_html .= $this->module->displayConfirmation($upload_result['filename']);
			if($id_xml_file = MongooseXmlFile::getIdByIdLang($file_id_lang))
				$xml_file = new MongooseXmlFile($id_xml_file);
			else
				$xml_file = new MongooseXmlFile();

			$xml_file->src_file = $filename;
			$xml_content = simplexml_load_file($path.$xml_file->src_file);
			$xml_file->src_line_total = count($xml_content);
			$xml_file->src_current_line = 0;
			$xml_file->src_id_lang = (int)$file_id_lang;
			$xml_file->save();
		}
		return $_html;
	}

	public function panel_upload_file()
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
				'title' => $this->l(' Ajouter un feed xml'),
				'icon' => 'icon-cloud-download',
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
		$helper->title = $this->l('Envoyer un feed xml de produit');
		$helper->submit_action = 'submitfile';
		$helper->fields_value['xml_lang'] = '';
		return $helper->generateForm($fields_form);
	}

	public function panel_list_file()
	{
		$files = MongooseXmlFile::getAllFiles();
		
		$nfiles = count($files);
		for ($i = 0; $i < $nfiles; ++$i)
		{
			$files[$i]['src_lang_iso'] = Language::getIsoById((int)$files[$i]['src_id_lang']);
			$files[$i]['percent'] = number_format(((int)$files[$i]['src_current_line'] / (int)$files[$i]['src_line_total']) * 100,2);
		}
		$this->context->smarty->assign(array(
			'files' => $files,
			'count_files' => $nfiles,
			'module_link' => $this->context->link->getAdminLink('AdminMongooseImport',true),
		));
		return $this->context->smarty->fetch(_PS_MODULE_DIR_.'mongoose/views/templates/admin/panel_list_file.tpl');
	}

	public function panel_import_to_product()
	{
		$line_count = MongooseProduct::count();
		$current_line_product = (int)Configuration::get('MONGOOSE_CURRENT_PRODUCT_LINE');
		$this->context->smarty->assign(array(
			'count_row' => $line_count,
			'current_line_product' => $current_line_product,
			'percent' => $line_count==0 ? 0 : number_format(($current_line_product / $line_count) * 100,2),
			'module_link' => $this->context->link->getAdminLink('AdminMongooseImport',true)

		));
		return $this->context->smarty->fetch(_PS_MODULE_DIR_.'mongoose/views/templates/admin/panel_import_to_product.tpl');
	}

	public function displayAjaxcopyMongooseXmlLine()
	{
		$file = new MongooseXmlFile(Tools::getValue('id_file'));
		$return = array(
			'status' => 'looping_on_xml_file',
			'message' => 'Copying the feed',
			'xml_feed_file' => $file,
			'percent' => number_format(((int)$file->src_current_line / (int)$file->src_line_total) * 100, 2)
		);

		if ((int)$file->src_current_line < $file->src_line_total)
		{
			$path = _PS_MODULE_DIR_.'mongoose'.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR;
			$xml_content = simplexml_load_file($path.$file->src_file);
			if ($line_imported = $this->copyXmlLineToDb($xml_content->product[(int)$file->src_current_line],$file->src_id_lang))
			{
				++$file->src_current_line;
				$return['product'] = $line_imported;
				$return['current_line_in_xml_feed_file'] = $file->src_current_line;
				
			}
		}
		else
		{
			$return = array_merge($return, array('message' => 'On est arrive à la fin du fichier', 'status' => 'end_file'));
		}
		$file->save();
		die(Tools::jsonEncode($return));
	}

	public function displayAjaxresetMongooseXmlCurrentLine()
	{
		$file = new MongooseXmlFile(Tools::getValue('id_file'));
		$return = array(
			'status' => 'reset_xml_current_line',
			'message' => 'An error occured while reseting the \'reset_xml_current_line\' to 0',
			'xml_feed_file' => $file
		);
		$file->src_current_line = 0;

		if($file->save())
			$return['message'] = 'The reseting the value of \'reset_xml_current_line\' to 0 has been a success';

		die(Tools::jsonEncode($return));
	}

	public function displayAjaximportMongooseProduct()
	{
		$total_row = MongooseProduct::count();
		$current_line_product = (int)Configuration::get('MONGOOSE_CURRENT_PRODUCT_LINE');

		$return = array(
			'message' => 'transfering mongoose_product to product',
			'status' => 'looping_on_db_table',
			'current_mongoose_product_line' => $current_line_product,
			'percent' => number_format(((int)$current_line_product / (int)$total_row) * 100, 2)
		);
		if((int)$current_line_product < (int)$total_row){
			// transfer
			if($line_transfered = $this->importMongooseProductToPSDb()){
				++$current_line_product;
				Configuration::updateValue('MONGOOSE_CURRENT_PRODUCT_LINE',(int)$current_line_product);
				$return['product'] = $line_transfered;
				$return['current_mongoose_product_line'] = $current_line_product;
			}
			//$return = array_merge($return,$this->loopOnProducts());
		}
		else
			$return = array_merge($return, array('message' => 'Fin des produits', 'status' => 'loop_end'));
		die(json_encode($return));
	}

	private function copyXmlLineToDb($line,$src_id_lang)
	{
		$id_lang_fr = (int)Language::getIdByIso('fr');
		// In case of $line->title empty
		if (empty($line->title))
			$line->title = (string)$line->artnr;

		// Add product
		if ($id_mongoose_product = MongooseProduct::getIdMongooseProductByIdSupplier((int)$line->id))
		{
			$product = new MongooseProduct($id_mongoose_product);
			if(empty($product->name[Configuration::get('PS_LANG_DEFAULT')]))
				$product->name[Configuration::get('PS_LANG_DEFAULT')] = (string)$line->title;
			$product->name[(int)$src_id_lang] = (string)$line->title;

			if(empty($product->link_rewrite[Configuration::get('PS_LANG_DEFAULT')]))
				$product->link_rewrite[Configuration::get('PS_LANG_DEFAULT')] = (string)$line->title;
			$product->link_rewrite[(int)$src_id_lang] = Tools::link_rewrite((string)$line->title);
		}
		else
		{
			$product = new MongooseProduct();
			$product->id_product_supplier = (int)$line->id;
			// If it's a new product without and if the default language is not the same as the actual lang
			if(Configuration::get('PS_LANG_DEFAULT') != (int)$src_id_lang){
				$product->name = array((int)$src_id_lang => (string)$line->title,
										Configuration::get('PS_LANG_DEFAULT') => (string)$line->title);
				$product->link_rewrite = array((int)$src_id_lang => Tools::link_rewrite((string)$line->title),
										Configuration::get('PS_LANG_DEFAULT') => Tools::link_rewrite((string)$line->title) );
			} else {
				$product->name = array((int)$src_id_lang => (string)$line->title);
				$product->link_rewrite = array((int)$src_id_lang => Tools::link_rewrite((string)$line->title));
			}
		}
		$product->reference = (string)$line->artnr;
		$product->description[$src_id_lang] = (string)$line->description;
		//$product->date_add = (string)$line->date;
		//$product->date_upd = (string)$line->modifydate;
		$product->price = (float)number_format($line->price->b2c / (1 + 21 / 100), 6, '.', '');
		$product->wholesale_price = (float)number_format((float)$line->price->b2b, 6, '.', '');
		if (isset($line->measures->packing) && !empty($line->measures->packing)){
			$packing = explode("x",(string)$line->measures->packing);
			$npacking = count($packing);
			for($n=0;$n<$npacking;++$n)
				$packing[$n] = (float)$packing[$n];
			list($product->depth,$product->width,$product->height) = $packing;
		}
		if (isset($line->measures->weight) && !empty($line->measures->weight))
			$product->weight = (float)$line->measures->weight;
		$product->supplier = 'edc';
		$product->id_ps_supplier = Supplier::getIdByName('EDC');
		$product->pics_list = serialize((array)$line->pics->pic);;

		//Adding manufacturer
		if($id_mongoose_manufacturer = MongooseManufacturer::getIdMongooseSupplierByIdSupplier($line->brand->id))
		{
			$manufacturer = new MongooseManufacturer($id_mongoose_manufacturer,(int)$src_id_lang);
		} 
		else 
		{
			$manufacturer = new MongooseManufacturer();
		}
		$manufacturer->id_manufacturer_supplier = (int)$line->brand->id;
		if(Configuration::get('PS_LANG_DEFAULT') != (int)$src_id_lang)
		{
			 $manufacturer_title = array((int)$src_id_lang => (string)$line->brand->title,
										Configuration::get('PS_LANG_DEFAULT') => (string)$line->brand->title);
		}
		else
		{
			$manufacturer_title = array((int)$src_id_lang => (string)$line->brand->title);
		}
		$manufacturer->title = $manufacturer_title;
		if(!$manufacturer->save())
			MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$line->id.'] Manufacturer can t be updated.');

		$product->id_manufacturer_supplier = $manufacturer->id;

		if(!$product->save())
			MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$line->id.'] Product can t be saved.');

		// Now i've got the id product
		// I can do the rest of the install
		//Adding category
		$ncategory = count($line->categories->category);
		for($l = 0;$l < $ncategory; ++$l)
		{
			$nundercat = count($line->categories->category[$l]->cat);
			for($m = 0;$m < $nundercat; ++$m)
			{
				if($m == 0)
					$id_parent = 0;
				else
					$id_parent = MongooseCategory::getIdMongooseCategoryByIdSupplier((int)$line->categories->category[$l]->cat[$m-1]->id);
				//p($line->categories->category[$l]->cat[$m]);
				//Tester si la categories existe déjà
				if ($id_mongoose_category = MongooseCategory::getIdMongooseCategoryByIdSupplier((int)$line->categories->category[$l]->cat[$m]->id))
				{
					$category = new MongooseCategory($id_mongoose_category);
					$category->title[(int)$src_id_lang] = (string)$line->categories->category[$l]->cat[$m]->title;
					//p($category);
					//$category->title = array((int)$src_id_lang => (string)$line->categories->category[$l]->cat[$m]->title);
				}
				else
				{
					$category = new MongooseCategory();
					$category->id_category_supplier = (int)$line->categories->category[$l]->cat[$m]->id;
					if(Configuration::get('PS_LANG_DEFAULT') != (int)$src_id_lang)
					{
						$category->title = array((int)$src_id_lang => (string)$line->categories->category[$l]->cat[$m]->title,
													(int)Configuration::get('PS_LANG_DEFAULT') => (string)$line->categories->category[$l]->cat[$m]->title);
					}
					else 
						$category->title = array((int)$src_id_lang => (string)$line->categories->category[$l]->cat[$m]->title);
				}
				$category->id_category_parent = (int)$id_parent;
				if(!$category->save())
					MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$line->id.'] Categories '.$category->title.' can t be added.');
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
						MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$line->id.'] Categories And product can t be added.');
			}
		}
		$product->id_default_category_supplier = $id_default_category;

		//Need to check the variants
		$nvariant = count($line->variants->variant);
		if ($nvariant == 1){
			//Only one variant - no attribute
			$product->ean13 = (string)$line->variants->variant[0]->ean;
			if((string)$line->variants->variant[0]->stock == "Y")
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
				// we loop on the attribute group to check if taille exist, if he exist we catch his id_attribute_group
				for($j = 0; $j < $natttibute_group; $j++)
				{
					if (strtolower($attribute_group[$j]['name']) == 'taille')
						$id_attribute_group = $attribute_group[$j]['id_attribute_group'];
				}
			}

			// if we don't have id_attribute_group, we go creat this crap
			if (!isset($id_attribute_group))
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
				if((int)$line->variants->variant[$i]->type != 'S')
					MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$product->id_product_supplier.'] attribute group different from Y.');
				else
				{
					$variant_size = strtolower((string)$line->variants->variant[$i]->title);
					// Si l'attribut existe déjà ok on prend son ID,
					if(Attribute::isAttribute($id_attribute_group, $variant_size, $id_lang_fr))
					{
						for($k = 0; $k<$nattr;++$k)
						{
							if( ($attr[$k]['id_attribute_group'] == (int)$id_attribute_group) && (strtolower($attr[$k]['name']) == (string)$variant_size)){
								$id_attribute = $attr[$k]['id_attribute'];
							}
						}
					}
					else
					{
						// Si l'attribut n'exsite pas on le crée
						$new_attribute = new Attribute();
						$new_attribute->id_attribute_group = $id_attribute_group;
						if(Configuration::get('PS_LANG_DEFAULT') != (int)$src_id_lang)
							$new_attribute->name = array((int)$src_id_lang => $variant_size, (int)Configuration::get('PS_LANG_DEFAULT') => $variant_size);
						else
							$new_attribute->name = array((int)$src_id_lang => $variant_size);
						$new_attribute->position = Attribute::getHigherPosition($id_attribute_group);
						if ($new_attribute->add())
							$id_attribute = $new_attribute->id;
						else
							MongooseApplicationService::log('Add a line in intermediaire table','[pid:'.(int)$product->id_product_supplier.'] Can\'t add attribute.');
					} 
				}

				//On test pr voir si l'attribut existe déjà
				if($product_attribute_id = MongooseProductAttribute::getIdMongooseProductAttributeByIdSupplier((int)$line->variants->variant[$i]->id))
				{
					$product_attribute = new MongooseProductAttribute((int)$product_attribute_id);
				}
				else
				{
					$product_attribute = new MongooseProductAttribute();
				}
				//Mtn on peut rajouter l'ajouter/l'updater
				$product_attribute->id_product_supplier = (int)$line->variants->variant[$i]->id;
				$product_attribute->id_mongoose_product = (int)$product->id;
				$product_attribute->id_ps_attribute = $id_attribute;
				$product_attribute->reference = (string)$line->variants->variant[$i]->subartnr;
				$product_attribute->ean13 = (string)$line->variants->variant[$i]->ean;

				if((string)$line->variants->variant[$i]->stock == "Y")
					$product_attribute->quantity = 100;
				else
					$product_attribute->quantity = 0;

				$product_attribute->save();
			}
		}
		// p($line);
		$product->save();
		return $product;
	}

	private function importMongooseProductToPSDb()
	{
		$current_line_product = (int)Configuration::get('MONGOOSE_CURRENT_PRODUCT_LINE');
		//$current_line_product = 2;
		$id_lang_fr = (int)Language::getIdByIso('fr');
		$sql = 'SELECT `id_mongoose_product` FROM `'. _DB_PREFIX_ . 'mongoose_product` ORDER BY `id_mongoose_product` LIMIT '.$current_line_product.',1';
		if ($results = Db::getInstance()->ExecuteS($sql)){
			$id_mongoose_product = $results[0]['id_mongoose_product'];
			$mongoose_product = new MongooseProduct($id_mongoose_product);
			if (!$mongoose_product->do_update)
				return 'do_update is false';
			$id_product = (int)Db::getInstance()->getValue('SELECT id_product FROM '._DB_PREFIX_.'product WHERE reference = \''.pSQL($mongoose_product->reference).'\'');
			if ($id_product)
				$return['maj'] = 'Mise à jour du produit';
			else
				$return['add'] = 'Rajout du produit';

			$product = $id_product ? new Product((int)$id_product, true) : new Product();
			// $product->reference = $mongoose_product->reference;
			$product->price = (float)$mongoose_product->price;
			$product->wholesale_price = (float)$mongoose_product->wholesale_price;
			$product->id_supplier = (float)$mongoose_product->id_ps_supplier;
			$product->supplier_reference = $mongoose_product->reference;
			$product->width = $mongoose_product->width;
			$product->height = $mongoose_product->height;
			$product->depth = $mongoose_product->depth;
			$product->weight = $mongoose_product->weight;


			if($product->active)
				$product->active = 1;
			else
				$product->active = 0;
			// :TODO: change to be correct
			$product->id_category_default = 2;

			$product->name = $mongoose_product->name;
			$product->description = $mongoose_product->description;
			$product->link_rewrite = $mongoose_product->link_rewrite;

			if (!isset($product->date_add) || empty($product->date_add))
				$product->date_add = date('Y-m-d H:i:s');
			$product->date_upd = date('Y-m-d H:i:s');
			

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
				//$id_product ? $product->updateCategories(array(2)) : $product->addToCategories(array(2));
				
			}

			if(!$product->save())
				MongooseApplicationService::log('Add product in ps','[pid:'.(int)$mongoose_product->id_mongoose_product.'] Cannot save product.');
				
			// Attribute
			$mongoose_product_attribute = MongooseProductAttribute::getByIdMgProduct($mongoose_product->id);
			if(!$mongoose_product_attribute)
			{
				// if($id_product)
				// 	StockAvailable::setQuantity($product->id,null,(int)$mongoose_product->quantity)
				// else
				$product->deleteProductAttributes();
				StockAvailable::setQuantity($product->id, null, (int)$mongoose_product->quantity);

			}
			else
			{
				$nmg_product_attribute = count($mongoose_product_attribute);
				//p($attribute_combination = $product->getAttributeCombinations($id_lang_fr));
				for($j = 0; $j<$nmg_product_attribute; ++$j)
				{
					$upd_prod = false;
					$attribute_combination = $product->getAttributeCombinations($id_lang_fr);
					$nattribute_combination = count($attribute_combination);
					for($k = 0;$k<$nattribute_combination; ++$k)
					{
						if($attribute_combination[$k]['id_attribute'] == $mongoose_product_attribute[$j]['id_ps_attribute'] && 
							$attribute_combination[$k]['reference'] == $mongoose_product_attribute[$j]['reference'])
						{
							$product->updateAttribute($attribute_combination[$k]['id_product_attribute'], 0, 0, 0, 0, 0,
								0, $mongoose_product_attribute[$j]['reference'], $mongoose_product_attribute[$j]['ean13'],true);
							$upd_prod = true;
							$product->addSupplierReference($id_supplier = Supplier::getIdByName('EDC'),$attribute_combination[$k]['id_product_attribute'],$mongoose_product_attribute[$j]['reference']);
							StockAvailable::setQuantity($product->id, $attribute_object, 100);
							//updateQuantity
						}
					}
					
					if(!$upd_prod){
						$attribute_object = $product->addAttribute(0,0,0,0,0,$mongoose_product_attribute[$j]['reference'],$mongoose_product_attribute[$j]['ean13'],
								true);
						$product->addSupplierReference($id_supplier = Supplier::getIdByName('EDC'),$attribute_object,$mongoose_product_attribute[$j]['reference']);
						$product->addAttributeCombinaison((int)$attribute_object,array($mongoose_product_attribute[$j]['id_ps_attribute']));
						StockAvailable::setQuantity($product->id, $attribute_object, 100);

					}
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
			$product->image = unserialize($mongoose_product->pics_list);
			$base_url = 'http://cdn.edc-internet.nl/500/';
			if ($id_product)
				$deleteImage = true;
			else
				$deleteImage = false;
			MongooseApplicationService::importImage($product,$base_url,$deleteImage);
			/*if (isset($product->image) && is_array($product->image) && count($product->image))
			{
				if ($id_product){
					$arr_image_to_del = Image::getImages($id_lang_fr, $product->id);
					$nimage_to_del = count($arr_image_to_del);
					for ($img_i=0; $img_i<$nimage_to_del;++$img_i)
					{
						$img_to_del = new Image($arr_image_to_del[$img_i]['id_image']);
						$img_to_del->delete();
					}
				}
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
			}*/
		}
		$return['id_product'] = $product->id;
		$return['product'] = $product;
		
		return $return;
	}

	private function tmp_deleteAllEmptyGroupAttribute()
	{
		$units = new PrestaShopCollection('MongooseProduct');
		//$units->where('id_mongoose_product','=','1');
		d($units->getAll());
	}
}