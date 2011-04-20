<?php
/**
 * @package ExternalData
 */

/**
 * A generic class to handle the retrieval of external data
 * 
 * Handles retrieval, caching and parsing of data. 
 * @package ExternalData
 */
abstract class DataController
{
    protected $DEFAULT_PARSER_CLASS='PassthroughDataParser';
    protected $cacheFolder='Data';
    protected $cacheFileSuffix='';
    protected $parser;
    protected $url;
    protected $cache;
    protected $baseURL;
    protected $title;
    protected $filters=array();
    protected $headers=array();
    protected $totalItems = null;
    protected $debugMode=false;
    protected $useCache=true;
    protected $cacheLifetime=900;
    
    /**
     * This method should return a single item based on the id
     * @param mixed $id the id to retrieve. The value of this id is data dependent.
	 * @return mixed The return value is data dependent. Subclasses should return false or null if the item could not be found
     */
    abstract public function getItem($id);

    /**
     * Returns the folder used to store caches. Subclasses should simply set the $cacheFolder property
	 * @return string
     */
    protected function cacheFolder() {
        return CACHE_DIR . "/" . $this->cacheFolder;
    }
    
    /**
     * This method should return the file suffix for cache files. Subclasses should simply set the $cacheFileSuffix property if necessary
	 * @return string
     */
    protected function cacheFileSuffix() {
        return $this->cacheFileSuffix ? '.' . $this->cacheFileSuffix : '';
    }
    
