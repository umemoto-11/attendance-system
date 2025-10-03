<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;

class CustomLoginController extends Controller
{
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        return redirect()->intended($request->isAdminLogin() ? '/admin/attendances' : '/attendance');
    }
}
