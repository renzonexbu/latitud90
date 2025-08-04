<?php

namespace App\Services\Admin\Courses;

use App\Models\Course;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CreateCourseService
{
    public function execute(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Create the course
            $course = $this->createCourse($data);
            
            // Handle file upload if provided
            if (isset($data['students_file']) && $data['students_file']) {
                $this->handleFileUpload($course, $data['students_file']);
            }
            
            return $course;
        });
    }

    private function createCourse(array $data): Course
    {
        // Find the associated program if provided
        $programId = null;
        if (!empty($data['associatedProgram'])) {
            $program = Program::where('name', 'like', '%' . $data['associatedProgram'] . '%')->first();
            
            if (!$program) {
                throw new \Exception('Programa asociado no encontrado.');
            }
            
            $programId = $program->id;
        }

        return Course::create([
            'institution_id' => $data['institutionId'],
            'education_level' => $data['educationLevel'],
            'year' => $data['year'],
            'grade' => $data['grade'],
            'shift' => $data['shift'],
            'contact_email' => $data['contactEmail'],
            'contact_phone' => $data['contactPhone'],
            'program_id' => $programId,
            'end_date' => $data['endDate'] ?? null,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);
    }

    private function handleFileUpload(Course $course, $file): void
    {
        // Generate unique filename
        $filename = 'courses/' . $course->id . '/' . time() . '_' . $file->getClientOriginalName();
        
        // Store the file
        $path = Storage::disk('public')->put($filename, $file);
        
        if ($path) {
            // Update course with file path
            $course->update([
                'students_file_path' => $filename,
                'students_file_name' => $file->getClientOriginalName(),
            ]);
        }
    }
}
