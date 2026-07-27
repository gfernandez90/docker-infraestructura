<?php

class RedmineService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(getenv('REDMINE_URL') ?: 'https://sita.anep.edu.uy', '/');
        $this->apiKey  = getenv('REDMINE_API_KEY') ?: '41534573162c5519f6b9eccc79d6256bfe62c1c1';
    }

    private function request(string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint . '.json';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Redmine-API-Key: ' . $this->apiKey
            ],
            CURLOPT_SSL_VERIFYPEER => false, // Ajustar si tienen cert auto-firmado
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Error cURL conectando a Redmine: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP {$httpCode} desde Redmine en {$endpoint}. Respuesta: " . substr($response, 0, 200));
        }

        return json_decode($response, true) ?: [];
    }

    public function getProyecto(string $identifier): ?array
    {
        try {
            $res = $this->request("/projects/{$identifier}");
            return $res['project'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

public function getTareasPorProyecto(string $projectIdentifier): array
{
    $issues = [];
    $offset = 0;
    $limit = 100;

    while (true) {
        $res = $this->request('/issues', [
            'project_id' => $projectIdentifier,
            'status_id'  => '*', 
            'include'    => 'watchers,relations,category', // <--- Trae seguidores y tareas relacionadas
            'limit'      => $limit,
            'offset'     => $offset
        ]);

        $fetched = $res['issues'] ?? [];
        $issues = array_merge($issues, $fetched);

        $totalCount = $res['total_count'] ?? 0;
        $offset += $limit;

        if ($offset >= $totalCount || empty($fetched)) {
            break;
        }
    }

    return $issues;
}
public function getTareaDetalle(int $id): ?array
    {
        $res = $this->request('/issues/' . $id, [
            'include' => 'watchers,relations,children'
        ]);

        return $res['issue'] ?? null;
    }
/**
     * Realiza peticiones POST a la API de Redmine
     */
    private function postRequest(string $endpoint, array $data): array
    {
        $url = $this->baseUrl . $endpoint . '.json?key=' . $this->apiKey;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201 && $httpCode !== 200) {
            throw new Exception("Error al crear registro en Redmine (HTTP {$httpCode}): " . $response);
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Crea una nueva tarea/incidente en Redmine
     */
    public function crearTarea(array $issueData): ?array
    {
        $payload = ['issue' => $issueData];
        $res = $this->postRequest('/issues', $payload);
        return $res['issue'] ?? null;
    }
public function getRequest(string $endpoint): array
    {
        $url = $this->baseUrl . $endpoint . (str_contains($endpoint, '?') ? '&' : '?') . 'key=' . $this->apiKey;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }
}
