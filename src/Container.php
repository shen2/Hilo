<?php
namespace Hilo;

class Container{
    /**
     * 
     * @var array
     */
    protected $_pool = [];
    
    /**
     * 已经送去cache查询，但是还没有去mysql查询的变量
     * @var array
     */
    protected $_keysNotInPool = [];
    
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
        $rowset = $this->_adapter->fetchMulti(array_keys($this->_keysNotInPool));
        
        //如果是null，也存进缓存
        foreach(array_diff_key($this->_keysNotInPool, $this->_pool) as $key => $null){
            $this->_pool[$key] = null;
        }
        
        $this->_keysNotInPool = [];
        
        foreach ($rowset as $key => $raw){
            $this->_pool[$key] = $this->_adapter->unpack($raw, $key);
        }
        
        $this->_adapter->cacheMulti($rowset);
    }
    
    public function get($offset){
        if (!array_key_exists($offset, $this->_pool)){
             
            if (!isset($this->_keysNotInPool[$offset])){
                $this->_adapter->preloadFromCache($offset, [$this, 'onArrive']);
                $this->_keysNotInPool[$offset] = null;
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
        $queryKeys = array_diff_key(array_flip($keys), $this->_pool, $this->_keysNotInPool);
        //    这里的keys不需要加array_unique()，array_flip()就可以去重
        $this->_keysNotInPool += $queryKeys;
        
        if (!empty($this->_keysNotInPool)){
            $this->_adapter->preloadMultiFromCache(array_keys($queryKeys), [$this, 'onArrive']);
        }
    }
    
    /**
     * 获取多条记录，得到一个序列数组
     * @param $keys
     */
    public function getMulti($keys){
        $this->preload($keys);
        $this->_adapter->flushCache();
    
        if (!empty($this->_keysNotInPool)){ //    在php的数组缓存中没有找到，再去mysql中找
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
    
        if (!empty($this->_keysNotInPool)){ //    在php的数组缓存中没有找到，再去mysql中找
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
        if (!empty($data)){
            $this->_pool[$key] = $this->_adapter->unpackFromCache($data, $key);
            unset($this->_keysNotInPool[$key]);
        }
    }

    /**
     * 强制删除缓存中的某个key
     */
    public function delete($key){
        unset($this->_pool[$key]);
        
        $this->_adapter->deleteCacheKey($key);
    }
}
