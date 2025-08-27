<?php

declare(strict_types=1);

namespace Laneful;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Laneful\Exceptions\ApiException;
use Laneful\Exceptions\HttpException;
use Laneful\Exceptions\ValidationException;
use Laneful\Models\Email;
use Psr\Http\Message\ResponseInterface;

/**
 * Main client for communicating with the Laneful email API.
 */
final class LanefulClient
{
    private const API_VERSION = 'v1';
    private const DEFAULT_TIMEOUT = 30;

    private HttpClient $httpClient;

    public function __construct(
        private string $baseUrl,
        private string $authToken,
        ?HttpClient $httpClient = null,
        private int $timeout = self::DEFAULT_TIMEOUT
    ) {
        if (empty($this->baseUrl)) {
            throw new ValidationException('Base URL cannot be empty');
        }

        if (empty($this->authToken)) {
            throw new ValidationException('Auth token cannot be empty');
        }

        $this->httpClient = $httpClient ?? $this->createDefaultHttpClient();
    }

    /**
     * Send a single email.
     *
     * @param Email $email The email to send
     * @return array<string, mixed> API response data
     * @throws ApiException When the API returns an error
     * @throws HttpException When HTTP communication fails
     */
    public function sendEmail(Email $email): array
    {
        return $this->sendEmails([$email]);
    }

    /**
     * Send multiple emails.
     *
     * @param Email[] $emails Array of emails to send
     * @return array<string, mixed> API response data
     * @throws ApiException When the API returns an error
     * @throws HttpException When HTTP communication fails
     * @throws ValidationException When input validation fails
     */
    public function sendEmails(array $emails): array
    {
        if (empty($emails)) {
            throw new ValidationException('Emails array cannot be empty');
        }

        // Validate all emails are Email instances
        foreach ($emails as $email) {
            if (!$email instanceof Email) {
                throw new ValidationException('All items in emails array must be Email instances');
            }
        }

        $requestData = [
            'emails' => array_map(fn(Email $email) => $email->toArray(), $emails),
        ];

        $url = $this->buildUrl('/email/send');

        try {
            $response = $this->httpClient->request('POST', $url, [
                RequestOptions::JSON => $requestData,
                RequestOptions::HEADERS => $this->getDefaultHeaders(),
                RequestOptions::TIMEOUT => $this->timeout,
            ]);

            return $this->handleResponse($response, $url);
        } catch (GuzzleException $e) {
            throw new HttpException(
                "HTTP request failed: {$e->getMessage()}",
                $e->getCode(),
                $e instanceof \Exception ? $e : null
            );
        }
    }

    /**
     * Create the default HTTP client with appropriate configuration.
     */
    private function createDefaultHttpClient(): HttpClient
    {
        return new HttpClient([
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::VERIFY => true, // Always verify SSL certificates
            RequestOptions::HTTP_ERRORS => false, // Handle HTTP errors manually
        ]);
    }

    /**
     * Get default headers for API requests.
     *
     * @return array<string, string>
     */
    private function getDefaultHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->authToken}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'laneful-php/1.0.0',
        ];
    }

    /**
     * Build the full API URL.
     */
    private function buildUrl(string $endpoint): string
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        $endpoint = ltrim($endpoint, '/');

        return "{$baseUrl}/" . self::API_VERSION . "/{$endpoint}";
    }

    /**
     * Handle the HTTP response and convert to array.
     *
     * @param ResponseInterface $response
     * @param string $url The URL that was requested (for debugging)
     * @return array<string, mixed>
     * @throws ApiException When the API returns an error
     * @throws HttpException When response parsing fails
     */
    private function handleResponse(ResponseInterface $response, string $url): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        // Handle 404 specifically as it likely means wrong URL
        if ($statusCode === 404) {
            throw new HttpException(
                "API endpoint not found (404). Check your base URL. Requested: {$url}",
                $statusCode
            );
        }

        // Try to decode JSON response
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log the actual response body for debugging
            $truncatedBody = strlen($body) > 500 ? substr($body, 0, 500) . '...' : $body;
            throw new HttpException(
                "Failed to decode JSON response: " . json_last_error_msg() .
                ". Response body: " . $truncatedBody . ". URL: {$url}",
                $statusCode
            );
        }

        // Handle successful responses
        if ($statusCode >= 200 && $statusCode < 300) {
            return $data;
        }

        // Handle API errors
        $errorMessage = $data['error'] ?? 'Unknown API error';
        throw new ApiException(
            "API request failed to {$url}",
            $statusCode,
            $errorMessage
        );
    }
}
