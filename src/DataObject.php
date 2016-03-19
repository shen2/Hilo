<?php
namespace JezoPool;
use \PDO;

class DataObject extends Base{
	
	use HashTrait;
	
	protected $_cacheFields = null;
	
	protected $_primary = null;
	
	/**
	 * 
	 * @var string
	 */
	protected $_dataClass = null;
	
    protected function _getKey($args){
		return $args[0];
	}
	
	/**
	 * 数据格式转换
	 * @param mixed $value
	 * @param array $args
	 * @return mixed
	 */
	protected function _unpack($value, $key){
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
	 * 从数据库中读取记录
	 * @return \Generator
	 */
	protected function _fetchMulti($keys){
		$cols = $this->_cacheFields
			? array_merge(array($this->_primary), $this->_cacheFields)
			: array($this->_primary, '*');
		
		$dataClass = $this->_dataClass;
		
		return $dataClass::selectCol($cols)
			->where($this->_primary .' in (?)', $keys)
			->fetchAssocMap();
	}
	
	/**
	 * PHP 5.6.9 之前的原生php5-redis扩展，如果数组中包含值为null的属性，会直接触发core dump
	 * 而5.5及之前的版本会自动发生强转为''
	 * @param array $data
	 * @return array
	 */
	protected function _serializeData(&$data){
		foreach($data as $key => $value)
			if ($value === null)
				$data[$key] = '';
		return $data;
	}
}
