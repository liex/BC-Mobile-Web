<?php

class MenuItem {

  public $mealDate;
  public $id;
  public $name;
  public $meal;
  public $hall;
  public $foodType;
  public $servingSize;
  public $servingUnit;
  public $type;

  public function __construct($data) {
    $this->mealDate = strtotime($data[0]);
    $this->id = $data[1];
    $this->name = $data[2];
    $this->meal = $data[3];
    $this->hall = $data[4];
    $this->foodType = $data[5];
    $this->servingSize = $data[6];
    $this->servingUnit = $data[7];
    $this->type = $data[8];
  }

  /* * **
   * Function to convert menu item food type codes into human-readable string
   */
  public function getFoodTypeAsName() {

    $foodTypeName;

    switch ($this->foodType) {

      case "01":
        $foodTypeName = "Breakfast Meats";
        break;
      case "02":
        $foodTypeName = "Breakfast Entrees";
        break;
      case "03":
        $foodTypeName = "Breakfast Bakery";
        break;
      case "04":
        $foodTypeName = "Breakfast Misc";
        break;
      case "05":
        $foodTypeName = "Breakfast Breads";
        break;
      case "06":
        $foodTypeName = "Seasonal";
        break;
      case "07":
        $foodTypeName = "Today's Soup";
        break;
      case "08":
        $foodTypeName = "Made to Order Bar";
        break;
      case "09":
        $foodTypeName = "Brunch";
        break;
      case "10":
        $foodTypeName = "Salad Bar";
        break;
      case "11":
        $foodTypeName = "Sandwich Bar";
        break;
      case "12":
        $foodTypeName = "Entrees";
        break;
      case "13":
        $foodTypeName = "Accompaniments";
        break;
      case "14":
        $foodTypeName = "Starch & Potatoes";
        break;
      case "15":
        $foodTypeName = "Vegetables";
        break;
      case "16":
        $foodTypeName = "Fruit, Fresh, Caned & Frozen";
        break;
      case "17":
        $foodTypeName = "Desserts";
        break;
      case "18":
        $foodTypeName = "Bread, Rolls, Misc Bakery";
        break;
      case "19":
        $foodTypeName = "From the Grille";
        break;
      case "20":
        $foodTypeName = "Bean, Whole Grain";
        break;
      case "21":
        $foodTypeName = "Basic Food Table";
        break;
      case "22":
        $foodTypeName = "Brown Rice Station";
        break;
      case "23":
        $foodTypeName = "Make or Build Your Own";
        break;
      case "24":
        $foodTypeName = "Special Bars - Board Menu";
        break;
      case "25":
        $foodTypeName = "Culinary Display";
        break;
      case "27":
        $foodTypeName = "In Addition at Annenberg";
        break;
      case "28":
        $foodTypeName = "Bag Lunches";
        break;
      case "29":
        $foodTypeName = "Production Salads";
        break;
      case "30":
        $foodTypeName = "A C I";
        break;
      case "31":
        $foodTypeName = "Chef's Choice";
        break;
      case "40":
        $foodTypeName = "Festive Meals";
        break;
      case "41":
        $foodTypeName = "Kosher Table";
        break;
      case "42":
        $foodTypeName = "Fly-By";
        break;
      case "43":
        $foodTypeName = "Continental Breakfast";
        break;
      case "44":
        $foodTypeName = "Vegetarian Station";
        break;
      case "45":
        $foodTypeName = "Pasta a la Carte";
        break;
      case "46":
        $foodTypeName = "Love Your Heart Menu";
        break;
      case "90":
        $foodTypeName = "Brain Break";
        break;
      case "99":
        $foodTypeName = "Misc. Supplies";
        break;
      default:
        $foodTypeName = "Other";
    }
    return $foodTypeName;
  }

  /**
   * Returns an array of values in the original order for writing out as CSV
   */
  public function toArray() {

    $values = array();
    $values[] = date("m/d/Y", $this->mealDate);
    $values[] = $this->id;
    $values[] = $this->name;
    $values[] = $this->meal;
    $values[] = $this->hall;
    $values[] = $this->getFoodTypeAsName();
    $values[] = $this->servingSize;
    $values[] = $this->servingUnit;
    $values[] = $this->type;

    return $values;
  }

  /**
   * Order of meals
   */
  public function getMealOrder() {
    $mealOrder = 0;
    switch ($this->meal) {
      case "BRK":
        $mealOrder = 0;
        break;
      case "LUN":
        $mealOrder = 1;
        break;
      case "DIN":
        $mealOrder = 2;
        break;
      default:
        $mealOrder = 3;
    }

    return $mealOrder;
  }

  /**
   * Comparator function used for sorting
   */
  static function compare($obj1, $obj2) {

    if ($obj1->getMealOrder() != $obj2->getMealOrder()) {
      return ($obj1->getMealOrder() < $obj2->getMealOrder()) ? -1 : 1;
    }

    if ($obj1->foodType != $obj2->foodType) {
      return ($obj1->foodType < $obj2->foodType) ? -1 : 1;
    }

    return strcmp($obj1->name, $obj2->name);
  }
}



