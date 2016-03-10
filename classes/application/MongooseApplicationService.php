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

}