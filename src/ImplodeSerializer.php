<?php
namespace JezoPool;

trait ImplodeSerializer{
	/**
	 * 
	 * @param array $data
	 * @return string
	 */
	protected function _serializeData(&$data){
		return implode(',', $data);
	}
	
	/**
	 * 
	 * @param string $data
	 * @return array
	 */
	protected function _unserializeData($data){
		if (empty($data))
			return [];
		
		return explode(',', $data);
	}
}
