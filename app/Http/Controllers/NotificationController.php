<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ? \App\Models\AppNotification::where('user_id', $request->user()->id)->latest()->paginate($this->perPage($request))->withQueryString()
            : collect();
        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Request $request)
    {
        \App\Models\AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')->update(['read_at' => now()]);
        return back();
    }
}
