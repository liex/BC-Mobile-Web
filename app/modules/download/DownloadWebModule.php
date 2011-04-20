<?php

class DownloadWebModule extends WebModule {
    protected $id = 'download';
    
    public static function hasApp($platform) {
        $config = ModuleConfigFile::factory('download', 'module');
        return $config->getOptionalVar($platform, null, 'deviceApps');
    }
  
    protected function initializeForPage() {

        $this->assign('deviceName',   Kurogo::getOptionalSiteVar($this->platform, null, 'deviceNames'));
        $this->assign('instructions', $this->getOptionalModuleVar($this->platform, null, 'deviceInstructions', 'module'));
        $this->assign('downloadUrl',  $this->getOptionalModuleVar($this->platform, null, 'deviceApps', 'module'));
    }
}
