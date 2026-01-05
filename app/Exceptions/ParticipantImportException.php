<?php

namespace App\Exceptions;

use Exception;

class ParticipantImportException extends Exception
{
    protected array $errors = [];
    protected int $totalRows = 0;
    protected int $successfulRows = 0;

    public function __construct(array $errors, int $totalRows, int $successfulRows)
    {
        $this->errors = $errors;
        $this->totalRows = $totalRows;
        $this->successfulRows = $successfulRows;

        $errorCount = count($errors);
        $message = "Se encontraron {$errorCount} error(es) al procesar el archivo de estudiantes. ";
        $message .= "Filas procesadas exitosamente: {$successfulRows} de {$totalRows}.";

        parent::__construct($message);
    }

    /**
     * Obtener los errores detallados
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtener el total de filas procesadas
     */
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * Obtener el número de filas exitosas
     */
    public function getSuccessfulRows(): int
    {
        return $this->successfulRows;
    }

    /**
     * Formatear los errores para mostrar al usuario
     */
    public function getFormattedErrors(): string
    {
        $formatted = [];

        foreach ($this->errors as $error) {
            $row = $error['row'];
            $participantName = $error['participant_name'] ?? 'Desconocido';
            $errorMessage = $error['error'];

            $formatted[] = "• Fila {$row} ({$participantName}): {$errorMessage}";
        }

        return implode("\n", $formatted);
    }

    /**
     * Convertir a array para JSON response
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'total_rows' => $this->totalRows,
            'successful_rows' => $this->successfulRows,
            'error_count' => count($this->errors),
            'errors' => $this->errors,
            'formatted_errors' => $this->getFormattedErrors(),
        ];
    }
}
