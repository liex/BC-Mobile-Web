<?php

require_once(LIB_DIR . '/WeatherData.php');

class YahooWeatherDataController extends DataController
{
    protected $DEFAULT_PARSER_CLASS='YahooWeatherDataParser';
    protected $cacheFolder='YahooWeather';
    protected $cacheFileSuffix='rss';
    protected $cacheLifetime = 100000;
    protected $units='f';
    
    public function getItem($id) {
        return false;
    }
    
    protected function setUnits($units) {
        $this->units = $units;
        $this->addFilter('u', $units);
        $this->parser->setUnits($units);
    }
    
    protected function init($args) {
        parent::init($args);
        $this->setBaseURL('http://weather.yahooapis.com/forecastrss');

        if (isset($args['LOCATION'])) {
            $this->addFilter('w', $args['LOCATION']);
        } else {
            throw new Exception('Location not set');
        }

        if (isset($args['UNITS'])) {
            $this->setUnits($args['UNITS']);
        } else {
            $this->setUnits($this->units);
        }
    }
   
}

class YahooWeatherDataParser extends RSSDataParser {

    protected $units=WeatherData::UNITS_IMPERIAL;
    
    public function setUnits($units) {
        $this->units = $units;
    }

    public function parseData($data) {
        $data = parent::parseData($data);
        $item = isset($data[0]) ? $data[0] : false;
        $items = array();
        
        foreach ($data as $item) {
            $WeatherData = new WeatherData();
            $WeatherData->setUnits($this->units);
            $WeatherData->setTitle($item->getTitle());
            $time = new DateTime($item->getPubDate());
            $WeatherData->setTimestamp($time);
            $WeatherData->setLat($item->getProperty('geo:lat'));
            $WeatherData->setLong($item->getProperty('geo:long'));
            
            if ($condition = $item->getChildElement('yweather:condition')) {
                $WeatherData->setCondition($condition->getAttrib('text'));
                $WeatherData->setTemperature($condition->getAttrib('temp'));
            }
            
            if ($forecasts = $item->getChildElement('yweather:forecast')) {
                foreach ($forecasts as $forecast) {
                    $WeatherForecast = new WeatherForecast();
                    $WeatherForecast->setUnits($this->units);
                    $WeatherForecast->setDate(new DateTime($forecast->getAttrib('date')));
                    $WeatherForecast->setText($forecast->getAttrib('text'));
                    $WeatherForecast->setHigh($forecast->getAttrib('high'));
                    $WeatherForecast->setLow($forecast->getAttrib('low'));
                    $WeatherData->addForecast($WeatherForecast);
                }
            }
            
            $items[] = $WeatherData;
        }
        
        return $items;
    }
}