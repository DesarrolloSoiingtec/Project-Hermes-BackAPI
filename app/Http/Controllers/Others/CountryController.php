<?php

namespace App\Http\Controllers\Others;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Other\Country;


class CountryController extends Controller
{
    public function getCountriesCodes(Request $request)
    {
        $countries = Country::select('name','iso2', 'prefix')
        ->get();
        Log::info('Datos del request:', $countries->all());

        return response()->json($countries, 200);
    }
}
