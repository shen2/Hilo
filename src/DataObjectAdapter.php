<?php
namespace Hilo;

class DataObjectAdapter extends RedisHashAdapter{
    
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
     * @return array
     */
    public function fetchMulti($keys){
        $cols = $this->_cacheFields
            ? array_merge([$this->_primary], $this->_cacheFields)
            : [$this->_primary, '*'];
        
        $dataClass = $this->_dataClass;
        
        return $dataClass::getTable()->selectCol($cols)
            ->where($this->_primary .' in (?)', $keys)
            ->fetchAssocMap();
    }
    
    /**
     * 从数据库读出来之后，到内存中的转换过程
     * 
     * @param mixed $value
     * @param array $args
     * @return mixed
     */
    public function unpack(&$value, $key){
        if ($value === null)
            return null;
    
        $data = [$this->_primary => $key];    //    单一主键的时候退化到这种情况
    
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
}
