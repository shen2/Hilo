<?php
namespace Hilo;

interface AdapterInterface{
    
    public function flushCache();
    
    public function deleteCacheKey($key);

    /**
     *
     * @param string|int $key
     */
    public function fetch($key);
    
    public function fetchMulti($keys);
    
    //protected function cacheOne($key, $raw);
    
    public function cacheMulti($rowset);
    
    public function preloadFromCache($key, $callback);
    
    /**
     * 从数据库读出来之后，重建成我们需要的数据
     * DB => Mem
     * @var unknown
    */
    public function unpack(&$data, $key);
}