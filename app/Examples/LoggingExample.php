<?php

namespace App\Examples;

use App\Helpers\LogHelper;
use App\Traits\SystemLogging;

/**
 * Ejemplo de cómo usar el sistema de logging
 * 
 * Este archivo muestra las diferentes formas de usar el sistema de logging
 * tanto con el trait SystemLogging como con el helper LogHelper
 */
class LoggingExample
{
    // Opción 1: Usar el trait en una clase
    use SystemLogging;

    public function exampleWithTrait()
    {
        try {
            // Log de inicio de operación
            $this->logOperationStart('Procesamiento de datos', ['batch_size' => 1000]);

            // Log de información
            $this->logInfo('Datos procesados correctamente', ['processed' => 950, 'failed' => 50]);

            // Log de advertencia
            $this->logWarning('Algunos registros no pudieron ser procesados', ['failed_records' => 50]);

            // Log de éxito
            $this->logOperationSuccess('Procesamiento de datos', ['total_processed' => 950]);

        } catch (\Exception $e) {
            // Log de error con excepción
            $this->logError('Error en procesamiento de datos', ['batch_id' => 123], $e);
            
            // Log de fallo de operación
            $this->logOperationFailure('Procesamiento de datos', $e->getMessage());
        }
    }

    public function exampleWithHelper()
    {
        try {
            // Log de inicio de operación
            LogHelper::operationStart('Exportación de reporte', ['report_type' => 'sales']);

            // Log de información
            LogHelper::info('Reporte generado exitosamente', ['rows' => 1500]);

            // Log de exportación
            LogHelper::export('Reporte de ventas', ['filename' => 'sales_report.xlsx']);

            // Log de éxito
            LogHelper::operationSuccess('Exportación de reporte', ['file_size' => '2.5MB']);

        } catch (\Exception $e) {
            // Log de error
            LogHelper::error('Error al exportar reporte', ['report_type' => 'sales'], $e);
            
            // Log de fallo
            LogHelper::operationFailure('Exportación de reporte', $e->getMessage());
        }
    }

    public function exampleValidationFailure()
    {
        $errors = [
            'email' => ['El email es requerido'],
            'name' => ['El nombre es requerido']
        ];

        $data = [
            'email' => '',
            'name' => '',
            'password' => 'secret123' // Este será ocultado automáticamente
        ];

        // Log de validación fallida
        LogHelper::validationFailure('Creación de usuario', $errors, $data);
    }

    public function exampleAccessDenied()
    {
        // Log de acceso denegado
        LogHelper::accessDenied('Acceso a panel de administración', 'Usuario sin permisos de admin');
    }

    public function exampleCriticalError()
    {
        try {
            // Simular un error crítico
            throw new \Exception('Error crítico en la base de datos');
        } catch (\Exception $e) {
            // Log crítico
            LogHelper::critical('Error crítico en base de datos', ['database' => 'main'], $e);
        }
    }
}

/**
 * Ejemplo de uso en un servicio real:
 * 
 * class CreateUserService
 * {
 *     use SystemLogging;
 * 
 *     public function execute(array $data)
 *     {
 *         try {
 *             $this->logOperationStart('Creación de usuario', ['email' => $data['email']]);
 * 
 *             // Validar datos
 *             $validator = Validator::make($data, [
 *                 'email' => 'required|email|unique:users',
 *                 'name' => 'required|string|max:255',
 *                 'password' => 'required|min:8'
 *             ]);
 * 
 *             if ($validator->fails()) {
 *                 $this->logValidationFailure('Creación de usuario', $validator->errors()->toArray(), $data);
 *                 throw new ValidationException($validator);
 *             }
 * 
 *             // Crear usuario
 *             $user = User::create($data);
 * 
 *             $this->logOperationSuccess('Creación de usuario', ['user_id' => $user->id]);
 * 
 *             return $user;
 * 
 *         } catch (\Exception $e) {
 *             $this->logOperationFailure('Creación de usuario', $e->getMessage());
 *             throw $e;
 *         }
 *     }
 * }
 */
