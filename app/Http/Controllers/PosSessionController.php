<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PosSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    public function current()
    {
        return response()->json([
            'success' => true,
            'data' => null // Return active session if exists
        ]);
    }

    public function open(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => ['id' => 1, 'status' => 'open']
        ]);
    }

    public function close(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => ['id' => 1, 'status' => 'closed']
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
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
