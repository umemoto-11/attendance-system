<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse;

class CustomLogoutResponse implements LogoutResponse
{
    public function toResponse($request)
    {
        $user = auth()->user();

        if ($user && $user->role === 'admin') {
            return redirect('/admin/login');
        }

        return redirect('/login');
    }
}