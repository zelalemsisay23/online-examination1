<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')
            ->when($request->user_id, fn($q, $id) => $q->where('user_id', $id))
            ->when($request->action,  fn($q, $a)  => $q->where('action', 'like', "%$a%"))
            ->when($request->date,    fn($q, $d)  => $q->whereDate('created_at', $d));

        $logs = $query->latest()->paginate($request->per_page ?? 30);

        return response()->json($logs);
    }

    /**
     * Log an activity (called from frontend for client-side events like anti-cheat violations).
     */
    public function store(Request $request)
    {
        $request->validate([
            'action'  => 'required|string|max:100',
            'detail'  => 'nullable|string|max:500',
            'context' => 'nullable|array',
        ]);

        ActivityLog::create([
            'user_id'    => $request->user()->id,
            'action'     => $request->action,
            'detail'     => $request->detail,
            'context'    => $request->context ? json_encode($request->context) : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Logged.'], 201);
    }
}
