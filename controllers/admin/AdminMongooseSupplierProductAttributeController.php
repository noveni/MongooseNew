<?php
require_once (dirname(__file__) . '/../../classes/MongooseProductAttribute.php');

class AdminMongooseSupplierProductAttributeController extends ModuleAdminController
{
	public function __construct()
	{
		$this->table = 'mongoose_product_attribute';
		$this->className = 'MongooseProductAttribute';
		$this->lang = false;
		$this->bootstrap = TRUE;

		$this->fields_list = array(
			'id_mongoose_product_attribute' => array(
				'title' => $this->l('ID'),
				'width' => 45,
				'align' => 'left',
				'type' => 'int'
			),
			'id_mongoose_product' => array(
				'title' => $this->l('Id Mongoose Product'),
				'width' => 45,
				'align' => 'left',
				'type' => 'int'
			),
			'id_ps_attribute' => array(
				'title' => $this->l('id prestashop attribute'),
				'width' => 45,
				'align' => 'left',
				'type' => 'int'
			),
			'reference' => array(
				'title' => $this->l('reference'),
				'width' => 'auto',
				'align' => 'left',
				'type' => 'string'
			),
			'ean13' => array(
				'title' => $this->l('EAN13'),
				'width' => 'auto',
				'align' => 'left',
				'type' => 'string'
			),
			'quantity' => array(
				'title' => $this->l('Qty'),
				'width' => 50,
				'align' => 'left',
				'type' => 'int'
			)
		);
		parent::__construct();
	}

	public function renderList()
	{

		$this->addRowAction('details');
		$this->addRowAction('edit');
		$this->addRowAction('delete');
		return parent::renderList();
	}
}