<?php

namespace AhmedEbead\LaraMultiAuth\Facades;

use Illuminate\Support\Facades\Facade;

class LaraMultiAuth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'auth.service';
    }
}
