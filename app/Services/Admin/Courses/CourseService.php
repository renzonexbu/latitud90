<?php

namespace App\Services\Admin\Courses;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Program;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class CourseService
{
    public function createCourse(array $data): Course
    {
        return DB::transaction(function () use ($data) {
            $course = new Course([
                'institution_id' => $data['institutionId'],
                'education_level' => $data['educationLevel'],
                'grade' => $data['grade'] ?? null,
                'year' => $data['year'],
                'course_number' => $data['courseNumber'] ?? null,
                'course_name' => $data['courseName'] ?? null,
                'contact_email' => $data['contactEmail'] ?? null,
                'contact_phone' => $data['contactPhone'] ?? null,
                'end_date' => $data['endDate'] ?? null,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            if (isset($data['studentsFile']) && $data['studentsFile'] instanceof UploadedFile) {
                $path = $data['studentsFile']->store('students', 'public');
                $course->students_file_path = $path;
            }

            $course->save();

            return $course;
        });
    }

    public function updateCourse(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {
            $course->update([
                'institution_id' => $data['institutionId'],
                'education_level' => $data['educationLevel'],
                'grade' => $data['grade'] ?? null,
                'year' => $data['year'],
                'course_number' => $data['courseNumber'] ?? null,
                'course_name' => $data['courseName'] ?? null,
                'contact_email' => $data['contactEmail'] ?? $course->contact_email,
                'contact_phone' => $data['contactPhone'] ?? $course->contact_phone,
                'program_id' => $data['associatedProgram'],
                'end_date' => $data['endDate'],
            ]);

            if (isset($data['studentsFile']) && $data['studentsFile'] instanceof UploadedFile) {
                // Delete old file if exists
                if ($course->students_file_path) {
                    Storage::disk('public')->delete($course->students_file_path);
                }
                
                $path = $data['studentsFile']->store('students', 'public');
                $course->students_file_path = $path;
                $course->save();
            }

            return $course->fresh();
        });
    }

    public function deleteCourse(Course $course): bool
    {
        try {
            if ($course->students_file_path) {
                Storage::disk('public')->delete($course->students_file_path);
            }
            
            return $course->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting course', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    public function toggleStatus(Course $course): bool
    {
        try {
            $course->update([
                'status' => $course->status === 'active' ? 'inactive' : 'active'
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error toggling course status', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    public function regenerateProgramName(Course $course): void
    {
        try {
            $program = $course->program;
            if (!$program) {
                return;
            }

            $institutionName = $course->institution?->name;
            $level = $this->mapEducationLevel($course->education_level);
            $num = $course->course_number;
            $grade = $course->grade;

            $coursePart = $num ? $num . '° ' . $level : $level;
            if ($grade) {
                $coursePart .= ' ' . strtoupper($grade);
            }

            $destination = $program->destination;
            $year = $program->departure_date ? (int) date('Y', strtotime($program->departure_date)) : $program->year;

            if ($institutionName && $coursePart && $destination && $year) {
                $newName = sprintf('%s - %s - %s - %d', 
                    $institutionName, 
                    $coursePart, 
                    $destination, 
                    $year
                );
                
                $program->update(['name' => $newName]);
            }
        } catch (\Exception $e) {
            Log::error('Error regenerating program name', [
                'course_id' => $course->id,
                'program_id' => $course->program_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function mapEducationLevel(string $level): string
    {
        return match ($level) {
            'primaria', 'primario', 'basica' => 'basica',
            'secundaria', 'secundario', 'media' => 'media',
            'preescolar' => 'preescolar',
            'universitaria', 'universitario' => 'universitaria',
            default => $level,
        };
    }
}
