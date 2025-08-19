<?php

namespace App\Http\Controllers\Administrator; // Lokasi disesuaikan

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TokenController extends Controller
{
    public function index()
    {
        $page = "Manajemen Token Langganan";

        $plans = SubscriptionPlan::all();
        $tokens = SubscriptionToken::with('plan', 'usedBy')->latest()->paginate(20);

        // Render view konten ke dalam variabel $content
        $content = view('admin.page.tokens.content', compact('plans', 'tokens'))->render();

        // Tampilkan menggunakan layout blank_page
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Method ini hanya me-redirect, jadi tidak perlu diubah.
     */
    public function store(Request $request)
    {
        $request->validate(['plan_id' => 'required|exists:subscription_plans,id']);

        $token = SubscriptionToken::create([
            'subscription_plan_id' => $request->plan_id,
            'token' => 'TOKEN-' . strtoupper(Str::random(12)) . '-' . time(),
            'created_by_user_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Token berhasil dibuat: ' . $token->token);
    }
}
