<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login()
    {
        return view('');
    }
    public function submit_login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (auth()->attempt($request->only('email', 'password'))) {
            $token = auth()->user()->createToken('API Token')->accessToken;

            return response()->json(['token' => $token]);
        }

        return response()->json(['error' => 'Unauthenticated'], 401);
    }
    public function register()
    {
        return view('');
    }
    public function submit_register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        // return $request;
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'User registered successfully!', 'user' => $user], 201);
    }

    public function logout(Request $request){
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function user(Request $request){
        return response()->json($request->user());
    }
}