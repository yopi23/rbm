<?php

namespace App\Traits;

use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;

trait HasOwnerScope
{
    
    /**
     * Get current user's owner code
     */
    public function getCurrentOwnerCode()
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $userDetail = UserDetail::where('kode_user', $user->id)->first();
        return $userDetail ? $userDetail->id_upline : null;
    }

    /**
     * Get current user detail (sama seperti getThisUser di original code)
     */
    public function getCurrentUserDetail()
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return UserDetail::where('kode_user', $user->id)->first();
    }

    /**
     * Compatibility method dengan existing code
     */
    public function getThisUser()
    {
        return $this->getCurrentUserDetail();
    }

    /**
     * Check if current user can access data for given owner
     */
    public function canAccessOwnerData($kodeOwner)
    {
        $currentOwner = $this->getCurrentOwnerCode();
        return $currentOwner === $kodeOwner;
    }

    /**
     * Get employees for current owner (same logic as original)
     */
    public function getCurrentOwnerEmployees()
    {
        $currentUserDetail = $this->getCurrentUserDetail();
        if (!$currentUserDetail) {
            return collect();
        }

        return \App\Models\User::join('user_details', 'users.id', '=', 'user_details.kode_user')
            ->where('user_details.id_upline', $currentUserDetail->id_upline)
            ->whereIn('user_details.jabatan', [2, 3]) // Kasir dan Teknisi
            ->get(['users.*', 'user_details.*', 'users.id as id_user']);
    }

    /**
     * Validate owner access for given user ID
     */
    public function validateUserOwnerAccess($userId)
    {
        $currentUserDetail = $this->getCurrentUserDetail();
        if (!$currentUserDetail) {
            return false;
        }

        $targetUserDetail = UserDetail::where('kode_user', $userId)->first();
        if (!$targetUserDetail) {
            return false;
        }

        return $currentUserDetail->id_upline === $targetUserDetail->id_upline;
    }

    /**
     * Apply owner filter to eloquent query
     */
    public function applyOwnerScope($query, $ownerColumn = 'kode_owner')
    {
        $ownerCode = $this->getCurrentOwnerCode();
        if ($ownerCode) {
            return $query->where($ownerColumn, $ownerCode);
        }
        return $query;
    }

    /**
     * Scope query to current user's owner only
     */
    public function scopeForCurrentOwner($query)
    {
        $ownerCode = $this->getCurrentOwnerCode();
        if ($ownerCode) {
            return $query->where('kode_owner', $ownerCode);
        }
        return $query;
    }
}
