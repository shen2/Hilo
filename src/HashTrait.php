<?php
namespace JezoPool;

trait HashTrait{
	protected function _saveToCache($key, $raw){
		static::$_cache->defer('hMset', array($this->_cacheNamespace . ':'. $key, $raw));
	
		if ($this->_ttl)
			static::$_cache->defer('expire', array($this->_cacheNamespace . ':'. $key, $this->_ttl));
	}
	
	protected function _preloadFromCache($key){
		static::$_cache->defer('hGetAll', array($this->_cacheNamespace . ':' . $key), array($this, 'onArrive'));
	}
	
	/**
	 * 批量预读取多个数据
	 *
	 * @param mixed $statement
	 * @param string $columnNames
	 */
	public function preload($keys){
		//	这里的keys不需要加array_unique()，这个迭代本身就可以去重
		foreach($keys as $key){
			if (!parent::offsetExists($key) && !array_key_exists($key, $this->_keysNotInPool)){
				$this->_preloadFromCache($key);
				$this->_keysNotInPool[$key] = null;
			}
		}
	}
}
