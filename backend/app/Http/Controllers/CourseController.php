<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::with('instructor.user')
            ->when($request->search, fn($q, $s) =>
                $q->where('title', 'like', "%$s%")->orWhere('code', 'like', "%$s%")
            )
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($courses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'code'          => 'required|string|max:50|unique:courses,code',
            'description'   => 'nullable|string',
            'instructor_id' => 'nullable|exists:instructors,id',
            'is_active'     => 'boolean',
        ]);

        $course = Course::create($request->only(['title', 'code', 'description', 'instructor_id', 'is_active']));

        return response()->json($course->load('instructor.user'), 201);
    }

    public function show(Course $course)
    {
        return response()->json($course->load(['instructor.user', 'exams']));
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'title'         => 'sometimes|string|max:255',
            'code'          => 'sometimes|string|max:50|unique:courses,code,' . $course->id,
            'description'   => 'nullable|string',
            'instructor_id' => 'nullable|exists:instructors,id',
            'is_active'     => 'sometimes|boolean',
        ]);

        $course->update($request->only(['title', 'code', 'description', 'instructor_id', 'is_active']));

        return response()->json($course->fresh()->load('instructor.user'));
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully.']);
    }
}
