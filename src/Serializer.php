<?php
namespace JezoPool;

trait Serializer{
    /**
     *
     * @param array $data
     * @return string
     */
    protected function _serializeData(&$data){
        return serialize($data);
    }

    /**
     *
     * @param string $data
     * @return array
     */
    protected function _unserializeData($data){
        if (empty($data))
            return [];

        return unserialize($data);
    }
}
