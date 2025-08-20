<?php

namespace App\Services\Admin\Reports\PartialReport;

use App\Helpers\ParticipantPriceHelper;
use App\Models\Participant;
use App\Models\Program;
use App\Models\SalesExecutive;
use Illuminate\Support\Collection;

class PartialAccountTransformer
{
    /**
     * Transforma las inscripciones para la vista principal
     */
    public function transformEnrollments(array $enrollments): Collection
    {
        return collect($enrollments)->map(function($enrollment) {
            return $this->transformEnrollment($enrollment);
        });
    }

    /**
     * Transforma las inscripciones para exportación
     */
    public function transformForExport(Collection $enrollments, array $selectedFields): Collection
    {
        $transformedData = $enrollments->map(function($enrollment) use ($selectedFields) {
            return $this->transformForExportRow($enrollment, $selectedFields);
        })->filter(function($row) {
            return $row !== null;
        });



        return $transformedData;
    }

    /**
     * Transforma una inscripción individual
     */
    private function transformEnrollment($enrollment): array
    {
        // Obtener datos relacionados
        $participant = Participant::find($enrollment->participant_id);
        $program = Program::find($enrollment->program_id);
        
        // Obtener ejecutivo de ventas
        $salesExecutiveName = $this->getSalesExecutiveName($enrollment);
        
        // Calcular datos financieros
        $financialData = $this->calculateFinancialData($participant, $program, $enrollment);
        
        return [
            'id' => $enrollment->participant_program_id,
            'participant_id' => $enrollment->participant_id,
            'program_id' => $enrollment->program_id,
            'created_at' => $enrollment->created_at,
            'participant_name' => $enrollment->first_last_name . ' ' . ($enrollment->second_last_name ? $enrollment->second_last_name . ' ' : '') . $enrollment->first_name . ' ' . ($enrollment->second_name ? $enrollment->second_name : ''),
            'participant_email' => $enrollment->email,
            'participant_document' => $enrollment->document_number,
            'participant_phone' => $enrollment->phone,
            'program_name' => $enrollment->program_name,
            'program_departure_date' => $enrollment->departure_date,
            'enrollment_code' => $enrollment->enrollment_code,
            'sales_executive_name' => $salesExecutiveName,
            'total_amount' => $financialData['total_amount'],
            'total_discounts' => $financialData['total_discounts'],
            'net_amount' => $financialData['net_amount'],
            'total_paid' => $financialData['total_paid'],
            'pending_amount' => $financialData['pending_amount'],
            'progress_percentage' => $financialData['progress_percentage'],
            'status' => $financialData['status'],
            'payment_history' => [], // TODO: Implementar si es necesario
            'upcoming_payments' => [], // TODO: Implementar si es necesario
            'discounts_detail' => [] // TODO: Implementar si es necesario
        ];
    }

