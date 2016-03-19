<?php
namespace JezoPool;

trait StringTrait{
	
	protected function _saveToCache($key, $raw){
		static::$_cache->defer('set', array($this->_cacheNamespace . ':'. $key, $raw));
	
		if ($this->_ttl)
			static::$_cache->defer('expire', array($this->_cacheNamespace . ':'. $key, $this->_ttl));
	}
	
	protected function _preloadFromCache($key){
		static::$_cache->defer('get', array($this->_cacheNamespace . ':' . $key), array($this, 'onArrive'));
	}
	
	/**
	 * 批量预读取多个数据
	 *
	 * @param array $keys
	 * @param string $columnNames
	 */
	public function preload($keys){
		$queryKeys = array();
		//	这里的keys不需要加array_unique()，这个迭代本身就可以去重
		foreach($keys as $key){
			if (!parent::offsetExists($key) && !array_key_exists($key, $this->_keysNotInPool)){
				$queryKeys[] = $this->_cacheNamespace . ':' . $key;
				$this->_keysNotInPool[$key] = null;
			}
		}
		if (!empty($queryKeys)){
			static::$_cache->defer('mget', array($queryKeys), function($vals, $keys){
				foreach($vals as $index => $val){
					$this->onArrive($val, $keys[$index]);
				}
			});
		}
	}
}
