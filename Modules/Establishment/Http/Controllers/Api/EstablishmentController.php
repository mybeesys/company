<?php

namespace Modules\Establishment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Transformers\Collections\EstablishmentCollection;

class EstablishmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $establishments = Establishment::all();
        return new EstablishmentCollection($establishments);
    }
}
