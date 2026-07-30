<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        return response()->json(Auth::user()->notifications()->orderBy('created_at', 'desc')->get());
    }
    public function markAsRead()
    {
        Auth::user()->notifications()->where('lu', false)->update(['lu' => true]);
        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.']);
    }
}