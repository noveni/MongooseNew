<?php
require_once (dirname(__file__) . '/../../classes/MongooseProduct.php');

class AdminMongooseProductsController extends ModuleAdminController
{
	public function __construct()
	{
		$this->context = Context::getContext();
		$this->table = 'mongoose_product';
		$this->className = 'MongooseProduct';
		$this->lang = TRUE;
		$this->bootstrap = true;

		$this->fields_list = array(
			'id_mongoose_product' => array(
				'title' => $this->l('ID'),
				'width' => 45,
				'align' => 'left',
				'type' => 'text'
			),
			'name' => array(
				'title'=> $this->l('Nom'), 
				'width' => '300',
				
			),
		);
		parent::__construct();
	}

	public function renderList()
	{
		$this->addRowAction('edit');
		$this->addRowAction('duplicate');
		$this->addRowAction('delete');
		return parent::renderList();
	}
}