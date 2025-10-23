<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class custom_core extends CModule
{
    public function __construct()
    {
        $arModuleVersion = array();
        
        include __DIR__ . '/version.php';
        $this->exclusionAdminFiles = array(
            "..",
            ".",
            "menu.php",
            "custom_core_index.php",
        );

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }
        
        $this->MODULE_ID = 'custom.core';
        $this->MODULE_NAME = Loc::getMessage('CUSTOM_CORE_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('CUSTOM_CORE_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('CUSTOM_CORE_MODULE_PARTNER_NAME');
        $this->PARTNER_URI = 'http://bitrix.expert';
        $this->MODULE_SORT = 1;
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = "Y";
        $this->MODULE_GROUP_RIGHTS = "Y";
    }
    public function GetPath($notDocumentRoot=false){
        if($notDocumentRoot){
            return str_ireplace(Application::getDocumentRoot(),'',dirname(__DIR__));
        }else{
            return dirname(__DIR__);
        }
    }
    public function doInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
        $this->InstallFiles();
		$this->registerAgents();
        return true;
    }

    public function doUninstall()
    {
        $this->uninstallDB();
        $this->UninstallFiles();
	    CAgent::RemoveModuleAgents($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }

	public function registerAgents() {
		
		if (!CAgent::GetList([], ['NAME' => '\\Custom\\Core\\Agents::cleanupFileSemaphoresAgent();'])->Fetch()) {
			$agentTimeOutSec = 10;
			$t = DateTime::createFromTimestamp(time() + $agentTimeOutSec);
			
			CAgent::AddAgent(
				'\\Custom\\Core\\Agents::cleanupFileSemaphoresAgent();',
				$this->MODULE_ID,
				'N',                            // агент не критический
				3600,                          // интервал в секундах (1 час)
				'',                            // дата первого запуска (сейчас)
				'Y',                           // агент активен
				$t->toString(),                            // дата следующего запуска (оставить пустым)
				100                            // сортировка
			);
		}
	}
	
    public function installDB()
    {
        return true;
    }

    public function uninstallDB()
    {
        Option::delete($this->MODULE_ID);
        return true;
    }
    public function InstallFiles()
    {
        if(Directory::isDirectoryExists($path = $this->GetPath().'/admin')){
            if($dir = opendir($path)){
                while (false !== $item = readdir($dir)){
                    if(in_array($item,$this->exclusionAdminFiles))
                        continue;
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$this->MODULE_ID.'_'.$item,
                                      '<'.'? require($_SERVER["DOCUMENT_ROOT"]."'.$this->GetPath(true).'/admin/'.$item.'");?'.'>');
                }
                closedir($dir);
            }
        }
        //CopyDirFiles($this->GetPath()."/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");

        return true;
    }

    public function UninstallFiles()
    {
        if(Directory::isDirectoryExists($path = $this->GetPath().'/admin')){
            if($dir = opendir($path)){
                while (false !== $item = readdir($dir)){
                    if(in_array($item,$this->exclusionAdminFiles))
                        continue;
                    File::deleteFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$this->MODULE_ID.'_'.$item);
                }
                closedir($dir);
            }
        }
        return true;
    }
}
