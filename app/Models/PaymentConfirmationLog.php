<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentConfirmationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'order_detail_id',
        'order_id',
        'event_type',
        'status',
        'details',
        'error_message',
        'file_path',
        'file_name',
        'bsale_document_id',
        'bsale_number',
        'email_recipient',
        'email_attachments',
        'triggered_by_user_id',
    ];

    protected $casts = [
        'details' => 'array',
        'email_attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    // Scopes
    public function scopeByPayment($query, $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Métodos estáticos para registrar eventos
    public static function logPaymentReceiptGenerated($payment, $orderDetail, $filePath, $fileName, $details = [])
    {
        try {
            return static::create([
                'payment_id' => $payment->id ?? null,
                'order_detail_id' => $orderDetail->id ?? null,
                'order_id' => $orderDetail->order_id ?? null,
                'event_type' => 'payment_receipt_generated',
                'status' => 'success',
                'file_path' => $filePath,
                'file_name' => $fileName,
                'details' => $details,
            ]);
        } catch (\Exception $e) {
            \Log::error('PaymentConfirmationLog: Error logging payment receipt generated', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public static function logContractGenerated($payment, $orderDetail, $filePath, $fileName, $details = [])
    {
        try {
            return static::create([
                'payment_id' => $payment->id ?? null,
                'order_detail_id' => $orderDetail->id ?? null,
                'order_id' => $orderDetail->order_id ?? null,
                'event_type' => 'contract_generated',
                'status' => 'success',
                'file_path' => $filePath,
                'file_name' => $fileName,
                'details' => $details,
            ]);
        } catch (\Exception $e) {
            \Log::error('PaymentConfirmationLog: Error logging contract generated', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public static function logBsaleInvoiceGenerated($payment, $orderDetail, $bsaleId, $bsaleNumber, $filePath = null, $details = [])
    {
        try {
            return static::create([
                'payment_id' => $payment->id ?? null,
                'order_detail_id' => $orderDetail->id ?? null,
                'order_id' => $orderDetail->order_id ?? null,
                'event_type' => 'bsale_invoice_generated',
                'status' => 'success',
                'bsale_document_id' => $bsaleId,
                'bsale_number' => $bsaleNumber,
                'file_path' => $filePath,
                'details' => $details,
            ]);
        } catch (\Exception $e) {
            \Log::error('PaymentConfirmationLog: Error logging Bsale invoice generated', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public static function logEmailSent($payment, $orderDetail, $recipient, $attachments = [], $details = [])
    {
        try {
            return static::create([
                'payment_id' => $payment->id ?? null,
                'order_detail_id' => $orderDetail->id ?? null,
                'order_id' => $orderDetail->order_id ?? null,
                'event_type' => 'email_sent',
                'status' => 'success',
                'email_recipient' => $recipient,
                'email_attachments' => $attachments,
                'details' => $details,
            ]);
        } catch (\Exception $e) {
            \Log::error('PaymentConfirmationLog: Error logging email sent', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public static function logEmailFailed($payment, $orderDetail, $recipient, $errorMessage, $details = [])
    {
        try {
            return static::create([
                'payment_id' => $payment->id ?? null,
                'order_detail_id' => $orderDetail->id ?? null,
                'order_id' => $orderDetail->order_id ?? null,
                'event_type' => 'email_failed',
                'status' => 'failed',
                'email_recipient' => $recipient,
                'error_message' => $errorMessage,
                'details' => $details,
            ]);
        } catch (\Exception $e) {
            \Log::error('PaymentConfirmationLog: Error logging email failed', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public static function logEmailResent($payment, $orderDetail, $recipient, $attachments = [], $userId = null, $details = [])
    {
        try {
            return static::create([
                'payment_id' => $payment->id ?? null,
                'order_detail_id' => $orderDetail->id ?? null,
                'order_id' => $orderDetail->order_id ?? null,
                'event_type' => 'email_resent',
                'status' => 'success',
                'email_recipient' => $recipient,
                'email_attachments' => $attachments,
                'triggered_by_user_id' => $userId,
                'details' => $details,
            ]);
        } catch (\Exception $e) {
            \Log::error('PaymentConfirmationLog: Error logging email resent', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
