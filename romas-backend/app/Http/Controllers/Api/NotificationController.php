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
    public function markOneAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->lu = true;
        $notification->save();
        return response()->json(['message' => 'Notification marquée comme lue.']);
    }
}