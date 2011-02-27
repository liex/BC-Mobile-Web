<?php

abstract class WeatherDataController extends DataController
{
    protected $cacheFolder='Weather';
    protected $cacheLifetime = 100000;
    protected $units='f';
    protected $title;
    protected $location;
    
    public function getItem($id) {
        return false;
    }
    
    protected function setUnits($units) {
        if (in_array($units, array(WeatherData::UNITS_METRIC, WeatherData::UNITS_IMPERIAL))) {
            $this->units = $units;
        } else {
            throw new Exception("Invalid weather units $units");
        }
    }
    
    protected function setLocation($location) {
        $this->location = $location;
    }
    
    protected function init($args) {
        parent::init($args);

        if (isset($args['LOCATION'])) {
            $this->setLocation($args['LOCATION']);
        } else {
            throw new Exception('Weather location not set');
        }

        if (isset($args['UNITS'])) {
            $this->setUnits($args['UNITS']);
        } else {
            $this->setUnits($this->units);
        }
    }
    
}

