<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $search = $request->query('search');
        $sortBy = $request->query('sortBy', 'ASC');
        $orderBy = $request->query('orderBy', 'name');
        $filter = ['name', 'iso2', 'iso3'];
        $clean = array_filter($filter, function ($var) use ($orderBy) {
            return $var === $orderBy;
        });

        $country = Country::query();

        $country = $country->where('is_supported', true);

        $country = $country->select(['id', 'name', 'iso2'])->orderBy($orderBy, $sortBy)->get();

        return response()->json([
            'message'   => '',
            'status'    => 200,
            'result'    => [
                'countries' => $country,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Country $country)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        //
    }
}