    /**
     * Turns on or off debug mode. In debug mode, URL requests and information are logged to the php error log
     * @param bool 
     */
    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode ? true : false;
    }
    
    /**
     * Adds a parameter to the url request. In the subclass has not overwritten url() then it will be added to the
     * url as a query string. Note that you can only have 1 value per parameter at this time. This method
     * will call clearInternalCache() since this will cause any previous data to be invalid.
     * @param string $var the parameter to add
     * @param mixed $value the value to assign. Must be a scalar value
     */
    public function addFilter($var, $value) {
        $this->filters[$var] = $value;
        $this->clearInternalCache();
    }

    /**
     * Removes a parameter from the url request. This method will call clearInternalCache() since this 
     * will cause any previous data to be invalid.
     * @param string $var the parameter to remove
     */
    public function removeFilter($var) {
        if (isset($this->filters[$var])) {
            unset($this->filters[$var]);
            $this->clearInternalCache();
        }
    }

    /**
     * Remove all parameters from the url request. This method will call clearInternalCache() since 
     * this will cause any previous data to be invalid.
     */
    public function removeAllFilters() {
        $this->filters = array();
        $this->clearInternalCache();
    }

    /**
     * Clears the internal cache of data. Subclasses can override this method to clean up any necessary
     * state, if necessary. Subclasses should call parent::clearInteralCache()
     */
    protected function clearInternalCache() {
        $this->setTotalItems(null);
    }

    /**
     * Returns a base filename for the cache file that will be used. The default implementation uses
     * a hash of the value returned from the url() method
     * @return string
     */
    protected function cacheFilename() {
        return md5($this->url());
    }

    /**
     * Returns a full path to the cacheMetaFile, a file used when debug mode is on to include information
     * about the request.
     * @return string
     */
    protected function cacheMetaFile() {
        return sprintf("%s/%s-meta.txt", $this->cacheFolder(), md5($this->url()));
    }
    
   /**
     * Sets the data parser to use for this request. Typically this is set at initialization automatically,
     * but certain subclasses might need to determine the parser dynamically.
     * @param DataParser a instantiated DataParser object
     */
    public function setParser(DataParser $parser) {
        $this->parser = $parser;
    }

    /**
     * Turns on or off using cache. You could also set cacheLifetime to 0
     * @param bool
     */
    public function setUseCache($useCache) {
        $this->useCache = $useCache ? true : false;
    }
    
    /**
     * Sets the title of the controller. Subclasses could use this if the title is dynamic.
     * @param string
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Returns the title of the controller.
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Sets the total number of items in the request. If subclasses override parseData() this method
     * should be called when the number of items is known. The value is usually set by retrieving the
     * the value of getTotalItems() from the DataParser.
     * @param int
     */
    protected function setTotalItems($totalItems) {
        $this->totalItems = $totalItems;
    }
    
    /**
     * Returns the total number of items in the request
     * @return int
     */
    public function getTotalItems() {
        return $this->totalItems;
    }
    
    /**
     * Sets the base url for the request. This value will be set automatically if the BASE_URL argument
     * is included in the factory method. Subclasses that have fixed URLs (i.e. web service data controllers)
     * can set this in the init() method.
     * @param string $baseURL the base url including protocol
     * @param bool clearFilters whether or not to clear the filters when setting (default is true)
     */
    public function setBaseURL($baseURL, $clearFilters=true) {
        $this->baseURL = $baseURL;
        if ($clearFilters) {
            $this->removeAllFilters();
        }
        $this->clearInternalCache();
    }
    
    /**
     * The initialization function. Sets the common parameters based on the $args. This method is
     * called by the public factory method. Subclasses can override this method, but must call parent::init()
     * FIRST. Optional parameters include PARSER_CLASS, BASE_URL, TITLE and CACHE_LIFETIME. Arguments
     * are also passed to the data parser object
     * @param array $args an associative array of arguments and paramters
     */
    protected function init($args) {

        // use a parser class if set, otherwise use the default parser class from the controller
        $args['PARSER_CLASS'] = isset($args['PARSER_CLASS']) ? $args['PARSER_CLASS'] : $this->DEFAULT_PARSER_CLASS;

        // instantiate the parser class and add it to the controller
        $parser = DataParser::factory($args['PARSER_CLASS'], $args);
        $this->setParser($parser);
        
        if (isset($args['BASE_URL'])) {
            $this->setBaseURL($args['BASE_URL']);
        }

        if (isset($args['TITLE'])) {
            $this->setTitle($args['TITLE']);
        }

        if (isset($args['CACHE_LIFETIME'])) {
            $this->setCacheLifetime($args['CACHE_LIFETIME']);
        }
    }

    /**
     * Public factory method. This is the designated way to instantiated data controllers. Takes a string
     * for the classname to load and an array of arguments. Subclasses should generally not override this
     * method, but instead override init() to provide initialization behavior
     * @param string $controllerClass the classname to instantiate
     * @param array $args an associative array of arguments that get passed to init() and the data parser
     * @return DataController a data controller object
     */
    public static function factory($controllerClass, $args=array()) {
        $args = is_array($args) ? $args : array();

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller class $controllerClass not defined");
        }
        
        $controller = new $controllerClass;
        
        if (!$controller instanceOf DataController) {
            throw new Exception("$controllerClass is not a subclass of DataController");
        }
        
        $controller->init($args);

        return $controller;
    }
    
    /**
     * Returns the url to use for the request. The default implementation will take the base url and
     * append any filters/parameters as query string parameters. Subclasses can override this method 
     * if a more dynamic method of URL generation is needed.
     * @return string
     */
    protected function url() {
        $url = $this->baseURL;
        if (count($this->filters)>0) {
            $glue = strpos($this->baseURL, '?') !== false ? '&' : '?';
            $url .= $glue . http_build_query($this->filters);
        }
        
        return $url;
    }
    
    /**
     * Parse the data. This method will also attempt to set the total items in a request by calling the
     * data parser's getTotalItems() method
     * @param string $data the data from a request (could be from the cache)
     * @param DataParser $parser optional, a alternative data parser to use. 
     * @return mixed the parsed data. This value is data dependent
     */
    protected function parseData($data, DataParser $parser=null) {       
        if (!$parser) {
            $parser = $this->parser;
        }
        $parsedData = $parser->parseData($data);
        $this->setTotalItems($parser->getTotalItems());
        return $parsedData;
    }
    
    /**
     * Return the parsed data. The default implementation will retrive the data and return value of
     * parseData()
     * @param DataParser $parser optional, a alternative data parser to use. 
     * @return mixed the parsed data. This value is data dependent
     */
    public function getParsedData(DataParser $parser=null) {
        $data = $this->getData();
        return $this->parseData($data, $parser);
    }
    
    /**
     * Returns a unix timestamp to use for the cache file. Return null to use the current time. Subclasses
     * can override this method to use a timestamp based on the returning data if appropriate.
     * @param string $data the unparsed data included by the request
     * @return int a unix timestamp or null to use the current time
     */
    protected function cacheTimestamp($data) {
        return null;
    }
    
    /**
     * Returns whether the cache is fresh or not. Subclasses could override this if they implement
     * custom caching 
     * @return bool 
     */
    protected function cacheIsFresh() {
        $cache = $this->getCache();
        return $cache->isFresh($this->cacheFilename());
    }

    /**
     * Returns the cached data based on the cacheFilename() custom caching. Subclasses could override 
     * this if they implement custom caching 
     * @return string 
     */
    protected function getCacheData() {
        $cache = $this->getCache();
        return $cache->read($this->cacheFilename());
    }
    
    /**
     * Writes the included data to the file based on cacheFilename(). Subclasses could override 
     * this if they implement custom caching 
     * @param string the data to cache
     */
    protected function writeCache($data) {
        $cache = $this->getCache();
        $cache->write($data, $this->cacheFilename(), $this->cacheTimestamp($data));
    }
    
    /**
     * Returns the a DiskCache object for this controller. Subclasses could override this if they
     * need to provide a custom object for caching. It should implement the DiskCache interface
     * @return DiskCache object
    */
    protected function getCache() {
        if ($this->cache === NULL) {
              $this->cache = new DiskCache($this->cacheFolder(), $this->cacheLifetime, TRUE);
              $this->cache->setSuffix($this->cacheFileSuffix());
              $this->cache->preserveFormat();
        }
        
        return $this->cache;
    }
    
    /**
     * Retrieves the data.  The default implementation will use the url returned by the url() 
     * function. If the cache is still fresh than it will return the data saved in the cache,
     * otherwise it will retrieve the data using the retrieveData() method and save the cache.
     * Subclasses should only need to override this method if an alternative caching scheme is
     * @param int a unix timestamp or null to use the current time
     */
    public function getData() {

        if (!$url = $this->url()) {
            throw new Exception("URL could not be determined");
        }

        $this->url = $url;
        $this->totalItems = 0;

        if ($this->useCache) {
            if ($this->cacheIsFresh()) {
                $data = $this->getCacheData();
            } else {

                if ($data = $this->retrieveData($url)) {
                    $this->writeCache($data); 
                }
                // should we return the stale cache if the data is unavailable ? 
            }
        } else {
            $data = $this->retrieveData($url);
        }
        
        return $data;
    }

    /**
     * Retrieves the data using the given url. The default implementation uses the file_get_content()
     * function to retrieve the request. Subclasses would need to implement this if a simple GET request
     * is not sufficient (i.e. you need POST or custom headers). 
     * @param string the url to retrieve
     * @return string the response from the server
     * @TODO support POST requests and custom headers and perhaps proxy requests
     */
    protected function retrieveData($url) {
        if ($this->debugMode) {
            error_log(sprintf(__CLASS__ . " Retrieving %s", $url));
        }
        
        $data = file_get_contents($url); 

        if ($this->debugMode) {
            file_put_contents($this->cacheMetaFile(), $url);
        }
        
        return $data;
    }

    /**
     * Sets the cache lifetime in seconds. Will be called if the initialization args contains CACHE_LIFETIME
     * @param int seconds to cache results (default for base class is 900 seconds / 15 minutes)
     */
    public function setCacheLifetime($seconds) {
        $this->cacheLifetime = intval($seconds);
    }

    /**
     * Sets the target encoding of the result. Defaults to utf-8.
     * @param string
     */
    public function setEncoding($encoding) {
        $this->parser->setEncoding($encoding);
    }

    /**
     * Returns the target encoding of the result.
     * @return string. Default is utf-8
     */
    public function getEncoding() {
        return $this->parser->getEncoding();
    }
    
    /**
     * Utility function to return a subset of items. Essentially is a robust version of array_slice.
     * @param array items
     * @param int $start 0 indexed value to start
     * @param int $limit how many items to return (use null to return all items beginning at $start)
     * @return array
     */
    protected function limitItems($items, $start=0, $limit=null) {
        $start = intval($start);
        $limit = is_null($limit) ? null : intval($limit);

        if ($limit && $start % $limit != 0) {
            $start = floor($start/$limit)*$limit;
        }
        
        if (!is_array($items)) {
            throw new Exception("Items list is not an array");
        }
        
        if ($start>0 || !is_null($limit)) {
            $items = array_slice($items, $start, $limit);
        }
        
        return $items;
        
    }

    /**
     * Returns an item at a particular index
     * @param int index
     * @return mixed the item or false if it's not there
     */
    public function getItemByIndex($index) {
        if ($items = $this->items($index,1)) {
            return current($items); 
        } else {
            return false;
        }
    }
    
    /**
     * Default implementation of items. Will retrieve the parsed items based on the current settings
     * and return a filtered list of items
     * @param int $start 0 based index to start
     * @limit int $limit number of items to return
     */
    public function items($start=0, $limit=null) {
        $items = $this->getParsedData();
        return $this->limitItems($items,$start, $limit);
    }
}

