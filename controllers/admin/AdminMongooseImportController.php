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
		$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/js/mongoose-copyfeed.js');
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

	public function displayAjaxcopyMongooseXmlLine()
	{
		$file = new MongooseXmlFile(Tools::getValue('id_file'));
		$return = array(
			'status' => 'looping_on_xml_file',
			'message' => 'Copying the feed',
			'xml_feed_file' => $file,
			'percent' => number_format(((int)$file->src_current_line / (int)$file->src_line_total) * 100, 2)
		);

		if ((int)$file->id_mongoose_xml_file < $file->src_line_total)
		{
			++$file->src_current_line;
			$return['current_line_in_xml_feed_file'] = $file->src_current_line;
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

	}

	private function copyXmlLineToDb($line)
	{

	}
}