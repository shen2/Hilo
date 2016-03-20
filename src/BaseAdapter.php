<?php
namespace Hilo;

abstract class BaseAdapter implements AdapterInterface{
    /**
     *
     * @var int
     */
    protected $_ttl = null;
    
    /**
     *
     * @var string
     */
    protected $_cacheNamespace = '';
    
    /**
     *
     * @var \EasyRedis\Manager
     */
    protected $_cache = null;
    
    /**
     * 
     * @param \EasyRedis\Manager $cache
     */
    public function __construct($cache){
        $this->_cache = $cache;
    }
    
    /**
     *
     * @param \EasyRedis\Manager $redisManager
     */
    public function setCache($cache){
        $this->_cache = $cache;
    }
    
    /**
     *
     * @return \EasyRedis\Manager
     */
    public function getCache(){
        return $this->_cache;
    }
    
    public function flushCache(){
        $this->_cache->flush();
    }
    
    public function deleteCacheKey($key){
        $this->_cache->defer('delete', [$this->_cacheNamespace . ':' . $key]);
    }
}
