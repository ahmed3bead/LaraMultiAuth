<?php

namespace AhmedEbead\LaraMultiAuth\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Config;
use Laravel\Passport\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseAuthModel extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;

}
