<?php
/**
 * Created by PhpStorm.
 * User: funson
 * Date: 2016/1/13
 * Time: 17:26
 */

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider as BaseEloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class EloquentUserProvider extends BaseEloquentUserProvider
{
    public function __construct($model)
    {
        $this->model = $model;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];
        $authPassword = $user->getAuthPassword();
        return $authPassword == md5($plain);
    }
}
