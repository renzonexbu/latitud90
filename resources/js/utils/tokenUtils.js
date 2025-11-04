/**
 * Codifica los datos del participante en un token base64 URL-safe
 * @param {Object} data - Objeto con document y document_type
 * @returns {string} Token codificado
 */
export function encodeParticipantToken(data) {
    const jsonString = JSON.stringify(data);
    // Codificar a base64 y hacerlo URL-safe
    const base64 = btoa(jsonString);
    return base64
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=/g, '');
}

/**
 * Decodifica el token base64 URL-safe a los datos del participante
 * @param {string} token - Token codificado
 * @returns {Object|null} Objeto con document y document_type, o null si hay error
 */
export function decodeParticipantToken(token) {
    try {
        // Revertir los cambios URL-safe
        let base64 = token
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        // Agregar padding si es necesario
        while (base64.length % 4) {
            base64 += '=';
        }

        const jsonString = atob(base64);
        return JSON.parse(jsonString);
    } catch (error) {
        console.error('Error decodificando token:', error);
        return null;
    }
}

/**
 * Obtiene el token desde la URL actual
 * @returns {string|null} Token si existe
 */
export function getTokenFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('token');
}

/**
 * Obtiene los datos del participante desde el token en la URL
 * @returns {Object|null} Datos decodificados o null
 */
export function getParticipantDataFromUrl() {
    const token = getTokenFromUrl();
    if (!token) return null;
    return decodeParticipantToken(token);
}
