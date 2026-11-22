<?php
class Fapshi
{
    public static function configOk(): bool
    {
        return FAPSHI_API_USER !== '' && FAPSHI_API_KEY !== '';
    }

    public static function initiate(array $data): array
    {
        if (!self::configOk()) {
            throw new RuntimeException('Les identifiants Fapshi ne sont pas configurés.');
        }

        return self::request('POST', '/initiate-pay', $data);
    }

    public static function paymentStatus(string $transId): array
    {
        if (!self::configOk()) {
            throw new RuntimeException('Les identifiants Fapshi ne sont pas configurés.');
        }

        return self::request('GET', '/payment-status/' . rawurlencode($transId));
    }

    private static function request(string $method, string $path, array $body = []): array
    {
        $ch = curl_init(FAPSHI_API_BASE . $path);
        if ($ch === false) {
            throw new RuntimeException('Impossible d’initialiser le client HTTP.');
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'apiuser: ' . FAPSHI_API_USER,
            'apikey: ' . FAPSHI_API_KEY,
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $message = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Connexion à Fapshi impossible : ' . $message);
        }

        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode((string) $raw, true);
        $data = is_array($json) ? $json : [];

        if ($http < 200 || $http >= 300) {
            throw new RuntimeException((string) ($data['message'] ?? 'Requête Fapshi refusée (' . $http . ').'));
        }

        return $data;
    }

    public static function normalizeStatus(string $status): ?string
    {
        return match (strtoupper($status)) {
            'SUCCESSFUL' => 'reussi',
            'FAILED', 'EXPIRED' => 'echoue',
            'CREATED', 'PENDING' => 'en_attente',
            default => null,
        };
    }
}
