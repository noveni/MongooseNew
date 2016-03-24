<?php
require_once (dirname(__file__) . '/../../classes/MongooseCategory.php');

class AdminMongooseSupplierCategoryController extends ModuleAdminController
{
	public function __construct()
	{
		$this->table = 'mongoose_category';
		$this->className = 'MongooseCategory';
		$this->lang = TRUE;
		$this->bootstrap = TRUE;

		$this->fields_list = array(
			'id_mongoose_category' => array(
				'title' => $this->l('ID'),
				'width' => 45,
				'align' => 'left',
				'type' => 'int'
			),
			'id_category_parent' => array(
				'title' => $this->l('Id parent category'),
				'width' => 45,
				'align' => 'left',
				'type' => 'int'
			),
			'title' => array(
				'title' => $this->l('Category name'),
				'width' => 'auto',
				'align' => 'left',
				'type' => 'text'
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