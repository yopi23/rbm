<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccessoryStockResource;
use App\Models\AccessoryStock;
use Illuminate\Http\Request;

class AccessoryStockApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $user = $request->user();

        $stocks = AccessoryStock::visibleTo($user)
            ->where('stock', '>', 0)
            ->with(['screenSize', 'cameraPosition', 'category'])
            ->get();

        return AccessoryStockResource::collection($stocks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        $user = $request->user();

        // Validasi akses
        if ($user->level !== 'superadmin' &&
            $user->id !== $accessoryStock->upline_id &&
            !$user->downlines()->where('id', $accessoryStock->upline_id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new AccessoryStockResource($accessoryStock->load(['screenSize', 'cameraPosition', 'category']));

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): Response
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): Response
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): Response
    {
        //
    }
}
