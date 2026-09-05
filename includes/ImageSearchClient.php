<?php

final class ImageSearchClient
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? (defined('VISUAL_SEARCH_API_URL') ? VISUAL_SEARCH_API_URL : 'http://127.0.0.1:8000');
    }

    /**
     * @return array<int, array{image_name: string, score: float}>
     */
    public function search(string $absolutePath, int $limit = 12): array
    {
        $handle = curl_init(rtrim($this->baseUrl, '/') . '/api/search');
        $payload = json_encode([
            'image_path' => $absolutePath,
            'top_k' => $limit,
        ]);

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 120,
        ]);

        $body = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException($error ?: 'Image search service returned HTTP ' . $status);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || ($decoded['status'] ?? '') !== 'success' || !isset($decoded['matches'])) {
            throw new RuntimeException('Invalid image search response.');
        }

        return $decoded['matches'];
    }

    public function isHealthy(): bool
    {
        $handle = curl_init(rtrim($this->baseUrl, '/') . '/');
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 5,
        ]);
        $body = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return $body !== false && $status >= 200 && $status < 300;
    }

    public function getRecommendations(int $userId, int $limit = 6): array
    {
        throw new RuntimeException('Recommendations are not available in the visual search API yet.');
    }
}
