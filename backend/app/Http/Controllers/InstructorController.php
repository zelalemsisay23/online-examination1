<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InstructorController extends Controller
{
    public function index(Request $request)
    {
        $instructors = Instructor::with('user')
            ->when($request->search, fn($q, $s) =>
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%"))
            )
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($instructors);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:8',
            'employee_id'    => 'nullable|string|unique:instructors,employee_id',
            'department'     => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'specialization' => 'nullable|string|max:255',
        ]);

        $instructor = DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'instructor',
            ]);

            return Instructor::create([
                'user_id'        => $user->id,
                'employee_id'    => $request->employee_id,
                'department'     => $request->department,
                'phone'          => $request->phone,
                'specialization' => $request->specialization,
            ]);
        });

        return response()->json($instructor->load('user'), 201);
    }

    public function show(Instructor $instructor)
    {
        return response()->json($instructor->load(['user', 'courses']));
    }

    public function update(Request $request, Instructor $instructor)
    {
        $request->validate([
            'name'           => 'sometimes|string|max:255',
            'email'          => 'sometimes|email|unique:users,email,' . $instructor->user_id,
            'password'       => 'nullable|string|min:8',
            'employee_id'    => 'nullable|string|unique:instructors,employee_id,' . $instructor->id,
            'department'     => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'is_active'      => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($request, $instructor) {
            $userUpdates = array_filter([
                'name'      => $request->name,
                'email'     => $request->email,
                'is_active' => $request->is_active,
            ], fn($v) => !is_null($v));

            if ($request->filled('password')) {
                $userUpdates['password'] = Hash::make($request->password);
            }

            if (!empty($userUpdates)) {
                $instructor->user->update($userUpdates);
            }

            $instructor->update($request->only(['employee_id', 'department', 'phone', 'specialization']));
        });

        return response()->json($instructor->fresh()->load('user'));
    }

    public function destroy(Instructor $instructor)
    {
        DB::transaction(function () use ($instructor) {
            $userId = $instructor->user_id;
            $instructor->delete();
            User::destroy($userId);
        });

        return response()->json(['message' => 'Instructor deleted successfully.']);
    }
}
