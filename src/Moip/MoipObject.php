<?php

namespace Potelo\MoPayment\Moip;

class MoipObject
{
    /**
     * Converts a response from the Moip API to the corresponding PHP object.
     *
     * @param $values
     * @return MoipObject|array
     */
    public static function convertToMoipObject($values)
    {
        $types = array(
            'plans',
            'customers',
            'subscriptions',
            'invoices',
        );

        if(in_array(key($values), $types)) {
            $mapped = array();
            foreach ($values->{key($values)} as $value) {
                array_push($mapped, self::convertToMoipObject($value));
            }

            return $mapped;
        } else {
            $object_type = get_called_class();
            $instance = new $object_type();
            foreach ($values as $k => $param) {
                $instance->{$k} = $param;
            }

            return $instance;
        }
    }
}