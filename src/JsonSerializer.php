<?php
namespace JezoPool;

trait JsonSerializer{
	/**
	 * 
	 * @param array $data
	 * @return string
	 */
	protected function _serializeData(&$data){
		return json_encode($data);
	}
	
	/**
	 * 
	 * @param string $data
	 * @return array
	 */
	protected function _unserializeData($data){
		return json_decode($data);
	}
}
