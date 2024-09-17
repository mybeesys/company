<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if (auth()->attempt($request->only('email', 'password'))) {
        $token = auth()->user()->createToken('API Token')->accessToken;

        return response()->json(['token' => $token]);
    }

    return response()->json(['error' => 'Unauthenticated'], 401);
});

Route::middleware('auth:api')->post('/logout', function (Request $request) {
    $request->user()->token()->revoke();
    return response()->json(['message' => 'Successfully logged out']);
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return response()->json($request->user());
});

Route::post('/register', function (Request $request) {
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
});