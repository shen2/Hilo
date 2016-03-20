<?php
namespace Hilo;

class DataObjectAdapter extends BaseAdapter{
    
	protected $_cacheFields = null;
	
	protected $_primary = null;
	
	/**
	 * 
	 * @var string
	 */
	protected $_dataClass = null;
	
    public function getKey($args){
		return $args[0];
	}
	
	public function fetch($key){
	    $resultMap = $this->fetchMuliti([$key]);
		return empty($resultMap) ? null : current($resultMap);
	}
	
	/**
	 * 从数据库中读取记录
	 * @return \Generator
	 */
	public function fetchMulti($keys){
		$cols = $this->_cacheFields
			? array_merge(array($this->_primary), $this->_cacheFields)
			: array($this->_primary, '*');
		
		$dataClass = $this->_dataClass;
		
		return $dataClass::getTable()->selectCol($cols)
			->where($this->_primary .' in (?)', $keys)
			->fetchAssocMap();
	}
	
	protected function cacheOne($key, $raw){
	    $this->_cache->defer('hMset', [$this->_cacheNamespace . ':'. $key, $this->serialize($raw)]);
	
	    if ($this->_ttl)
	        $this->_cache->defer('expire', array($this->_cacheNamespace . ':'. $key, $this->_ttl));
	}
	
	public function cacheMulti($rowset){
	    foreach ($rowset as $key => $raw){
	        $this->cacheOne($key, $raw);
	    }
	}
	
	public function preloadFromCache($key, $callback){
	    $this->_cache->defer('hGetAll', array($this->_cacheNamespace . ':' . $key), $callback);
	}
	
	public function preloadMultiFromCache($keys, $callback){
	    foreach($keys as $key){
	        $this->_cache->defer('hGetAll', array($this->_cacheNamespace . ':' . $key), $callback);
	    }
	}
	
	/**
	 * 从数据库读出来之后，到内存中的转换过程
	 * 
	 * @param mixed $value
	 * @param array $args
	 * @return mixed
	 */
	public function unpack($value, $key){
	    if ($value === null)
	        return null;
	
	    $data = array($this->_primary => $key);	//	单一主键的时候退化到这种情况
	
	    if ($this->_cacheFields){
	        foreach($this->_cacheFields as $field => $columnName)
	            $data[$columnName] = $value[$field];
	    }
	    else{
	        $data += $value;
	    }
	
	    $dataClass = $this->_dataClass;
	    return new $dataClass($data, true, true);
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
	
	public function unpackFromCache($data, $key){
	    $key = substr($key, strlen($this->_cacheNamespace) + 1);
	    return $this->unpack($data, $key);
	}
}
