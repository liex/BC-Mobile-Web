<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

class BCDining {
  public static $meals = array(
    "breakfast" => array("days" => "Monday,Tuesday,Wednesday,Thursday,Friday,Saturday"),
    "brunch"    => array("days" => "Sunday"),
    "lunch"     => array("days" => "Monday,Tuesday,Wednesday,Thursday,Friday,Saturday"),
    "dinner"    => array(),
    "bb"        => array("name" => "brain break", "days" => "Sunday,Monday,Tuesday,Wednesday,Thursday"),
  );

  public static function mealName($meal) {
    if(isset(self::$meals[$meal]['name'])) {
      return self::$meals[$meal]['name'];
    } else {
      return $meal;
    }
  }

  public function getMealData($baseUrl, $dateToday, $mealExtension) {
    $urlLink = $baseUrl.$dateToday.$mealExtension;
    $contents = file_get_contents($urlLink);

    return $contents;
  }
}

