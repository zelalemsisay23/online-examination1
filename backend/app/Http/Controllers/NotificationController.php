<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($notifications);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)->findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Marked as read.']);
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * Broadcast a notification to all students (admin/instructor use).
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'type'    => 'in:info,success,warning,exam,result',
        ]);

        $students = \App\Models\User::where('role', 'student')->pluck('id');
        $data = [];
        foreach ($students as $uid) {
            $data[] = [
                'user_id'    => $uid,
                'title'      => $request->title,
                'message'    => $request->message,
                'type'       => $request->type ?? 'info',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Notification::insert($data);

        return response()->json(['message' => 'Notification sent to ' . count($data) . ' students.']);
    }
}
