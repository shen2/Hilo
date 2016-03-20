<?php
namespace Hilo;

trait Serializer{
    /**
     *
     * @param array $data
     * @return string
     */
    protected function serialize(&$data){
        return \serialize($data);
    }

    /**
     *
     * @param string $data
     * @return array
     */
    public function unpackFromCache($data, $key){
        if (empty($data))
            return [];

        return \unserialize($data);
    }
}
