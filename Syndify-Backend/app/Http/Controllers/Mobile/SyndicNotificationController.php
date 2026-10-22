<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SyndicNotificationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $notifications = $user->notifications()->latest()->get()->map(function($notif) {
                return [
                    'id' => $notif->id,
                    'titre' => $notif->data['titre'] ?? 'Notification',
                    'message' => $notif->data['message'] ?? '',
                    'type' => $notif->data['type'] ?? 'info',
                    'lu' => $notif->read_at !== null,
                    'date_humaine' => $notif->created_at->locale('fr')->diffForHumans(),
                    'date' => $notif->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json(['success' => true, 'data' => $notifications], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function markAsRead(Request $request, $id)
    {
        try {
            $notification = $request->user()->notifications()->where('id', $id)->first();
            
            if ($notification) {
                $notification->markAsRead();
            }
            
            return response()->json(['success' => true, 'message' => 'Notification marquée comme lue.']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}