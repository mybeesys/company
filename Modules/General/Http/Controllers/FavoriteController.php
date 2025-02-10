<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\General\Models\FavoriteBills;

class FavoriteController extends Controller
{
    public function toggle(Request $request)
{
    $transactionId = $request->transaction_id;
    $userId = Auth::user()->id;

    $favorite = FavoriteBills::where('transaction_id', $transactionId)->where('user_id', $userId)->first();

    if ($favorite) {
        $favorite->delete();
        return response()->json(['is_favorite' => false]);
    } else {
        FavoriteBills::create([
            'transaction_id' => $transactionId,
            'user_id' => $userId
        ]);
        return response()->json(['is_favorite' => true]);
    }
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('general::create');
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
        return view('general::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('general::edit');
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