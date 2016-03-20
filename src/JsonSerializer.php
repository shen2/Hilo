<?php
namespace Hilo;

trait JsonSerializer{
	/**
	 * 
	 * @param array $data
	 * @return string
	 */
	protected function serialize(&$data){
		return \json_encode($data);
	}
	
	/**
	 * 
	 * @param string $data
	 * @return array
	 */
	public function unpackFromCache($data, $key){
		return \json_decode($data);
	}
}
