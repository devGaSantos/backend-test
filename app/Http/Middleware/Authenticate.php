<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // o uso de espaçamento para a negação da variavel $request->expectsJson() foge do pradrão
        // creio também que a API não deve redirecionar, deve retornar 401
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
