<?php


namespace App\Service\Gpt\Extension;


trait Arrayable
{
    /**
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }
}