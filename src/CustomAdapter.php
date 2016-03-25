<?php
namespace Hilo;

abstract class CustomAdapter extends BaseAdapter{
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
    
    public function preloadFromCache($key, $callback){
        $this->_cache->defer('get', [$this->_cacheNamespace . ':' . $key], $callback);
    }
    
    public function preloadMultiFromCache($keys, $callback){
        $cacheKeys = [];
        foreach($keys as $key)
            $cacheKeys[] = $this->_cacheNamespace . ':' . $key;
        
        $this->_cache->defer('mget', [$cacheKeys], function($vals, $keys) use ($callback){
                foreach($vals as $index => $data)
                    $callback($data, $keys[$index]);
            });
    }
    
    public function unpack(&$data, $key){
        return $data;
    }
    
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
