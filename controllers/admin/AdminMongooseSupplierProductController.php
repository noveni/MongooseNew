<?php

require_once (dirname(__FILE__) . '/../../classes/MongooseProduct.php');

class AdminMongooseSupplierProductController extends ModuleAdminController
{
	public function __construct()
	{
		$this->table = 'mongoose_product';
		$this->className = 'MongooseProduct';
		$this->lang = TRUE;
		$this->bootstrap = TRUE;

		$this->fields_list = array(
			'id_mongoose_product' => array(
				'title' => $this->l('ID'),
				'width' => 45,
				'align' => 'left',
				'type' => 'int'
			),
			'image' => array(
				'title' => $this->l('Image'),
				'align' => 'center',
				'orderby' => false,
				'filter' => false,
				'search' => false
			),
			'name' => array(
				'title' => $this->l('Product name'),
				'width' => 'auto',
				'align' => 'left',
				'type' => 'text'
			),
			'do_update' => array(
				'title' => $this->l('Synchroniser avec les produits'),
				'width' => 80,
				'align' => 'center',
				'type' => 'bool',
				'active' => 'do_update',
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

	public function initProcess()
	{
		/* Change object statuts (active, inactive) */
		if ((isset($_GET['do_update'.$this->table]) || isset($_GET['do_update'])) && Tools::getValue($this->identifier))
		{
			if ($this->tabAccess['edit'] === '1')
				$this->action = 'staysynchro';
			else
				$this->errors[] = Tools::displayError('You do not have permission to edit this.');
		}
		return parent::initProcess();
	}

	public function processStaysynchro()
	{
		if (Validate::isLoadedObject($object = $this->loadObject()))
		{
			if ($object->toggleDoUpdate())
			{
				//d($object->reference);
				//We need to erase the product from table
				$real_id_product = (int)Db::getInstance()->getValue('SELECT id_product FROM '._DB_PREFIX_.'product WHERE reference = \''.pSQL($object->reference).'\'');
				$real_product = new Product((int)$real_id_product, true);
				if ($real_product->delete()){

					$matches = array();
					if (preg_match('/[\?|&]controller=([^&]*)/', (string)$_SERVER['HTTP_REFERER'], $matches) !== false
						&& strtolower($matches[1]) != strtolower(preg_replace('/controller/i', '', get_class($this))))
							$this->redirect_after = preg_replace('/[\?|&]conf=([^&]*)/i', '', (string)$_SERVER['HTTP_REFERER']);
					else
						$this->redirect_after = self::$currentIndex.'&token='.$this->token;

					$id_category = (($id_category = (int)Tools::getValue('id_category')) && Tools::getValue('id_product')) ? '&id_category='.$id_category : '';
					$this->redirect_after .= '&conf=5'.$id_category;
				}
			}
			else
				$this->errors[] = Tools::displayError('An error occurred while updating the do_update field.');

		}
		else
			$this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').
				' <b>'.$this->table.'</b> '.
				Tools::displayError('(cannot load object)');
		return $object;
	}

	public function ajaxProcessDoUpdatemongooseProduct()
	{
		$success = false;
		$text = "Erreur lors de la mise à jour de synchro.";
		$this->id = (int)Tools::getValue('id_mongoose_product');
		if (Validate::isLoadedObject($object = $this->loadObject()))
		{
			if($this->processStaysynchro()){
				$success = true;
				$text = 'Statut de synchronisation à jour';
			}
				

		}
		echo Tools::jsonEncode(array(
			'success' => $success,
			'text' => $text,
			'fields_display' => $this->fieldsDisplay,
		));
		die();
	}





}