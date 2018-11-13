<?php

namespace Grechanyuk\YandexKassy\Facades;

use Illuminate\Support\Facades\Facade;

class YandexKassy extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'yandexkassy';
    }
}
