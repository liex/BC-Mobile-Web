<?php
/**
  * @package Module
  * @subpackage Mobile
  */

/**
  * @package Module
  * @subpackage Fullweb
  */
class MobileWebModule extends WebModule {
  protected $id = 'mobile';

  protected function initializeForPage() {

  	 $url = 'http://www.bc.edu/m';
     header("Location: $url");
     die();

     /*
     if ($url = $this->getModuleVar('url')) {
         header("Location: $url");
         die();
     } else {
        throw new Exception("URL not specified");
     }*/
  }
}
