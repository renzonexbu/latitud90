<?php

namespace App\Services\Admin\Reports\PartialReport;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PartialAccountDataProvider
{
    /**
     * Obtiene las inscripciones paginadas
     */
    public function getEnrollments(Builder $query, int $page = 1, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        // Apply participant search filter if provided
        if (!empty($filters['participantSearch'])) {
            $searchTerm = '%' . $filters['participantSearch'] . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('p.first_name', 'LIKE', $searchTerm)
                  ->orWhere('p.first_last_name', 'LIKE', $searchTerm)
                  ->orWhere('p.second_last_name', 'LIKE', $searchTerm)
                  ->orWhereRaw("CONCAT(p.first_name, ' ', p.first_last_name) LIKE ?", [$searchTerm])
                  ->orWhereRaw("CONCAT(p.first_name, ' ', p.first_last_name, ' ', p.second_last_name) LIKE ?", [$searchTerm]);
            });
        }

        $result = $query
            ->groupBy([
                'p.id', 'p.first_last_name', 'p.second_last_name', 'p.first_name', 'p.second_name', 'p.email', 'p.document_number', 'p.phone',
                'pp.id', 'pp.enrollment_code', 'pp.individual_price', 'pp.status', 'pp.created_at',
                'pgc.id', 'pgc.departure_date', 'pgc.sales_executive_id', 'pgc.name',
                'pr.id',
                'c.education_level', 'c.course_number',
                'i.name',
            ])
            ->select([
                'p.id as participant_id',
                'p.first_last_name',
                'p.second_last_name',
                'p.first_name',
                'p.second_name',
                'p.email',
                'p.document_number',
                'p.phone',
                'pp.id as participant_program_id',
                'pp.enrollment_code',
                'pp.individual_price',
                'pp.status as enrollment_status',
                'pp.created_at',
                'pgc.id as program_course_id',
                'pgc.departure_date',
                'pgc.sales_executive_id',
                'pr.id as program_id',
                'pgc.name as program_name',
                'c.education_level',
                'c.course_number',
                'i.name as institution_name',
                DB::raw('COALESCE(SUM(pay.amount), 0) as paid_amount'),
            ])
            ->orderByDesc('pp.created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return $result;
    }

    /**
     * Obtiene todas las inscripciones sin paginación
     */
    public function getAllEnrollments(Builder $query): Collection
    {
        $result = $query
            ->groupBy([
                'p.id', 'p.first_last_name', 'p.second_last_name', 'p.first_name', 'p.second_name', 'p.email', 'p.document_number', 'p.phone',
                'pp.id', 'pp.enrollment_code', 'pp.individual_price', 'pp.status', 'pp.created_at',
                'pgc.id', 'pgc.departure_date', 'pgc.sales_executive_id', 'pgc.name',
                'pr.id',
                'c.education_level', 'c.course_number',
                'i.name',
            ])
            ->select([
                'p.id as participant_id',
                'p.first_last_name',
                'p.second_last_name',
                'p.first_name',
                'p.second_name',
                'p.email',
                'p.document_number',
                'p.phone',
                'pp.id as participant_program_id',
                'pp.enrollment_code',
                'pp.individual_price',
                'pp.status as enrollment_status',
                'pp.created_at',
                'pgc.id as program_course_id',
                'pgc.departure_date',
                'pgc.sales_executive_id',
                'pr.id as program_id',
                'pgc.name as program_name',
                'c.education_level',
                'c.course_number',
                'i.name as institution_name',
                DB::raw('COALESCE(SUM(pay.amount), 0) as paid_amount'),
            ])
            ->orderByDesc('pp.created_at')
            ->get();

        return $result;
    }

    /**
     * Construye la consulta base para las inscripciones
     */
    public function buildBaseQuery(): Builder
    {
        return DB::table('participant_program as pp')
            ->join('participants as p', 'p.id', '=', 'pp.participant_id')
            ->join('program_courses as pgc', 'pgc.id', '=', 'pp.program_id')
            ->join('programs as pr', 'pr.id', '=', 'pgc.program_id')
            ->leftJoin('courses as c', 'c.id', '=', 'pgc.course_id')
            ->leftJoin('institutions as i', 'i.id', '=', 'c.institution_id')
            ->leftJoin('orders as o', function($join) {
                $join->on('o.participant_id', '=', 'p.id')
                     ->on('o.program_id', '=', 'pgc.id')
                     ->whereIn('o.status', ['completed', 'paid', 'approved']);
            })
            ->leftJoin('payments as pay', function($join) {
                $join->on('pay.order_id', '=', 'o.id')
                     ->whereIn('pay.status', ['completed', 'paid', 'approved'])
                     ->where('pay.amount', '>', 0);
            })
            ->whereNotNull('pp.participant_id')
            ->whereNotNull('pp.program_id');
    }
}
