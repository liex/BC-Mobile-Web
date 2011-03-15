<?php

class TransitPath {
  private $id = null;
  private $points = array();
  
  function __construct($id, $points) {
    $this->id = $id;
    
    $pathPoints = array();
    foreach ($points as &$point) {
      $pathPoints[] = array(
        'lat' => floatVal(reset($point)),
        'lon' => floatVal(end($point)),
      );
    }
    $this->points = $pathPoints;
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getPoints() {
    return $this->points;
  }
}
