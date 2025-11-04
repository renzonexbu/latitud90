<?php

namespace App\Helpers;

class TokenHelper
{
    /**
     * Decodifica el token del participante
     *
     * @param string $token
     * @return array|null ['document' => string, 'document_type' => string]
     */
    public static function decodeParticipantToken(string $token): ?array
    {
        try {
            // Revertir los cambios URL-safe
            $base64 = str_replace(['-', '_'], ['+', '/'], $token);

            // Agregar padding si es necesario
            $padding = strlen($base64) % 4;
            if ($padding) {
                $base64 .= str_repeat('=', 4 - $padding);
            }

            $jsonString = base64_decode($base64);

            if ($jsonString === false) {
                return null;
            }

            $data = json_decode($jsonString, true);

            if (!is_array($data) || !isset($data['document']) || !isset($data['document_type'])) {
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            \Log::error('Error decodificando token del participante:', [
                'error' => $e->getMessage(),
                'token' => $token
            ]);
            return null;
        }
    }

    /**
     * Codifica los datos del participante en un token
     *
     * @param string $document
     * @param string $documentType
     * @return string
     */
    public static function encodeParticipantToken(string $document, string $documentType): string
    {
        $data = [
            'document' => $document,
            'document_type' => $documentType
        ];

        $jsonString = json_encode($data);
        $base64 = base64_encode($jsonString);

        // Hacerlo URL-safe
        return str_replace(['+', '/', '='], ['-', '_', ''], $base64);
    }
}
