<?php

namespace Adyen;

class Util
{

    public static function convertToObject($resp, $opts)
    {
        $models = array(
            'RecurringDetail' => 'Adyen\\Model\\RecurringDetail'
        );

    }

}