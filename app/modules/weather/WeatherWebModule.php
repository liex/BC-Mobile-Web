<?php
/**
  * @package Module
  * @subpackage Stats
  */

/**
  * @package Module
  * @subpackage Stats
  */
class WeatherWebModule extends WebModule {
    protected $id = 'weather';
    
    protected function initializeForPage() {
        $locations = $this->loadFeedData();
        $items = array();
        
        foreach ($locations as $locationFeed) {
            $controller = DataController::factory($locationFeed['CONTROLLER_CLASS'], $locationFeed);
            if ($data = $controller->getItemByIndex(0)) {
                $items[] = array(
                    'img'=>$data->getImage(),
                    'title'=>$data->getTitle(),
                    'subtitle'=>nl2br($data->getSummary())
                );
            }
        }
        
        $this->assign('results', $items);
    }
    
}
