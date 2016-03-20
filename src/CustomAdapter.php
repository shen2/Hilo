<?php
namespace Hilo;

abstract class CustomAdapter extends BaseAdapter{
    public function fetchMulti($keys){
        $result = array();
        foreach($keys as $key)
            $result[$key] = $this->_fetch($key);
        return $result;
    }
    
    protected function cacheOne($key, $raw){
        $this->_cache->defer('set', [$this->_cacheNamespace . ':'. $key, $raw]);
    
        if ($this->_ttl)
            $this->_cache->defer('expire', [$this->_cacheNamespace . ':'. $key, $this->_ttl]);
    }
    
    public function cacheMulti($rowset){
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
        $this->_cache->defer('get', array($this->_cacheNamespace . ':' . $key), $callback);
    }
    
    public function preloadMultiFromCache($keys, $callback){
        $cacheKeys = [];
        foreach($keys as $key)
            $cacheKeys[] = $this->_cacheNamespace . ':' . $key;
        
        $this->_cache->defer('mget', array($cacheKeys), function($vals, $keys) use ($callback){
                foreach($vals as $index => $data)
                    $callback($data, $keys[$index]);
            });
    }
    
    public function unpack(&$data, $key){
        return $data;
    }
    
    public function unpackFromCache($data, $key){
        return $this->unserialize($data);
    }
}