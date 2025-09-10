<?php

namespace App\Services\Admin\Courses;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Program;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CourseDataService
{
    public function getCourseList(array $filters = [])
    {
        return Course::with(['program', 'createdBy', 'institution'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('institution', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhere('education_level', 'like', "%{$search}%")
                ->orWhere('course_name', 'like', "%{$search}%");
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Calculate metrics for a collection or paginator of courses
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator  $courses
     * @return \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator
     */
    public function calculateCourseMetrics($courses)
    {
        $transform = function ($course) {
            if ($course->program) {
                $course->program->makeVisible(['trip_price', 'name', 'destination']);
            }

            $participants = $course->participants ?? collect();
            $activeParticipants = $participants->filter(fn($p) => ($p->pivot->status ?? 'active') !== 'cancelled');
            
            $courseTotalAmount = $this->calculateTotalAmount($course, $activeParticipants);
            $coursePaidAmount = $this->calculatePaidAmount($course);
            $coursePaymentPercentage = $this->calculatePaymentPercentage($courseTotalAmount, $coursePaidAmount);

            $course->course_total_amount = $courseTotalAmount;
            $course->course_paid_amount = $coursePaidAmount;
            $course->course_payment_percentage = $coursePaymentPercentage;
            $course->append(['payment_percentage', 'payment_percentage_text']);
            
            return $course;
        };

        // Handle both Collection and LengthAwarePaginator
        if ($courses instanceof \Illuminate\Pagination\AbstractPaginator) {
            $courses->getCollection()->transform($transform);
            return $courses;
        }

        return $courses->map($transform);
    }

    private function calculateTotalAmount($course, $activeParticipants): float
    {
        return $activeParticipants->reduce(function ($carry, $p) use ($course) {
            if ($course->program) {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($p, $course->program);
                return $carry + $priceData['final_price'];
            }
            $base = (float) ($p->pivot->individual_price ?? $p->individual_price ?? 0);
            $adj = (float) ($p->pivot->price_adjustments ?? 0);
            return $carry + round($base + $adj, 2);
        }, 0.0);
    }

    private function calculatePaidAmount($course): float
    {
        if (!$course->program) {
            return 0.0;
        }

        return (float) DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.program_id', $course->program->id)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->sum('payments.amount');
    }

    private function calculatePaymentPercentage(float $total, float $paid): int
    {
        return $total > 0 ? (int) round(($paid / $total) * 100, 0) : 0;
    }

    public function getCourseForEdit(Course $course): array
    {
        $course->load(['institution', 'program', 'participants']);
        
        // Calculate payment metrics if program exists
        if ($course->program) {
            $course->program->makeVisible(['trip_price', 'name', 'destination']);
            
            // Calculate total amount from active participants
            $participants = $course->participants ?? collect();
            $activeParticipants = $participants->filter(fn($p) => ($p->pivot->status ?? 'active') !== 'cancelled');
            
            $courseTotalAmount = $activeParticipants->reduce(function ($carry, $p) use ($course) {
                if ($course->program) {
                    $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($p, $course->program);
                    return $carry + $priceData['final_price'];
                }
                $base = (float) ($p->pivot->individual_price ?? $p->individual_price ?? 0);
                $adj = (float) ($p->pivot->price_adjustments ?? 0);
                return $carry + round($base + $adj, 2);
            }, 0.0);

            // Calculate paid amount
            $coursePaidAmount = (float) \App\Models\Payment::whereHas('order', function($q) use ($course) {
                $q->where('program_id', $course->program->id);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->sum('amount');
            $coursePaidAmount = round($coursePaidAmount, 2);

            // Calculate payment percentage
            $coursePaymentPercentage = $courseTotalAmount > 0 
                ? round(($coursePaidAmount / $courseTotalAmount) * 100, 0)
                : 0;

            // Attach metrics to program
            $course->program->payment_percentage = $coursePaymentPercentage;
            $course->program->paid_amount = $coursePaidAmount;
            $course->program->total_amount = $courseTotalAmount > 0 ? $courseTotalAmount : ($course->program->trip_price ?? 0);
        }
        
        // Add calculated accessors
        $course->append(['payment_percentage', 'payment_percentage_text']);
        
        return [
            'course' => $course,
            'institution' => $course->institution,
            'program' => $course->program,
            'participants' => $course->participants
        ];
    }

    /**
     * Get header information for a course
     */
    public function getCourseHeaderInfo(Course $course): array
    {
        return [
            'institution_name' => $course->institution?->name ?? 'Sin institución',
            'year' => $course->year,
            'grade' => $course->course_display,
            'shift' => null,
            'education_level' => $course->education_level,
        ];
    }

    public function getFilteredPayments(Course $course, array $filters = [])
    {
        $query = \App\Models\Payment::query()
            ->with([
                'order.program.course.institution',
                'order.participant.documentType',
                'orderDetail.country',
                'orderDetail.region',
                'orderDetail.city',
                'paymentGateway',
                'paymentOption'
            ])
            ->whereHas('order.program', function($query) use ($course) {
                $query->where('id', $course->program_id);
            });

        if (!empty($filters['participant_name'])) {
            $query->whereHas('order.participant', function($q) use ($filters) {
                $q->where('first_name', 'like', '%' . $filters['participant_name'] . '%')
                  ->orWhere('last_name', 'like', '%' . $filters['participant_name'] . '%');
            });
        }

        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('status', $filters['payment_status']);
        }

        if (!empty($filters['program_id'])) {
            $query->whereHas('order.program', fn($q) => $q->where('id', $filters['program_id']));
        }

        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            if ($filters['payment_method'] === 'presencial') {
                $query->where(fn($q) => 
                    $q->whereHas('paymentOption', fn($sq) => $sq->where('gateway_code', 'presencial'))
                      ->orWhereNull('payment_option_id')
                );
            } else {
                $query->whereHas('paymentOption', fn($q) => 
                    $q->where('gateway_code', $filters['payment_method'])
                );
            }
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'page', $filters['page'] ?? 1);
    }
}
