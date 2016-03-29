<?php
namespace Hilo;

abstract class RedisHashAdapter implements AdapterInterface{
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
        $this->_cache->defer('hMset', [$this->_cacheNamespace . ':'. $key, $this->serialize($raw)]);
    
        if ($this->_ttl)
            $this->_cache->defer('expire', [$this->_cacheNamespace . ':'. $key, $this->_ttl]);
    }
    
    public function cacheMulti($rowset){
        foreach ($rowset as $key => $raw){
            $this->cacheOne($key, $raw);
        }
    }
    
    public function preloadFromCache($key, $container){
        $this->_cache->defer('hGetAll', [$this->_cacheNamespace . ':' . $key], function($data) use ($key, $container){
            if (!empty($data))
                $container->onArrive($this->unpack($data, $key), $key);
        });
    }
    
    public function preloadMultiFromCache($keys, $container){
        foreach($keys as $key){
            $this->preloadFromCache($key, $container);
        }
    }
    
    public function unpack(&$data, $key){
        return $data;
    }
    
    /**
     * PHP 5.6.9 之前的原生php5-redis扩展，如果数组中包含值为null的属性，会直接触发core dump
     * 而5.5及之前的版本会自动发生强转为''
     * @param array $data
     * @return array
     */
    protected function serialize(&$data){
        foreach($data as $key => $value)
            if ($value === null)
                $data[$key] = '';
        return $data;
    }
}