    /**
     * Transforma una inscripción para exportación
     */
    private function transformForExportRow($enrollment, array $selectedFields): ?array
    {
        // Validar datos básicos
        if (!$enrollment || !isset($enrollment->participant_id) || !isset($enrollment->program_id)) {
            return null;
        }
        
        $participant = Participant::find($enrollment->participant_id);
        $program = Program::find($enrollment->program_id);
        $salesExecutiveName = $this->getSalesExecutiveName($enrollment);
        $financialData = $this->calculateFinancialData($participant, $program, $enrollment);
        
        $row = [];
        
        // Campos del participante
        if (isset($selectedFields['participant'])) {
            if (in_array('name', $selectedFields['participant'])) {
                $row['Nombre del Participante'] = $this->cleanUtf8(($enrollment->first_last_name ?? '') . ' ' . ($enrollment->second_last_name ?? '') . ' ' . ($enrollment->first_name ?? '') . ' ' . ($enrollment->second_name ?? ''));
            }
            if (in_array('email', $selectedFields['participant'])) {
                $row['Email'] = $this->cleanUtf8($enrollment->email ?? '');
            }
            if (in_array('document', $selectedFields['participant'])) {
                $row['Documento'] = $this->cleanUtf8($enrollment->document_number ?? '');
            }
            if (in_array('phone', $selectedFields['participant'])) {
                $row['Teléfono'] = $this->cleanUtf8($enrollment->phone ?? '');
            }
        }
        
        // Campos del programa
        if (isset($selectedFields['program'])) {
            if (in_array('name', $selectedFields['program'])) {
                $row['Nombre del Programa'] = $this->cleanUtf8($enrollment->program_name ?? '');
            }
            if (in_array('departureDate', $selectedFields['program'])) {
                $row['Fecha de Salida'] = $enrollment->departure_date ? date('d/m/Y', strtotime($enrollment->departure_date)) : '';
            }
            if (in_array('enrollmentCode', $selectedFields['program'])) {
                $row['Código de Inscripción'] = $this->cleanUtf8($enrollment->enrollment_code ?? '');
            }
            if (in_array('salesExecutive', $selectedFields['program'])) {
                $row['Ejecutivo de Ventas'] = $this->cleanUtf8($salesExecutiveName ?? 'N/A');
            }
        }
        
        // Campos financieros
        if (isset($selectedFields['financial'])) {
            if (in_array('totalAmount', $selectedFields['financial'])) {
                $row['Precio Total'] = $financialData['total_amount'];
            }
            if (in_array('discounts', $selectedFields['financial'])) {
                $row['Descuentos'] = $financialData['total_discounts'];
            }
            if (in_array('netAmount', $selectedFields['financial'])) {
                $row['Monto Neto'] = $financialData['net_amount'];
            }
            if (in_array('totalPaid', $selectedFields['financial'])) {
                $row['Total Pagado'] = $financialData['total_paid'];
            }
            if (in_array('pendingAmount', $selectedFields['financial'])) {
                $row['Saldo Pendiente'] = $financialData['pending_amount'];
            }
            if (in_array('progressPercentage', $selectedFields['financial'])) {
                $row['Progreso de Pago (%)'] = $financialData['progress_percentage'] / 100; // Convertir a decimal para Excel
            }
        }
        
        return $row;
    }

    /**
     * Obtiene el nombre del ejecutivo de ventas
     */
    private function getSalesExecutiveName($enrollment): ?string
    {
        $salesExecutiveName = $enrollment->sales_executive_name ?? null;
        
        if (!$salesExecutiveName && $enrollment->sales_executive_id) {
            $salesExecutive = SalesExecutive::find($enrollment->sales_executive_id);
            $salesExecutiveName = $salesExecutive ? $salesExecutive->name : null;
        }
        
        return $salesExecutiveName;
    }

    /**
     * Calcula los datos financieros del participante
     */
    private function calculateFinancialData($participant, $program, $enrollment): array
    {
        $priceData = null;
        if ($participant && $program) {
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
        }
        
        $totalAmount = $enrollment->individual_price ?? 0;
        $totalDiscounts = $priceData ? $priceData['discounts'] : 0;
        $netAmount = $priceData ? $priceData['final_price'] : $totalAmount;
        $totalPaid = $enrollment->paid_amount ?? 0;
        $pendingAmount = $netAmount - $totalPaid;
        
        // Determinar estado
        $status = 'pending';
        if ($pendingAmount <= 0) {
            $status = 'paid';
        } elseif ($pendingAmount < $netAmount) {
            $status = 'partial';
        }

        // Calcular porcentaje de avance
        $progressPercentage = $netAmount > 0 ? round(($totalPaid / $netAmount) * 100, 2) : 0;

        return [
            'total_amount' => $totalAmount,
            'total_discounts' => $totalDiscounts,
            'net_amount' => $netAmount,
            'total_paid' => $totalPaid,
            'pending_amount' => $pendingAmount,
            'progress_percentage' => $progressPercentage,
            'status' => $status,
        ];
    }

    /**
     * Limpia y normaliza caracteres UTF-8
     */
    private function cleanUtf8(string $string): string
    {
        if (empty($string)) {
            return $string;
        }


        
        // Detectar y convertir codificación si es necesario
        $encoding = mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1', 'Windows-1252']);
        
        if ($encoding && $encoding !== 'UTF-8') {
            $string = mb_convert_encoding($string, 'UTF-8', $encoding);

        }
        
        // Reemplazar directamente los caracteres mal codificados más comunes
        $string = str_replace(
            ['Ã©', 'Ã³', 'Ã­', 'Ã¡', 'Ãº', 'Ã±', 'Ã', 'Ã', 'Ã', 'Ã', 'Ã', 'Ã'],
            ['é', 'ó', 'í', 'á', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            $string
        );
        
        // Limpiar caracteres de control y normalizar
        $string = preg_replace('/[\x00-\x1F\x7F]/', '', $string);
        $string = trim($string);
        
        return $string;
    }
}
