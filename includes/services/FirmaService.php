<?php
/**
 * Servicio para gestionar firmas electrónicas avanzadas (e.firma SAT)
 */

namespace SIC\Services;

class FirmaService
{
    /**
     * Firma la cadena original usando los certificados e.firma del usuario.
     *
     * @param string $cadenaOriginal Cadena original a firmar
     * @param string $rutaCer Path temporal al archivo .cer
     * @param string $rutaKey Path temporal al archivo .key
     * @param string $password Contraseña del archivo .key
     * @return array Retorna arreglo con firma base64, hash y datos del certificado
     * @throws \RuntimeException si ocurre algún error en el proceso
     */
    public static function firmarCadena(string $cadenaOriginal, string $rutaCer, string $rutaKey, string $password): array
    {
        if (!extension_loaded('openssl')) {
            throw new \RuntimeException('La extensión OpenSSL no está disponible.');
        }

        $cerContent = @file_get_contents($rutaCer);
        $keyContent = @file_get_contents($rutaKey);

        if ($cerContent === false || $keyContent === false) {
            throw new \RuntimeException('No se pudieron leer los archivos de certificado.');
        }

        $certPem = self::convertirCerAPem($cerContent);
        $privateKey = self::obtenerPrivateKey($keyContent, $password);

        $firma = '';
        $resultado = openssl_sign($cadenaOriginal, $firma, $privateKey, OPENSSL_ALGO_SHA256);

        openssl_free_key($privateKey);

        if (!$resultado) {
            throw new \RuntimeException('No fue posible firmar la cadena original.');
        }

        $certData = openssl_x509_parse($certPem);
        if ($certData === false) {
            throw new \RuntimeException('Certificado inválido.');
        }

        return [
            'firma_base64' => base64_encode($firma),
            'hash_documento' => hash('sha256', $cadenaOriginal),
            'numero_certificado' => $certData['serialNumberHex'] ?? null,
            'vigencia_certificado' => isset($certData['validTo_time_t']) ? date('Y-m-d H:i:s', $certData['validTo_time_t']) : null,
            'certificado_pem' => $certPem,
        ];
    }

    private static function convertirCerAPem(string $cerContent): string
    {
        $cerBase64 = chunk_split(base64_encode($cerContent), 64, "\n");
        return "-----BEGIN CERTIFICATE-----\n" . $cerBase64 . "-----END CERTIFICATE-----\n";
    }

    private static function obtenerPrivateKey(string $keyContent, string $password)
    {
        $privateKey = openssl_pkey_get_private($keyContent, $password);
        if ($privateKey === false) {
            // Intentar convertir de DER a PEM si fuera necesario
            $pem = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n" . chunk_split(base64_encode($keyContent), 64, "\n") . "-----END ENCRYPTED PRIVATE KEY-----\n";
            $privateKey = openssl_pkey_get_private($pem, $password);
        }

        if ($privateKey === false) {
            throw new \RuntimeException('No se pudo obtener la llave privada. Verifica la contraseña.');
        }

        return $privateKey;
    }
}
