<?php

namespace App\Services\Admin\Reports\DailyPayments;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Traits\AdminLogging;

class DailyPaymentsService
{
    use AdminLogging;
    public function __construct(
        private DailyPaymentsDataProvider $dataProvider,
        private DailyPaymentsFilters $filters,
        private DailyPaymentsTransformer $transformer
    ) {}

    public function getDailyPayments(array $filters, int $page = 1, int|string $perPage = 25): LengthAwarePaginator
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $data = $this->dataProvider->getDailyPayments($query, $page, $perPage);
        $transformed = $this->transformer->transformForView($data);
        
        // Log the daily payments report view
        $this->logView(
            'reports',
            'DailyPayments',
            0, // No specific resource ID for list views
            "Reporte de pagos diarios consultado - Página {$page}",
            [
                'filters' => $filters,
                'page' => $page,
                'total_records' => $transformed->total(),
                'per_page' => $transformed->perPage(),
                'report_type' => 'daily_payments',
            ]
        );
        
        return $transformed;
    }

    public function getAllDailyPayments(array $filters, array $selectedFields = []): Collection
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $data = $this->dataProvider->getAllDailyPayments($query);
        $transformed = $this->transformer->transformForExport($data, $selectedFields);

        // Log the daily payments export - sin URL para evitar problemas con URLs largas
        $this->logExportWithoutUrl(
            'reports',
            "Exportación de pagos diarios generada",
            [
                'filters' => $filters,
                'selected_fields' => $selectedFields,
                'total_records' => $transformed->count(),
                'report_type' => 'daily_payments_export',
            ]
        );

        return $transformed;
    }

    public function getSummary(array $filters): array
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $summary = $this->dataProvider->getSummary($query);
        
        return $summary;
    }

    public function getPrograms(): Collection
    {
        return $this->dataProvider->getPrograms();
    }

    public function getSalesExecutives(): Collection
    {
        return $this->dataProvider->getSalesExecutives();
    }

    public function getFinancingTypes(): array
    {
        return $this->dataProvider->getFinancingTypes();
    }

    public function getPaymentMethods(): Collection
    {
        return $this->dataProvider->getPaymentMethods();
    }

    public function getDefaultDateRange(): array
    {
        return $this->dataProvider->getDefaultDateRange();
    }

    public function getData(array $filters): array
    {
        $page = request()->get('page', 1);
        $perPage = isset($filters['perPage']) && $filters['perPage'] === 'all' ? 'all' : (int) ($filters['perPage'] ?? 25);

        Log::info('🔍 DailyPaymentsService - Obteniendo datos con filtros:', $filters);
        Log::info('📄 DailyPaymentsService - Página solicitada:', ['page' => $page, 'perPage' => $perPage]);

        $dailyPayments = $this->getDailyPayments($filters, $page, $perPage);
        $summary = $this->getSummary($filters);
        $programs = $this->getPrograms();
        $salesExecutives = $this->getSalesExecutives();
        $financingTypes = $this->getFinancingTypes();
        $paymentMethods = $this->getPaymentMethods();
        $defaultDateRange = $this->getDefaultDateRange();

        Log::info('📊 DailyPaymentsService - Datos generados:', [
            'dailyPayments_count' => $dailyPayments->count(),
            'dailyPayments_total' => $dailyPayments->total(),
            'summary' => $summary,
            'programs_count' => $programs->count(),
            'salesExecutives_count' => $salesExecutives->count(),
            'financingTypes_count' => count($financingTypes),
            'paymentMethods_count' => $paymentMethods->count()
        ]);

        return [
            'dailyPayments' => $dailyPayments,
            'summary' => $summary,
            'programs' => $programs,
            'salesExecutives' => $salesExecutives,
            'financingTypes' => $financingTypes,
            'paymentMethods' => $paymentMethods,
            'defaultDateRange' => $defaultDateRange,
        ];
    }

    /**
     * Log an export action without URL to avoid truncation issues
     */
    protected function logExportWithoutUrl(
        string $module,
        ?string $description = null,
        ?array $additionalData = null
    ): void {
        $user = auth()->user();
        
        \App\Models\AdminLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'action' => 'export',
            'module' => $module,
            'resource_type' => null,
            'resource_id' => null,
            'description' => $description ?? "Exportación de datos",
            'old_values' => null,
            'new_values' => null,
            'additional_data' => $additionalData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_method' => request()->method(),
            'request_url' => null, // Explícitamente null para evitar URLs largas
            'request_data' => $this->sanitizeRequestData(request()->all()),
        ]);
    }

    /**
     * Sanitize request data to remove sensitive information
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            '_token',
            'api_token',
            'remember_token',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***HIDDEN***';
            }
        }

        return $data;
    }
}
