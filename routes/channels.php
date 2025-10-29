<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log; // Import Log untuk debugging (opsional)

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Existing Channel (Payment Verified) - Ini sudah ada di kode Anda
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});


Broadcast::channel('qris-mutations.{ownerId}.{kasirId}', function ($user, $ownerId, $kasirId) {
    Log::info('--- Pusher Authorization Check ---');
    Log::info("Channel: qris-mutations.{$ownerId}.{$kasirId}");

    // **DEBUG: Cek Token API (Authentication)**
    if (!$user) {
        Log::warning('Pusher Auth DENIED (401/500): User is NULL. API Token failed to authenticate.');
        return false;
    }

    $loggedInDetailId = $user->id; // Mengambil ID dari model User yang terotentikasi.
    Log::info("Logged In User ID (from Token): {$loggedInDetailId}");

    // Otorisasi: Izinkan jika dia adalah owner atau kasir yang dituju.
    $isAuthorized = ((int) $loggedInDetailId === (int) $ownerId || (int) $loggedInDetailId === (int) $kasirId);

    if ($isAuthorized) {
        Log::info('Pusher Auth GRANTED (200): User is authorized.');
        return ['detail_id' => $loggedInDetailId];
    }

    // **DEBUG: Mismatch Logic (Authorization)**
    Log::warning('Pusher Auth DENIED (403): User ID Mismatch.', [
        'user_id' => $loggedInDetailId,
        'owner_id' => $ownerId,
        'kasir_id' => $kasirId,
    ]);

    return false;
});
