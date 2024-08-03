<?php

namespace AhmedEbead\LaraMultiAuth\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseAuthModel extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $guardName;

    public function __construct(array $attributes = [], $guard = null)
    {
        parent::__construct($attributes);
        $this->guardName = $guard ?: config('multiauth.default_guard');
    }

    public function guardName()
    {
        return $this->guardName;
    }

    public function setGuardName($guard)
    {
        $this->guardName = $guard;
    }
}
