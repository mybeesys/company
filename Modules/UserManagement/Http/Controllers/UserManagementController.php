<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Redis;

class UserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function login(Request $request)

     {
/*        Redis::set('name', 'John Doe'); 
            $name = Redis::get('name');
        dd($name);
*/
        // dd($request->email." ". $request->password." ".Auth::attempt(['email' => $request->email, 'password' => $request->password]));
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $token = auth()->user()->createToken('API Token')->accessToken;

            return redirect()->route('dashboard');
          // return response()->json(['token' => $token]);
        }
        return redirect()->route('dashboard');
     // return response()->json(['error' => 'Unauthenticated'], 401);
     }


    public function index()
    {
        return view('usermanagement::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('usermanagement::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('usermanagement::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('usermanagement::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
