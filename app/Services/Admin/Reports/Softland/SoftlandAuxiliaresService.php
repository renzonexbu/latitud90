<?php

namespace App\Services\Admin\Reports\Softland;

use App\Models\OrderDetail;
use App\Models\Region;
use App\Models\Comune;
use App\Models\Document;
use App\Models\Country;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SoftlandAuxiliaresService
{
    /**
     * Mapeo de regiones chilenas a códigos numéricos de Softland
     */
    private const REGION_CODES = [
        'I Región de Tarapacá' => 1,
        'II Región de Antofagasta' => 2,
        'III Región de Atacama' => 3,
        'IV Región de Coquimbo' => 4,
        'V Región de Valparaíso' => 5,
        'VI Región del Libertador General Bernardo O\'Higgins' => 6,
        'VII Región del Maule' => 7,
        'VIII Región del Biobío' => 8,
        'IX Región de la Araucanía' => 9,
        'X Región de Los Lagos' => 10,
        'XI Región Aysén del General Carlos Ibáñez del Campo' => 11,
        'XII Región de Magallanes y de la Antártica Chilena' => 12,
        'Región Metropolitana de Santiago' => 13,
        'XIV Región de Los Ríos' => 14,
        'XV Región de Arica y Parinacota' => 15,
        'XVI Región de Ñuble' => 16,
    ];

    /**
     * Genera los datos de auxiliares contables para Softland desde orders_detail y suscripciones
     */
    public function generateAuxiliaresData(): Collection
    {
        $auxiliares = collect();

        // 1. Obtener compradores únicos de orders_detail con joins a las tablas relacionadas
        $orderDetails = OrderDetail::with([
            'documentType',
            'country',
            'region',
            'city',
            'order.participant' // Agregar relación con participant
        ])
            ->get()
            ->groupBy(function ($item) {
                // Normalizar el document_number para evitar duplicados por caracteres especiales
                return $this->normalizeDocumentNumber($item->document_number);
            })
            ->map(function ($group) {
                // Tomar el primer registro de cada grupo (document_number único normalizado)
                return $group->first();
            });

        // Mapear order_details a auxiliares
        foreach ($orderDetails as $orderDetail) {
            $auxiliares->push($this->mapOrderDetailToAuxiliar($orderDetail));
        }

        // 2. Obtener compradores únicos de suscripciones (buyer_data)
        $subscriptions = \App\Models\ProgramSubscription::whereNotNull('buyer_data')
            ->with('participant')
            ->get();

        $subscriptionBuyers = $subscriptions
            ->filter(function ($subscription) {
                $buyerData = $subscription->buyer_data;
                return !empty($buyerData['document_number']);
            })
            ->groupBy(function ($subscription) {
                $buyerData = $subscription->buyer_data;
                return $this->normalizeDocumentNumber($buyerData['document_number']);
            })
            ->map(function ($group) {
                // Tomar el primer registro de cada grupo
                return $group->first();
            });

        // Mapear subscriptions a auxiliares
        foreach ($subscriptionBuyers as $subscription) {
            $normalizedDoc = $this->normalizeDocumentNumber($subscription->buyer_data['document_number']);

            // Solo agregar si no existe ya en auxiliares (evitar duplicados con order_details)
            if (!$auxiliares->contains(function ($aux) use ($normalizedDoc) {
                return $this->normalizeDocumentNumber($aux['rut_auxiliar']) === $normalizedDoc;
            })) {
                $auxiliares->push($this->mapSubscriptionToAuxiliar($subscription));
            }
        }

        return $auxiliares;
    }

    /**
     * Mapea un order_detail a los campos de auxiliar de Softland
     */
    private function mapOrderDetailToAuxiliar($orderDetail): array
    {
        $documentNumber = $this->formatDocumentNumber($orderDetail);
        $auxiliarCode = $this->generateAuxiliarCode($orderDetail);
        
        // Usar el nombre del participante en lugar del comprador
        $participantName = '';
        if ($orderDetail->order && $orderDetail->order->participant) {
            $participantName = $orderDetail->order->participant->name ?? '';
        }
        $nombreAuxiliar = $this->truncateString($participantName ?: $orderDetail->name, 60);
        
        // Construir dirección: "País, Región"
        $direccion = '';
        $countryName = ($orderDetail->country && is_object($orderDetail->country)) ? $orderDetail->country->name : '';
        $regionName = ($orderDetail->region && is_object($orderDetail->region)) ? $orderDetail->region->name : '';
        if ($countryName && $regionName) {
            $direccion = "{$countryName}, {$regionName}";
        } elseif ($countryName) {
            $direccion = $countryName;
        } elseif ($regionName) {
            $direccion = $regionName;
        }
        
        // Obtener nombre de comuna
        $comunaName = ($orderDetail->city && is_object($orderDetail->city)) ? $orderDetail->city->name : '';
        
        return [
            // Campos principales con datos reales
            'codigo_auxiliar' => $auxiliarCode,
            'nombre_auxiliar' => $nombreAuxiliar, // Nombre del participante
            'nombre_fantasia' => $nombreAuxiliar, // Mismo valor que Nombre Auxiliar
            'rut_auxiliar' => $documentNumber, // RUT del comprador
            'activo' => 'S',
            
            // Campos de ubicación con datos reales
            'codigo_giro_comercial' => '',
            'codigo_pais_auxiliar' => '',
            'codigo_region' => '',
            'codigo_ciudad_auxiliar' => '',
            'codigo_comuna_auxiliar' => '',
            'direccion_auxiliar' => $this->truncateString($direccion, 60), // País, Región
            'numero_dir_auxiliar' => '',
            'telefono_1_auxiliar' => '',
            'telefono_2_auxiliar' => '',
            'telefono_3_auxiliar' => '',
            'fax_1_auxiliar' => '',
            'fax_2_auxiliar' => '',
            'clasificacion_cliente' => 'S', // Siempre S
            'clasificacion_proveedor' => 'S', // Siempre S
            'clasificacion_empleado' => '',
            'clasificacion_socio' => '',
            'clasificacion_distribuidor' => '',
            'clasificacion_otro' => '',
            'casilla_auxiliar' => '',
            'email_auxiliar' => '',
            'sitio_web_auxiliar' => '',
            'notas_auxiliar' => $this->truncateString($comunaName, 60), // Comuna name en notas
            'nombre_contacto' => '',
            'codigo_cargo_contacto' => '',
            'telefono_contacto' => '',
            'fax_contacto' => '',
            'email_contacto' => '',
            'codigo_vendedor' => '',
            'condicion_venta' => '',
            'monto_autorizado' => '',
            'codigo_categoria_cliente' => '',
            'codigo_zona_vendedor' => '',
            'codigo_canal_venta' => '',
            'lugar_despacho' => '',
            'direccion_despacho' => '',
            'codigo_comuna_despacho' => '',
            'codigo_ciudad_despacho' => '',
            'codigo_pais_despacho' => '',
            'telefono_1_despacho' => '',
            'telefono_2_despacho' => '',
            'telefono_3_despacho' => '',
            'fax_despacho' => '',
            'atencion_despacho' => '',
            'codigo_cobrador' => '',
            'direccion_cobranza' => '',
            'codigo_comuna_cobranza' => '',
            'codigo_ciudad_cobranza' => '',
            'codigo_pais_cobranza' => '',
            'telefono' => '',
            'dia_pago' => '',
            'codigo_lista_precio' => '',
            'email_dte' => $orderDetail->email ?? '', // Email del comprador
            'es_emisor_receptor_dte' => 'S', // Siempre S
            'codigo_clasificacion_negocio' => '',
            'cuenta_clientes_doctos_moneda_base' => '',
            'cuenta_clientes_doctos_moneda_extranjera' => '',
            'codigo_banco' => '',
            'cuenta_corriente' => '',
            'codigo_condicion_pago_proveedor' => '',
        ];
    }

    /**
     * Formatea el número de documento según el tipo
     * Para pasaportes: usar RUT estándar 55.555.555-5 en campo RUT de Softland
     */
    private function formatDocumentNumber($orderDetail): string
    {
        if (!$orderDetail->document_number) {
            return '';
        }
        
        $documentNumber = $orderDetail->document_number;
        
        // Si es pasaporte, usar RUT estándar para extranjeros en Softland
        if ($orderDetail->documentType && is_object($orderDetail->documentType) && $orderDetail->documentType->name === 'PASAPORTE') {
            return '55555555-5'; // RUT estándar para extranjeros en Softland con formato
        }
        
        // Si es RUT, formatear con guión (sin puntos)
        if ($orderDetail->documentType && is_object($orderDetail->documentType) && $orderDetail->documentType->name === 'RUT') {
            // Limpiar puntos y guiones existentes
            $cleanNumber = preg_replace('/[^0-9kK]/', '', $documentNumber);
            if (strlen($cleanNumber) >= 8) {
                $number = substr($cleanNumber, 0, -1);
                $dv = strtoupper(substr($cleanNumber, -1));
                $documentNumber = $number . '-' . $dv;
            }
        }
        
        return $this->truncateString($documentNumber, 11);
    }

    /**
     * Normaliza el número de documento para evitar duplicados por caracteres especiales
     */
    private function normalizeDocumentNumber(?string $documentNumber): string
    {
        if (!$documentNumber) {
            return '';
        }
        
        // Remover todos los caracteres especiales (puntos, guiones, espacios) y convertir a mayúsculas
        return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $documentNumber));
    }

    /**
     * Genera un código único para el auxiliar (RUT sin puntos ni guiones, o pasaporte)
     */
    private function generateAuxiliarCode($orderDetail): string
    {
        if (!$orderDetail->document_number) {
            return 'SIN_DOC_' . $orderDetail->id;
        }
        
        $documentNumber = $orderDetail->document_number;
        
        // Si es pasaporte, usar tal como está
        if ($orderDetail->documentType && is_object($orderDetail->documentType) && $orderDetail->documentType->name === 'PASAPORTE') {
            return strtoupper($documentNumber);
        }
        
        // Si es RUT, quitar puntos y guiones
        if ($orderDetail->documentType && is_object($orderDetail->documentType) && $orderDetail->documentType->name === 'RUT') {
            return preg_replace('/[^0-9kK]/', '', $documentNumber);
        }
        
        // Para otros tipos de documento, usar tal como está
        return $documentNumber;
    }

    /**
     * Trunca una cadena al largo especificado
     */
    private function truncateString(?string $string, int $length): string
    {
        if (!$string) {
            return '';
        }

        return mb_substr($string, 0, $length);
    }

    /**
     * Mapea una suscripción (buyer_data) a los campos de auxiliar de Softland
     */
    private function mapSubscriptionToAuxiliar($subscription): array
    {
        $buyerData = $subscription->buyer_data;
        $participant = $subscription->participant;

        // Formatear el número de documento
        $documentNumber = $this->formatSubscriptionDocumentNumber($buyerData);

        // Generar código auxiliar (RUT sin puntos ni guiones)
        $auxiliarCode = strtoupper(str_replace(['.', '-', ' '], '', $buyerData['document_number'] ?? ''));

        // Nombre del auxiliar en capital case
        $firstName = ucwords(strtolower($buyerData['first_name'] ?? ''));
        $lastName = ucwords(strtolower($buyerData['first_last_name'] ?? ''));
        $nombreAuxiliar = $this->truncateString("{$firstName} {$lastName}", 60);

        // Email del comprador
        $email = $buyerData['email'] ?? '';

        return [
            // Campos principales con datos reales
            'codigo_auxiliar' => $auxiliarCode,
            'nombre_auxiliar' => $nombreAuxiliar,
            'nombre_fantasia' => $nombreAuxiliar,
            'rut_auxiliar' => $documentNumber,
            'activo' => 'S',

            // Campos de ubicación
            'codigo_giro_comercial' => '',
            'codigo_pais_auxiliar' => '',
            'codigo_region' => '',
            'codigo_ciudad_auxiliar' => '',
            'codigo_comuna_auxiliar' => '',
            'direccion_auxiliar' => '', // Vacío para suscripciones
            'numero_dir_auxiliar' => '',
            'telefono_1_auxiliar' => '', // Vacío para suscripciones
            'telefono_2_auxiliar' => '',
            'telefono_3_auxiliar' => '',
            'fax_1_auxiliar' => '',
            'fax_2_auxiliar' => '',
            'clasificacion_cliente' => 'S',
            'clasificacion_proveedor' => 'S',
            'clasificacion_empleado' => '',
            'clasificacion_socio' => '',
            'clasificacion_distribuidor' => '',
            'clasificacion_otro' => '',
            'casilla_auxiliar' => '',
            'email_auxiliar' => '',
            'sitio_web_auxiliar' => '',
            'notas_auxiliar' => '', // Vacío para suscripciones
            'nombre_contacto' => '',
            'codigo_cargo_contacto' => '',
            'telefono_contacto' => '',
            'fax_contacto' => '',
            'email_contacto' => '',
            'codigo_vendedor' => '',
            'condicion_venta' => '',
            'monto_autorizado' => '',
            'codigo_categoria_cliente' => '',
            'codigo_zona_vendedor' => '',
            'codigo_canal_venta' => '',
            'lugar_despacho' => '',
            'direccion_despacho' => '',
            'codigo_comuna_despacho' => '',
            'codigo_ciudad_despacho' => '',
            'codigo_pais_despacho' => '',
            'telefono_1_despacho' => '',
            'telefono_2_despacho' => '',
            'telefono_3_despacho' => '',
            'fax_despacho' => '',
            'atencion_despacho' => '',
            'codigo_cobrador' => '',
            'direccion_cobranza' => '',
            'codigo_comuna_cobranza' => '',
            'codigo_ciudad_cobranza' => '',
            'codigo_pais_cobranza' => '',
            'telefono' => '',
            'dia_pago' => '',
            'codigo_lista_precio' => '',
            'email_dte' => $email,
            'es_emisor_receptor_dte' => 'S',
            'codigo_clasificacion_negocio' => '',
            'cuenta_clientes_doctos_moneda_base' => '',
            'cuenta_clientes_doctos_moneda_extranjera' => '',
            'codigo_banco' => '',
            'cuenta_corriente' => '',
            'codigo_condicion_pago_proveedor' => '',
        ];
    }

    /**
     * Formatea el número de documento de una suscripción (buyer_data)
     */
    private function formatSubscriptionDocumentNumber(array $buyerData): string
    {
        if (empty($buyerData['document_number'])) {
            return '';
        }

        $documentNumber = $buyerData['document_number'];
        $documentType = $buyerData['document_type'] ?? 'RUT';

        // Si es pasaporte, usar RUT estándar para extranjeros en Softland
        if ($documentType === 'PASAPORTE') {
            return '55555555-5';
        }

        // Si es RUT, formatear con guión (sin puntos)
        if ($documentType === 'RUT') {
            // Limpiar puntos y guiones existentes
            $cleanNumber = preg_replace('/[^0-9kK]/', '', $documentNumber);
            if (strlen($cleanNumber) >= 8) {
                $number = substr($cleanNumber, 0, -1);
                $dv = strtoupper(substr($cleanNumber, -1));
                $documentNumber = $number . '-' . $dv;
            }
        }

        return $this->truncateString($documentNumber, 11);
    }

    /**
     * Obtiene los encabezados de las columnas para el archivo
     */
    public function getHeaders(): array
    {
        return [
            'Código auxiliar',
            'Nombre Auxiliar',
            'Nombre de Fantasía',
            'RUT Auxiliar',
            'Activo',
            'Código Giro Comercial',
            'Código País Auxiliar',
            'Código de Región',
            'Código Ciudad Auxiliar',
            'Código Comuna Auxiliar',
            'Dirección Auxiliar',
            'Número Dir. Auxiliar',
            'Teléfono 1 Auxiliar',
            'Teléfono 2 Auxiliar',
            'Teléfono 3 Auxiliar',
            'Fax 1 Auxiliar',
            'Fax 2 Auxiliar',
            'Clasificación Cliente',
            'Clasificación Proveedor',
            'Clasificación Empleado',
            'Clasificación Socio',
            'Clasificación Distribuidor',
            'Clasificación Otro',
            'Casilla Auxiliar',
            'E-Mail Auxiliar',
            'Sitio Web Auxiliar',
            'Notas Auxiliar',
            'Nombre Contacto',
            'Código Cargo Contacto',
            'Teléfono Contacto',
            'Fax Contacto',
            'E-Mail Contacto',
            'Código Vendedor',
            'Condición de Venta',
            'Monto Autorizado',
            'Código Categoría Cliente',
            'Código Zona Vendedor',
            'Código Canal de Venta',
            'Lugar Despacho',
            'Dirección Despacho',
            'Código Comuna Despacho',
            'Código Ciudad Despacho',
            'Código País Despacho',
            'Teléfono 1 Despacho',
            'Teléfono 2 Despacho',
            'Teléfono 3 Despacho',
            'Fax Despacho',
            'Atención Despacho',
            'Código Cobrador',
            'Dirección Cobranza',
            'Código Comuna Cobranza',
            'Código Ciudad Cobranza',
            'Código País Cobranza',
            'Teléfono',
            'Día de Pago',
            'Código Lista Precio',
            'eMail DTE',
            'Es Emisor o Receptor de Documentos Electrónicos',
            'Código Clasificación de Negocio',
            'Cuenta clientes doctos. Moneda Base',
            'Cuenta clientes doctos. Moneda Extranjera y/o Exportación',
            'Código Banco',
            'Cuenta Corriente',
            'Código de condición de pago Proveedor',
        ];
    }

    /**
     * Genera un JSON con los datos para verificación
     */
    public function generateJsonData(): array
    {
        $data = $this->generateAuxiliaresData();
        
        return [
            'total_auxiliares' => $data->count(),
            'headers' => $this->getHeaders(),
            'data' => $data->toArray()
        ];
    }
}