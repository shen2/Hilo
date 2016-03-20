<?php
namespace Hilo;

trait ImplodeSerializer{
    /**
     * 
     * @param array $data
     * @return string
     */
    protected function serialize(&$data){
        return \implode(',', $data);
    }
    
    /**
     * 
     * @param string $data
     * @return array
     */
    public function unpackFromCache($data, $key){
        if (empty($data))
            return [];
        
        return \explode(',', $data);
    }
}
