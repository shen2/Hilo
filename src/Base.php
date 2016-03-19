<?php
namespace JezoPool;

abstract class Base extends \ArrayObject{
	
	public static $instances = array();
	
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
	 * 已经送去cache查询，但是还没有去mysql查询的变量
	 * @var array
	 */
	protected $_keysNotInPool = array();
	
	/**
	 * 
	 * @var \EasyRedis\Manager
	 */
	protected static $_cache = null;
	
	/**
	 * 
	 * @param \EasyRedis\Manager $redisManager
	 */
	public static function setCache($cache){
		static::$_cache = $cache;
	}
	
	/**
	 * 
	 * @return \EasyRedis\Manager
	 */
	public static function getCache(){
		return static::$_cache;
	}
	
	abstract protected function _preloadFromCache($key);
	
	abstract public function preload($keys);
	
	protected function _fetch($key){
		return null;
	}
	
	protected function _fetchMulti($keys){
		$result = array();
		foreach($keys as $key)
			$result[$key] = $this->_fetch($key);
		return $result;
	}
	
	protected function _getKey($args){
		return implode('_' , $args);
	}
	
	/**
	 * 数据库中取出的结果，在直接使用之前的转换
	 * @param mixed $data
	 */
	protected function _unpack($data, $key){
    	return $data;
	}
	
	protected function _loadFromDb(){
		$rowset = $this->_fetchMulti(array_keys($this->_keysNotInPool));
		
		//如果是null，也存进缓存
		foreach($this->_keysNotInPool as $key => $null){
			if (!parent::offsetExists($key))
				parent::offsetSet($key, null);
		}
		
		$this->_keysNotInPool = array();
		
		foreach ($rowset as $key => $raw){
			
			parent::offsetSet($key, $this->_unpack($raw, $key));
        	
			if (method_exists($this, '_serializeData'))
				$raw = $this->_serializeData($raw);
			$this->_saveToCache($key, $raw);
		}
	}
	
	public function offsetGet($offset){
		if (!parent::offsetExists($offset)){
			 
			if (!isset($this->_keysNotInPool[$offset])){
				$this->_preloadFromCache($offset);
				$this->_keysNotInPool[$offset] = null;
			}
			
			static::$_cache->flush();
			 
			if (!parent::offsetExists($offset)){    //Cache Miss $ Save
				//	在php的数组缓存中没有找到，再去mysql中找
				$this->_loadFromDb();
			}
		}
		return parent::offsetGet($offset);
	}
	
	public function get(){
		return $this->offsetGet($this->_getKey(func_get_args()));
	}
	
	public function delete(){
		 static::$_cache->defer('delete', array($this->_cacheNamespace . ':' . $this->_getKey(func_get_args())));
	}
	
	/**
	 * 获取多条记录，得到一个序列数组
	 * @param $keys
	 */
	public function getMulti($keys){
		$this->preload($keys);
		static::$_cache->flush();
	
		if (!empty($this->_keysNotInPool)){ //	在php的数组缓存中没有找到，再去mysql中找
			$this->_loadFromDb();
		}
		
		$result = array();
		foreach($keys as $key)
			if (!empty($this[$key]))	//	$this[$key] 有可能是null
				$result[] = $this[$key];
		return $result;
	}
	
	/**
	 * 获取多条记录，得到一个关联数组
	 * @param $keys
	 */
	public function getAssocMulti($keys){
		$this->preload($keys);
		static::$_cache->flush();
	
		if (!empty($this->_keysNotInPool)){ //	在php的数组缓存中没有找到，再去mysql中找
			$this->_loadFromDb();
		}
		return array_intersect_key($this->getArrayCopy(), array_flip($keys));
	}
	
	public function onArrive($data, $key){
		if (!empty($data)){
			if (method_exists($this, '_unserializeData'))
				$data = $this->_unserializeData($data);
			
			$key = substr($key, strlen($this->_cacheNamespace) + 1);
			parent::offsetSet($key, $this->_unpack($data, $key));
			unset($this->_keysNotInPool[$key]);
		}
	}
}
