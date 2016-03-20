<?php
namespace Hilo;

abstract class BaseAdapter{
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
    
    /**
     * 
     * @param string|int $key
     */
    abstract public function fetch($key);
    
    abstract public function fetchMulti($keys);
    
    abstract protected function cacheOne($key, $raw);
    
    abstract public function cacheMulti($rowset);
    
    abstract public function preloadFromCache($key, $callback);
    
    /**
     * 从数据库读出来之后，重建成我们需要的数据
     * DB => Mem
     * @var unknown
     */
    abstract public function unpack();
    
    /**
     * 从cache中读出的数据，重建成我们内存中的数据
     * Cache => Mem
     * @var
     */
    abstract public function unpackFromCache($data, $key);
    
    /**
     * Raw => Cache
     * @var 
     */
    abstract protected function serialize();
}
