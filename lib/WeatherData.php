<?php

class WeatherData
{
    const UNITS_METRIC='c';
    const UNITS_IMPERIAL='f';
    protected $units=WeatherData::UNITS_IMPERIAL;
    protected $lat;
    protected $long;
    protected $title;
    protected $condition;
    protected $timestamp;
    protected $temperature;
    protected $image;
    protected $forecasts = array();
 
    public function setUnits($units) {
        if (in_array($units, array(WeatherData::UNITS_METRIC, WeatherData::UNITS_IMPERIAL))) {
            $this->units = $units;
        } else {
            throw new Exception("Invalid units $units");
        }
    }
    
    public function getUnits() {
        return $this->units;
    }
    
    public function setLat($lat) {
        $this->lat = $lat;
    }
    
    public function getLat() {
        return $this->lat;
    }

    public function setLong($long) {
        $this->long = $long;
    }

    public function getLong() {
        return $this->long;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }
    
    public function setCondition($condition) {
        $this->condition = $condition;
    }
    
    public function getCondition() {
        return $this->condition;
    }
    
    public function setTimestamp(DateTime $timestamp) {
        $this->timestamp = $timestamp;
    }
    
    public function getTimestamp() {
        return $this->timestamp;
    }

    public function setTemperature($temperature) {
        $this->temperature = $temperature;
    }
    
    public function getTemperature() {
        return $this->temperature;
    }

    public function setImage($img) {
        $this->image = $img;
    }
    
    public function getImage() {
        return $this->image;
    }

    public function addForecast(WeatherForecast $forecast) {
        $this->forecasts[$forecast->getDate('Ymd')] = $forecast;
    }
    
    public function getForecast($date) {
        return isset($this->forecasts[$date]) ? $this->forecasts[$date] : null;
    }
    
    public function getSummary() {
        $summary = sprintf("Now: %s, %d %s", $this->getCondition(), $this->getTemperature(), strtoupper($this->getUnits()));
        if ($forecast = $this->getForecast(date('Ymd'))) {
            $summary .= PHP_EOL . "Today: " . $forecast->getSummary();
        }
        
        if ($forecast = $this->getForecast(date('Ymd', strtotime("+1 day")))) {
            $summary .= PHP_EOL . "Tomorrow: " . $forecast->getSummary();
        }
        
        return $summary;
    }

}

class WeatherForecast
{
    protected $units;
    protected $date;
    protected $text;
    protected $high;
    protected $low;

    public function setUnits($units) {
        if (in_array($units, array(WeatherData::UNITS_METRIC, WeatherData::UNITS_IMPERIAL))) {
            $this->units = $units;
        } else {
            throw new Exception("Invalid units $units");
        }
    }
    
    public function getSummary() {
        return sprintf("%s High: %d Low: %s", $this->getText(), $this->getHigh(), $this->getLow());
    }
    
    public function getUnits() {
        return $this->units;
    }

    public function setDate(DateTime $date) {
        $this->date = $date;
    }
    
    public function getDate($format=null) {
        return $format ? $this->date->format($format) : $this->date;
    }

    public function setText($text) {
        $this->text = $text;
    }
    
    public function getText() {
        return $this->text;
    }

    public function setHigh($high) {
        $this->high = $high;
    }
    
    public function getHigh() {
        return $this->high;
    }

    public function setLow($low) {
        $this->low = $low;
    }
    
    public function getLow() {
        return $this->low;
    }
    
}