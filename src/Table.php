<?php
namespace JezoPool;
use MDO;

class Table extends Base{
	
	use HashTrait;
	
	protected $_cacheFields = null;
	
	protected $_primary = null;
	
	/**
	 * 
	 * @var \MDO\Table
	 */
	protected $_table = null;
	
	/**
	 * 
	 */
	public function __construct($table = null){
		if ($table instanceof \MDO\Table){
			$this->_table = $table;
		}
		elseif (is_string($table)){
			$this->_table = new $table();
		}
		else{
			$this->_table = new $this->_tableClass();
		}
	}
	
	/**
	 * 
	 * @param \MDO\Table $table
	 */
	public function setTable($table){
		$this->_table = $table;
	}
	
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
		
		$rowClass = $this->_table->getRowClass();
		return new $rowClass($data, $this->_table, true, true);
	}
	
	/**
	 * 从数据库中读取记录
	 */
	protected function _fetchMulti($keys){
		$cols = $this->_cacheFields
			? array_merge(array($this->_primary), $this->_cacheFields)
			: array($this->_primary, '*');
		
		return $this->_table->selectCol($cols)
			->where($this->_primary .' in (?)', $keys)
			->query()
			->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
	}
}
