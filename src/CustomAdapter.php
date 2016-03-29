<?php
namespace Hilo;

abstract class RedisStringAdapter implements AdapterInterface{
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
    
    public function fetchMulti($keys){
        $result = [];
        foreach($keys as $key)
            $result[$key] = $this->fetch($key);
        return $result;
    }
    
    protected function cacheOne($key, $raw){
        $this->_cache->defer('set', [$this->_cacheNamespace . ':'. $key, $this->serialize($raw)]);
    
        if ($this->_ttl)
            $this->_cache->defer('expire', [$this->_cacheNamespace . ':'. $key, $this->_ttl]);
    }
    
    public function cacheMulti($rowset){
        if (empty($rowset))
            return;
        
        $params = [];
        foreach($rowset as $key => $raw)
            $params[$this->_cacheNamespace . ':'. $key] = $this->serialize($raw);
        
        $this->_cache->defer('mset', [$params]);

        if ($this->_ttl){
            foreach($rowset as $key => $raw)
                $this->_cache->defer('expire', [$this->_cacheNamespace . ':'. $key, $this->_ttl]);
        }
    }
    
    public function preloadFromCache($key, $container){
        $this->_cache->defer('get', [$this->_cacheNamespace . ':' . $key], function($data) use ($key, $container){
            if (!empty($data))
                $container->onArrive($this->unpackFromCache($data, $key), $key);
        });
    }
    
    public function preloadMultiFromCache($keys, $container){
        $cacheKeys = [];
        foreach($keys as $key)
            $cacheKeys[] = $this->_cacheNamespace . ':' . $key;
        
        $this->_cache->defer('mget', [$cacheKeys], function($vals) use ($keys, $container){
                foreach($vals as $index => $data)
                    if (!empty($data))
                        $container->onArrive($this->unpackFromCache($data, $keys[$index]), $keys[$index]);
            });
    }
    
    public function unpack(&$data, $key){
        return $data;
    }
    
    /**
     * 从cache中读出的数据，重建成我们内存中的数据
     * Cache => Mem
     * @var
     */
    public function unpackFromCache($data, $key){
        return $data;
    }
    
    /**
     * Raw => Cache
     * @var 
     */
    protected function serialize(&$data){
        return $data;
    }
}
