<?php
/**
  * @package Module
  * @subpackage Fullweb
  */

/**
  * @package Module
  * @subpackage Fullweb
  */
class FullwebWebModule extends WebModule {
  protected $id = 'fullweb';


  protected function initializeForPage() {
  	 $url = 'http://www.bc.edu';
     header("Location: $url");
     die();
  }
}
