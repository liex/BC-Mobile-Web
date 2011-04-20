<?php

class CoreAPIModule extends APIModule
{
    protected $id = 'core';
    protected $vmin = 1;
    protected $vmax = 1;

    // special factory method for core
    public static function factory($command='', $args=array()) {
        $module = new CoreAPIModule();
        $module->init($command, $args);
        return $module;
    }
 
    //always allow access
    protected function getAccessControlLists($type) {
        return array(AccessControlList::allAccess());
    }
    
    public function initializeForCommand() {  
    
        switch ($this->command) {
            case 'hello':
            
                $allmodules = $this->getAllModules();
                foreach ($allmodules as $moduleID=>$module) {
                    if (!$module->getModuleVar('disabled', 'module')) {
                        $modules[] = array(
                            'id'        =>$module->getID(),
                            'tag'       =>$module->getConfigModule(),
                            'title'     =>$module->getModuleVar('title','module'),
                            'access'    =>$module->getAccess(AccessControlList::RULE_TYPE_ACCESS),
                            'vmin'      =>$module->getVmin(),
                            'vmax'      =>$module->getVmax()
                        );
                    }
                }
                $response = array(
                    'version'=>KUROGO_VERSION,
                    'modules'=>$modules
                );
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;
            default:
                $this->invalidCommand();
                break;
        }
    }
}
