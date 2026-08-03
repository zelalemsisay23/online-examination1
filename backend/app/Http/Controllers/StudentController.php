<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('user')
            ->when($request->search, fn($q, $s) =>
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%"))
            );

        $students = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json($students);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'student_id' => 'nullable|string|unique:students,student_id',
            'phone'      => 'nullable|string|max:20',
            'address'    => 'nullable|string',
            'gender'     => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
        ]);

        $student = DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'student',
            ]);

            return Student::create([
                'user_id'       => $user->id,
                'student_id'    => $request->student_id,
                'phone'         => $request->phone,
                'address'       => $request->address,
                'gender'        => $request->gender,
                'date_of_birth' => $request->date_of_birth,
            ]);
        });

        return response()->json($student->load('user'), 201);
    }

    public function show(Student $student)
    {
        return response()->json($student->load('user'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name'       => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $student->user_id,
            'password'   => 'nullable|string|min:8',
            'student_id' => 'nullable|string|unique:students,student_id,' . $student->id,
            'phone'      => 'nullable|string|max:20',
            'address'    => 'nullable|string',
            'gender'     => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'is_active'  => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($request, $student) {
            $userUpdates = array_filter([
                'name'      => $request->name,
                'email'     => $request->email,
                'is_active' => $request->is_active,
            ], fn($v) => !is_null($v));

            if ($request->filled('password')) {
                $userUpdates['password'] = Hash::make($request->password);
            }

            if (!empty($userUpdates)) {
                $student->user->update($userUpdates);
            }

            $student->update($request->only(['student_id', 'phone', 'address', 'gender', 'date_of_birth']));
        });

        return response()->json($student->fresh()->load('user'));
    }

    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student) {
            $userId = $student->user_id;
            $student->delete();
            User::destroy($userId);
        });

        return response()->json(['message' => 'Student deleted successfully.']);
    }
}
