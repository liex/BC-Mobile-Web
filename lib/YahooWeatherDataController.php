<?php

require_once(LIB_DIR . '/WeatherData.php');

class YahooWeatherDataController extends WeatherDataController
{
    protected $DEFAULT_PARSER_CLASS='YahooWeatherDataParser';
    protected $cacheFileSuffix='rss';
    protected $baseURL='http://weather.yahooapis.com/forecastrss';
    
    protected function setUnits($units) {
        parent::setUnits($units);
        $this->addFilter('u', $units);
    }
    
    protected function setLocation($location) {
        parent::setLocation($location);
        $this->addFilter('w', $location);
    }

    protected function parseData($data, DataParser $parser=null) {
        $this->parser->setUnits($this->units);
        $parsedData = $this->parser->parseData($data);
    
        if ($this->title) {
            foreach ($parsedData as &$item) {
                $item->setTitle($this->title);
            }
        }
        return $parsedData;
    }
}

class YahooWeatherDataParser extends RSSDataParser {

    protected $units=WeatherData::UNITS_IMPERIAL;
    
    public function setUnits($units) {
        if (in_array($units, array(WeatherData::UNITS_METRIC, WeatherData::UNITS_IMPERIAL))) {
            $this->units = $units;
        } else {
            throw new Exception("Invalid units $units");
        }
    }

    public function parseData($data) {
        $data = parent::parseData($data);
        $item = isset($data[0]) ? $data[0] : false;
        $items = array();
        
        foreach ($data as $item) {
            $WeatherData = new WeatherData();
            $WeatherData->setUnits($this->units);
            
            /* get title. attempt to extract out the location to be prettier */
            $title = $item->getTitle();
            if (preg_match("/^Conditions for (.*?) at/", $title, $bits)) {
                $title = $bits[1];
            }
            $WeatherData->setTitle($title);

            /* set time */
            $time = new DateTime($item->getPubDate());
            $WeatherData->setTimestamp($time);
            
            /* set lat/long */
            $WeatherData->setLat($item->getProperty('geo:lat'));
            $WeatherData->setLong($item->getProperty('geo:long'));

            /* attempt to extract the image */
            $content = $item->getContent();    
            if (preg_match('/<img src="([^"]+)"[^>]+>/', $content, $bits)) {
                $WeatherData->setImage($bits[1]);
            }
            
            if ($condition = $item->getChildElement('yweather:condition')) {
                $WeatherData->setCondition($condition->getAttrib('text'));
                $WeatherData->setTemperature($condition->getAttrib('temp'));
            }

            $WeatherData->setURL($item->getLink());            
            
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