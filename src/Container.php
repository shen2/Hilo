<?php
namespace Hilo;

class Container{
    /**
     * 
     * @var array
     */
    protected $_pool = [];
    
    /**
     * 已经送去cache查询，正在等待返回的key
     * @var array
     */
    protected $_waitingKeys = [];
    
    /**
     * 
     * @var AdapterInterface
     */
    protected $_adapter;
    
    /**
     * 
     * @param AdapterInterface $adapter
     */
    public function __construct($adapter){
        $this->_adapter = $adapter;
    }
    
    protected function _loadFromDb(){
        // 如果是null，也存进缓存
        // 这个操作必须在fetchMulti之前做，因为fetchMulti时有可能触发statement queue的flush，从而导致影响_waitingKeys
        foreach(array_diff_key($this->_waitingKeys, $this->_pool) as $key => $null){
            $this->_pool[$key] = null;
        }
        
        $rowset = $this->_adapter->fetchMulti(array_keys($this->_waitingKeys));
        
        foreach ($rowset as $key => $raw){
            $this->_pool[$key] = $this->_adapter->unpack($raw, $key);
            // fetchMulti()之后_waitingKeys有可能增加更多的key，因此不能直接把_waitingKeys置空，而需要一行一行unset
            unset($this->_waitingKeys[$key]);
        }
        
        $this->_adapter->cacheMulti($rowset);
    }
    
    public function get($offset){
        if (!array_key_exists($offset, $this->_pool)){
             
            if (!isset($this->_waitingKeys[$offset])){
                $this->_waitingKeys[$offset] = null;
                $this->_adapter->preloadFromCache($offset, $this);
            }
            
            $this->_adapter->flushCache();
             
            if (!array_key_exists($offset, $this->_pool)){    //Cache Miss $ Save
                //    在php的数组缓存中没有找到，再去mysql中找
                $this->_loadFromDb();
            }
        }
        return $this->_pool[$offset];
    }
    
    /**
     * 批量预读取多个数据
     *
     * @param array $keys
     */
    public function preload($keys){
        $queryKeys = array_diff_key(array_flip($keys), $this->_pool, $this->_waitingKeys);
        //    这里的keys不需要加array_unique()，array_flip()就可以去重
        $this->_waitingKeys += $queryKeys;
        
        if (!empty($queryKeys)){
            $this->_adapter->preloadMultiFromCache(array_keys($queryKeys), $this);
        }
    }
    
    /**
     * 获取多条记录，得到一个序列数组
     * @param $keys
     */
    public function getMulti($keys){
        $this->preload($keys);
        $this->_adapter->flushCache();
    
        if (!empty($this->_waitingKeys)){ //    在php的数组缓存中没有找到，再去mysql中找
            $this->_loadFromDb();
        }
        
        $result = [];
        foreach($keys as $key)
            if (!empty($this->_pool[$key]))    //    $this->_pool[$key] 有可能是null
                $result[] = $this->_pool[$key];
        return $result;
    }
    
    /**
     * 获取多条记录，得到一个关联数组
     * @param $keys
     */
    public function getAssocMulti($keys){
        $this->preload($keys);
        $this->_adapter->flushCache();
    
        if (!empty($this->_waitingKeys)){ //    在php的数组缓存中没有找到，再去mysql中找
            $this->_loadFromDb();
        }
        return array_intersect_key($this->_pool, array_flip($keys));
    }
    
    /**
     * 接收回调
     * @param $data
     * @param $key
     */
    public function onArrive($data, $key){
        $this->_pool[$key] = $data;
        unset($this->_waitingKeys[$key]);
    }

    /**
     * 强制删除缓存中的某个key
     */
    public function delete($key){
        unset($this->_pool[$key]);
        
        $this->_adapter->deleteCacheKey($key);
    }
}
