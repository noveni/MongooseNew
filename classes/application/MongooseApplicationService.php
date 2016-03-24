<?php

class MongooseApplicationService {

	public static function log($action, $value = '')
	{
		$logFile = MongooseApplicationService::getLogDir().'log-'.date('Y-m-d').'.txt';

		if (!file_exists($logFile)){
			$fp = fopen($logFile, 'w+');
			fwrite($fp, '');
			fclose($fp);
		}

		$string = '['.date('Y-m-d H:i:s').'] ['.$action.'] '.(!empty($value) ? ': '.$value : '') . "\r\n";
		file_put_contents($logFile, $string, FILE_APPEND);
	}

	public static function getLogDir()
	{
		return dirname(__FILE__).'/../../log/';
	}

	public static function createTab($id_parent, $module, $name, $class_name)
	{
		$Tab = new Tab();
		$Tab->module = $module;
		foreach (Language::getLanguages(true) as $languages)
			$Tab->name[$languages["id_lang"]] = $name;

		$Tab->id_parent = $id_parent;
		$Tab->class_name = $class_name;
		$r = $Tab->save();

		if ($r == false)
			return false;

		return $Tab->id;
	}

	public static function uninstallModuleTab($tabClass)
	{
		$idTab = Tab::getIdFromClassName($tabClass);
		if ($idTab != 0)
		{
			$tab = new Tab($idTab);
			$tab->delete();
			return true;
		}
		return false;
	}

	public static function installAllTabs($id_parent, $module, $list_tab)
	{
		$return = true;
		$nlist_tab = count($list_tab);
		for ($i = 0; $i < $nlist_tab; ++$i)
		{
			if($list_tab[$i]['active'] != 0)
			{
				$idTab = Tab::getIdFromClassName($list_tab[$i]['class_name']);
				if ($idTab == 0) // Si l'onglet n'existe pas déjà on le crée
				{
					if(!self::createTab($id_parent, $module, $list_tab[$i]['name'], $list_tab[$i]['class_name']))
						$return = false;
				}
			}
		}
		return $return;
	}

	public static function uninstallAllTabs($list_tab)
	{
		$return = true;
		$nlist_tab = count($list_tab);
		for ($i = 0; $i < $nlist_tab; ++$i)
		{
			self::uninstallModuleTab($list_tab[$i]['class_name']);
		}
	}

	public static function uploadXMLFile($file,$path,$final_filename)
	{
		if (isset($file) && !empty($file['error']))
		{
			switch ($file['error']) 
			{
				case UPLOAD_ERR_INI_SIZE:
					$file['error'] = Tools::displayError('The uploaded file exceeds the upload_max_filesize directive in php.ini. If your server configuration allows it, you may add a directive in your .htaccess.');
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$file['error'] = Tools::displayError('The uploaded file exceeds the post_max_size directive in php.ini.
						If your server configuration allows it, you may add a directive in your .htaccess, for example:')
					.'<br/><a href="'.$this->context->link->getAdminLink('AdminMeta').'" >
					<code>php_value post_max_size 20M</code> '.
					Tools::displayError('(click to open "Generators" page)').'</a>';
					break;
				break;
				case UPLOAD_ERR_PARTIAL:
					$file['error'] = Tools::displayError('The uploaded file was only partially uploaded.');
					break;
				break;
				case UPLOAD_ERR_NO_FILE:
					$file['error'] = Tools::displayError('No file was uploaded.');
					break;
				break;
			}
		}
		elseif (!preg_match('/.*\.xml$/i', $file['name']))
			$file['error'] = Tools::displayError('The extension of your file should be .xml.');
		elseif (!@filemtime($file['tmp_name']) || 
			!move_uploaded_file($file['tmp_name'], $path.str_replace("\0", '', $final_filename)))
			$file['error'] = $this->l('An error occurred while uploading / copying the file.');
		else
		{
			@chmod($path.$final_filename, 0664);
			$file['filename'] = str_replace('\0', '', $final_filename);
		}

		return $file;
	}

	public static function importImage($product,$base_url = false,$deleteImage = false)
	{
		if (!$base_url)
			$base_url = 'http://cdn.edc-internet.nl/500/';

		if (isset($product->image) && is_array($product->image) && count($product->image))
		{
			if ($deleteImage)
			{
				$img_array_to_del = Image::getImages((int)Language::getIdByIso('fr'), $product->id);
				$count_img = count($img_array_to_del);
				for ($img_i=0; $img_i<$count_img;++$img_i)
				{
					$img_to_del = new Image($img_array_to_del[$img_i]['id_image']);
					$img_to_del->delete();
				}
			}

			foreach ($product->image as $key => $filename)
			{
				$url = $base_url.$filename;
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

	

}