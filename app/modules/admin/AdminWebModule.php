<?php
/**
  * @package Module
  * @subpackage Admin
  */

/**
  * @package Module
  * @subpackage Admin
  */
class AdminWebModule extends WebModule {
    protected $id = 'admin';
  
    private function getNavSections() {
        $navSections = array(
            array(
                'id'=>'site',
                'title'=>'Site Configuration',
                'description'=>'',
                'url'=>$this->buildURL('site',array()),
            ),
            array(
                'id'=>'modules',
                'title'=>'Module Configuration',
                'description'=>'',
                'url'=>$this->buildURL('modules',array()),
            ),
            array(
                'id'=>'credits',
                'title'=>'Credits and Licensing',
                'description'=>'',
                'url'=>$this->buildURL('credits',array()),
            ),
        );
        
        return $navSections;
    }
    
    private function getSiteAdminConfig() {
        static $configData;
        if (!$configData) {
            $file = APP_DIR . "/common/config/admin-site.json";
            if (!$configData = json_decode(file_get_contents($file), true)) {
                throw new Exception("Error parsing $file");
            }
            
        }
        
        return $configData;
    }
    
    private function getSubNavSections($section) {
        $subNavSections = array();
        switch ($section) {
            case 'site':
                $configData = $this->getSiteAdminConfig();
                foreach ($configData as $id=>$data) {
                    $subNavSections[$id] = array(
                        'id'=>$id,
                        'title'=>$data['title'],
                        'url'=>$this->buildURL($section, array('section'=>$id))
                    );
                }
 
                break;
                
            case 'modules':
                $subNavSections['overview'] = array(
                    'id'=>'overview',
                    'title'=>'Modules Overview',
                    'url'=>$this->buildURL($section, array('section'=>'overview'))
                );
                $subNavSections['homescreen'] = array(
                    'id'=>'homescreen',
                    'title'=>'Home Screen Layout',
                    'url'=>$this->buildURL($section, array('section'=>'homescreen'))
                );
                $modules = array();
                foreach ($this->getAllModules() as $module) {
                    $subNavSections[$module->getConfigModule()] = array(
                        'id'=>$module->getConfigModule(),
                        'title'=>$module->getModuleName(),
                        'url'=>$this->buildURL('modules', array('module'=>$module->getConfigModule()))
                    );
                    $modules[$module->getConfigModule()] = array(
                        'id'=>$module->getConfigModule(),
                        'title'=>$module->getModuleName(),
                        'home'=>$module->isOnHomeScreen(),
                        'disabled'=>$module->getModuleVar('disabled'),
                        'protected'=>$module->getModuleVar('protected'),
                        'secure'=>$module->getModuleVar('secure'),
                        'search'=>$module->getModuleVar('search'),
                        'url'=>$this->buildURL('modules', array('module'=>$module->getConfigModule()))
                    );
                    
                }
                $this->assign('modules', $modules);
                break;
        }

        return $subNavSections;
    }

    private function getModules() {
        $moduleNavConfig = $this->getModuleNavigationConfig();
        $modules = array(
            'primary'=>$moduleNavConfig->getOptionalSection('primary_modules'), 
            'secondary'=>$moduleNavConfig->getOptionalSection('secondary_modules'),
            'unused'=>array()
            
        );
        

        $usedModules = array_merge($modules['primary'], $modules['secondary']);
        $allModules = $this->getAllModules();
        $unusedModules = array_diff(array_keys($allModules), array_keys($usedModules));
        
        foreach ($unusedModules as $moduleID) {
            $module = $allModules[$moduleID];
            if ($module->canBeAddedToHomeScreen()) {
                $modules['unused'][$moduleID] = $module->getModuleName();
            }
        }
                
        $imgSuffix = ($this->pagetype == 'tablet' && $selected) ? '-selected' : '';

        foreach ($modules as $type=>&$m) {
            foreach ($m as $id=>$title) {
                $modules[$type][$id] = array(
                    'title'       => $title,
                    'img'         => "/modules/home/images/{$id}{$imgSuffix}".$this->imageExt,
                );
            }
        }
                
        return $modules;
    }

    protected function initialize() {
        $this->requiresAdmin();
    }
  
    protected function initializeForPage() {
        //make sure that only desktop devices can use the module
        if (!$GLOBALS['deviceClassifier']->isComputer() && $this->page !='index') {
            $this->redirectTo('index');
        }

        $navSections = $this->getNavSections();
        $section = '';
        $this->assign('navSections', $navSections);
        $this->addJQuery();
        $this->addJQueryUI();

        switch ($this->page)
        {
            case 'modules':
                $subNavSections = $this->getSubNavSections($this->page);
                $this->assign('subNavSections', $subNavSections);
        
                $defaultSubNavSection = key($subNavSections);
                $section = $this->getArg('section', $defaultSubNavSection);
                $moduleID = $this->getArg('module');
                
                if ($moduleID) {
                    $modulePage = 'module';
                    try {
                        if ($module = WebModule::factory($moduleID)) {
                            $this->assign('moduleName', $module->getModuleName());
                            $this->assign('moduleID', $module->getConfigModule());
                            $section = $moduleID;
                            $moduleSection = $this->getArg('section','general');
                            $this->assign('moduleSection',$moduleSection);
                        }
                    } catch (Exception $e) {
                        $this->redirectTo($this->page, array());
                    }
                
                } elseif ($section == $defaultSubNavSection) {
                    $modulePage = $defaultSubNavSection;
                } elseif ($section == 'homescreen') {
                    $modulePage = $section;
                    
                    $homeModule = WebModule::factory('home');
                    $modules = $this->getModules();
                    $this->assign('modules', $modules);                    
                    
                } else {
                    $this->redirectTo($this->page, array());
                }
                
                $this->assign('modulePage', $modulePage);
                
                break;
            case 'site':            
        
                $subNavSections = $this->getSubNavSections($this->page);
                $this->assign('subNavSections', $subNavSections);
        
                $defaultSubNavSection = key($subNavSections);
                $section = $this->getArg('section', $defaultSubNavSection);
                
                if (!isset($subNavSections[$section])) {
                    $this->redirectTo($this->page, array());
                }
                break;
            case 'credits':
                
                $section = $this->getArg('section', 'credits');
                $subNavSections =  array(
                    'credits'=>array(
                        'id'=>'credits',
                        'title'=>'Credits',
                        'url'=>$this->buildURL($this->page, array('section'=>'credits'))
                    ),
                    'license'=>array(
                        'id'=>'license',
                        'title'=>'License',
                        'url'=>$this->buildURL($this->page, array('section'=>'license'))
                    )
                );
                $this->assign('subNavSections', $subNavSections);
                
                if (isset($subNavSections[$section])) {
                    $this->setTemplatePage($section);
                } else {
                    $this->redirectTo('section', array());
                }
                break;
                
            case 'index':
                //redirect desktop devices to the "default page"
                if ($GLOBALS['deviceClassifier']->isComputer()) {
                    $defaultSection = current($navSections);
                    $this->redirectTo($defaultSection['id'], array());
                }
                break;
            default:
                $this->redirectTo('index', array());
                break;
  
        }  

        $this->assign('section', $section);
  }

}
