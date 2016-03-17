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
		// $this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/js/mongooseimport.js');
	}

	public function renderList()
	{
		$_html = "";

		if (isset($_POST["submitfile"]) && $_POST["submitfile"] == 1)
		{
			$_html .= $this->uploadFile();
		}

		$_html .= $this->panel_upload_file();
		$_html .= $this->panel_list_file();
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
		$fields_list = array(
			'id_mongoose_xml_file' => array(
				'title' => $this->l('Id'),
				'width' => 140,
				'type' => 'text',
			),
			'src_file' => array(
				'title' => $this->l('Filename'),
				'width' => 'auto',
				'type' => 'text',
			),
			'src_line_total' => array(
				'title' => $this->l('Total lines'),
				'width' => 140,
				'type' => 'text'
			),
			'src_current_line' => array(
				'title' => $this->l('Line imported'),
				'width' => 140,
				'type' => 'text',
			),
			'src_id_lang' => array(
				'title' => $this->l('Language'),
				'width' => 140,
				'type' => 'text',
			)
		);
		$helper = new HelperList();
		$helper->shopLinkType = '';
		$helper->simpler_header = false;

		$helper->actions = array('edit', 'delete', 'view');

		$helper->identifier = 'id_mongoose_xml_file';
		$helper->title = $this->l('All xml file');
		$helper->token = $this->context->controller->token;

		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->module->name;

		//$helper->identifier = 'id_mongoose_xml_file';
		//$helper->title = 'Toto';
		//$helper->table = 'ps_mongoose_product';

		//$helper->token = Tools::getAdminTokenLite('AdminModules');
	    //$helper->currentIndex = AdminController::$currentIndex.'&configure='.'titi';
		//d(MongooseXmlFile::getAllFiles());
	    return $helper->generateList(MongooseXmlFile::getAllFiles(), $fields_list);
	}
}