<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AuthRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index(AuthRequest $request) 
    {
        $validatedData = $request->validated();

        if(Auth::attempt(['email' => $validatedData['email'], 'password' => $validatedData['password']])){
            $user = Auth::user();
            $token = $user->createToken('token')->plainTextToken;
            return response()->json([
                'token' => $token,
                'success' => 'true',
            ], 200);
        }else{
            return response()->json([
                
            ], 401);
        }
    }
}
