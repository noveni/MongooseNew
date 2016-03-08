<?php

class ApplicationService {

	public static function log($action, $value = '')
	{
		$logFile = ApplicationService::getLogDir().'log-'.date('Y-m-d').'.txt';

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

}