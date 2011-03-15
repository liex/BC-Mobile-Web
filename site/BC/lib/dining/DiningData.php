<?php
class DiningData {

  public static function createDiningFlatFile($local_file) {

    $handle1 = fopen($local_file, 'w');
    $contents = file_get_contents(DINING_MENU_RAW_FILE);
    fwrite($handle1, $contents);
    fclose($handle1);

    $handle = fopen($local_file, "r");

    $menus = array();
    while (($data = fgetcsv($handle)) !== FALSE) {
      $menuItem = new MenuItem($data);
      $menu_key = $menuItem->mealDate;

      if (!array_key_exists($menu_key, $menus)) {
        $menus[$menu_key] = array();
      }

      $menus[$menu_key][] = $menuItem;
    }

    fclose($handle);

    /*
    * Write out a file for each date in appropriate sorted order.
    */
    if (!file_exists(DINING_MENU_DIRECTORY)) {
      if (!mkdir(DINING_MENU_DIRECTORY, 0755, true)) {
        error_log("could not create $path");
      }
    }

    foreach ($menus as $menuDate => $menuItemList) {
      // Format as YYYY-MM-DD for file name
      $filename = DINING_MENU_DIRECTORY .date("Y-m-d", $menuDate).".csv";
      $handle = fopen($filename, "w");
      usort($menuItemList, array("MenuItem", "compare"));

      if ($handle !== FALSE) {
        foreach ($menuItemList as $menuItem) {
          fputcsv($handle, $menuItem->toArray());
        }

        fclose($handle);
      }
    }
  }


  public static function getDiningData($date, $mealTime, $categorize=true) {

    $menu = array();
    $day = $date;
    $filename = DINING_MENU_DIRECTORY."$day.csv";

    self::createDiningFlatFile(DINING_MENU_FLAT_FILE);

    if (file_exists($filename)) {
      $handle = fopen($filename, "r");

      while (($data = fgetcsv($handle)) !== FALSE) {
        $menuItem = new MenuItem($data);

        $menuItemArray = array();
        $menuItemArray['item'] = $menuItem->name;
        $menuItemArray['meal'] = $menuItem->meal;
        $menuItemArray['date'] = date('Y-m-d',$menuItem->mealDate);
        $menuItemArray['id'] = $menuItem->id;
        $menuItemArray['category'] = $menuItem->foodType;
        $menuItemArray['servingSize'] = $menuItem->servingSize;
        $menuItemArray['servingUnit'] = $menuItem->servingUnit;
        $menuItemArray['type'] = $menuItem->type;

        if ($mealTime == $menuItem->meal)
            $menu[] = $menuItemArray;
      }
    }

    if ($categorize) {
      return self::collectFoodByCategory($menu);
    } else {
      return $menu;
    }
  }

  private static function collectFoodByCategory($items) {
    $foodCategories = array();

    foreach($items as $item) {
      if(!array_key_exists($item['category'], $foodCategories)) {
        $foodCategories[$item['category']] = array($item);
      } else {
        $foodCategories[$item['category']][] = $item;
      }
    }

    // reorder food categories by priority
    $orderedFoodCategories = array();
    $priorityFoodCategories = array(
      "Breakfast Entrees",
      "Today's Soup",
      "Brunch",
      "Entrees",
      "Accompaniments",
      "Desserts",
      "Pasta a la Carte",
      "Vegetables",
      "Starch & Potatoes",
    );

    foreach($priorityFoodCategories as $foodCategory) {
      if(isset($foodCategories[$foodCategory])) {
        $orderedFoodCategories[$foodCategory] = $foodCategories[$foodCategory];
      }
    }

    foreach($foodCategories as $category => $food_items) {
      if(!isset($orderedFoodCategories[$category])) {
        $orderedFoodCategories[$category] = $food_items;
      }
    }

    return $orderedFoodCategories;
  }

}