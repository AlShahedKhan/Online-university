<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Result;
use App\Models\Student;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResultController extends Controller
{
    // Method for professor to update results
    public function assignResult(Request $request, $student_id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'result' => 'required|string',
            'remarks' => 'nullable|string',
            'topic' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::find($student_id);
        $course = Course::find($request->course_id);

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        $result = Result::updateOrCreate(
            ['student_table_id' => $student_id, 'course_id' => $request->course_id],
            [
                'result' => $request->result,
                'remarks' => $request->remarks,
                'topic' => $request->topic,
                'date' => now(),
            ]
        );

        return response()->json([
            'message' => 'Result assigned successfully',
            'result' => $result,
            'student_first_name' => $student->first_name,
            'student_last_name' => $student->last_name,
        ]);
    }
}
