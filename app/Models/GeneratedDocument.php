<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class GeneratedDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'generated_documents';

    protected $fillable = [
        'document_type',
        'file_path',
        'file_name',
        'original_name',
        'file_size',
        'payment_id',
        'order_detail_id',
        'participant_id',
        'program_id',
        'email_sent',
        'email_sent_at',
        'email_sent_to',
        'email_send_count',
        'generated_by',
        'generated_from',
        'metadata',
    ];

    protected $casts = [
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'email_send_count' => 'integer',
        'file_size' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Constantes para tipos de documento
    const TYPE_PAYMENT_RECEIPT = 'payment_receipt';
    const TYPE_CONTRACT = 'contract';
    const TYPE_BSALE_INVOICE = 'bsale_invoice';

    // Constantes para orígenes de generación
    const SOURCE_PAYMENT_CONFIRMATION = 'payment_confirmation';
    const SOURCE_MANUAL = 'manual';
    const SOURCE_ADMIN = 'admin';
    const SOURCE_RESEND = 'resend';

    /**
     * Relaciones
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function program()
    {
        return $this->belongsTo(ProgramCourse::class, 'program_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Scopes
     */
    public function scopePaymentReceipts($query)
    {
        return $query->where('document_type', self::TYPE_PAYMENT_RECEIPT);
    }

    public function scopeContracts($query)
    {
        return $query->where('document_type', self::TYPE_CONTRACT);
    }

    public function scopeBsaleInvoices($query)
    {
        return $query->where('document_type', self::TYPE_BSALE_INVOICE);
    }

    public function scopeEmailSent($query)
    {
        return $query->where('email_sent', true);
    }

    public function scopeEmailPending($query)
    {
        return $query->where('email_sent', false);
    }

    public function scopeForParticipant($query, $participantId)
    {
        return $query->where('participant_id', $participantId);
    }

    public function scopeForProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeForPayment($query, $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    /**
     * Métodos helper
     */
    public function getFullPath()
    {
        return storage_path('app/' . $this->file_path);
    }

    public function exists()
    {
        return Storage::exists($this->file_path);
    }

    public function getDownloadUrl()
    {
        return route('admin.documents.download', $this->id);
    }

    public function getFileSizeFormatted()
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getTypeLabel()
    {
        $labels = [
            self::TYPE_PAYMENT_RECEIPT => 'Comprobante de Pago',
            self::TYPE_CONTRACT => 'Contrato de Reserva',
            self::TYPE_BSALE_INVOICE => 'Boleta Bsale',
        ];

        return $labels[$this->document_type] ?? $this->document_type;
    }

    public function markAsEmailSent($email, $incrementCount = true)
    {
        $this->email_sent = true;
        $this->email_sent_at = now();
        $this->email_sent_to = $email;

        if ($incrementCount) {
            $this->email_send_count++;
        }

        $this->save();
    }

    /**
     * Método estático para crear un nuevo documento registrado
     */
    public static function createDocument(
        string $documentType,
        string $filePath,
        string $fileName,
        array $relations = [],
        array $metadata = []
    ) {
        $fileSize = Storage::exists($filePath) ? Storage::size($filePath) : null;

        return self::create([
            'document_type' => $documentType,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'payment_id' => $relations['payment_id'] ?? null,
            'order_detail_id' => $relations['order_detail_id'] ?? null,
            'participant_id' => $relations['participant_id'] ?? null,
            'program_id' => $relations['program_id'] ?? null,
            'generated_by' => $relations['generated_by'] ?? auth()->id(),
            'generated_from' => $relations['generated_from'] ?? self::SOURCE_MANUAL,
            'metadata' => $metadata,
        ]);
    }
}
