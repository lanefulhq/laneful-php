<?php

declare(strict_types=1);

namespace Laneful;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Laneful\Exceptions\ApiException;
use Laneful\Exceptions\HttpException;
use Laneful\Exceptions\ValidationException;
use Laneful\Models\CreateDomainRequest;
use Laneful\Models\Domain;
use Laneful\Models\Email;
use Laneful\Models\ListDomainSpamRatioRadarParams;
use Laneful\Models\ListDomainSpamRatioRadarResponse;
use Laneful\Models\ListDomainsParams;
use Laneful\Models\ListDomainsResponse;
use Laneful\Models\ListGooglePostmasterSpamReportsParams;
use Laneful\Models\ListGooglePostmasterSpamReportsResponse;
use Laneful\Models\ListSndsReportsParams;
use Laneful\Models\ListSndsReportsResponse;
use Laneful\Models\ListUnsubscribeGroupsParams;
use Laneful\Models\ListUnsubscribeGroupsResponse;
use Laneful\Models\MailSettings;
use Laneful\Models\SuccessResponse;
use Laneful\Models\UnsubscribeGroup;
use Laneful\Models\UpdateDomainRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * Main client for communicating with the Laneful API.
 *
 * Email sending uses a send host (https://your-endpoint.send.laneful.net).
 * Domain, unsubscribe-group, and analytics endpoints use the organization
 * API host (https://api.laneful.net).
 */
final class LanefulClient
{
    private const API_VERSION = 'v1';
    private const VERSION = '1.1.0';
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
     * @return array<string, mixed> API response data
     * @throws ApiException When the API returns an error
     * @throws HttpException When HTTP communication fails
     */
    public function sendEmail(Email $email, ?MailSettings $settings = null): array
    {
        return $this->sendEmails([$email], $settings);
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
    public function sendEmails(array $emails, ?MailSettings $settings = null): array
    {
        if (empty($emails)) {
            throw new ValidationException('Emails array cannot be empty');
        }

        foreach ($emails as $email) {
            if (!$email instanceof Email) {
                throw new ValidationException('All items in emails array must be Email instances');
            }
        }

        $requestData = [
            'emails' => array_map(static fn(Email $email) => $email->toArray(), $emails),
        ];

        if ($settings !== null) {
            $requestData['mail_settings'] = $settings->toArray();
        }

        return $this->request('POST', '/email/send', $requestData);
    }

    /**
     * List unsubscribe groups for a workspace.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function listUnsubscribeGroups(
        int $workspaceId,
        ?ListUnsubscribeGroupsParams $params = null
    ): ListUnsubscribeGroupsResponse {
        $query = $params?->toQuery() ?? [];

        return ListUnsubscribeGroupsResponse::fromArray(
            $this->request(
                'GET',
                "/workspaces/{$workspaceId}/unsubscribe-groups",
                null,
                $query
            )
        );
    }

    /**
     * Create an unsubscribe group in a workspace.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function createUnsubscribeGroup(int $workspaceId, string $name): UnsubscribeGroup
    {
        $data = $this->request(
            'POST',
            "/workspaces/{$workspaceId}/unsubscribe-groups",
            ['name' => $name]
        );

        return UnsubscribeGroup::fromArray($data['unsubscribe_group'] ?? $data);
    }

    /**
     * Update an unsubscribe group.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function updateUnsubscribeGroup(
        int $workspaceId,
        int $unsubscribeGroupId,
        string $name
    ): UnsubscribeGroup {
        $data = $this->request(
            'PATCH',
            "/workspaces/{$workspaceId}/unsubscribe-groups/{$unsubscribeGroupId}",
            ['name' => $name]
        );

        return UnsubscribeGroup::fromArray($data['unsubscribe_group'] ?? $data);
    }

    /**
     * List sending domains for a workspace.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function listDomains(int $workspaceId, ?ListDomainsParams $params = null): ListDomainsResponse
    {
        $query = $params?->toQuery() ?? [];

        return ListDomainsResponse::fromArray(
            $this->request('GET', "/workspaces/{$workspaceId}/domains", null, $query)
        );
    }

    /**
     * Get a single sending domain by name.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function getDomain(int $workspaceId, string $domain): Domain
    {
        $encoded = rawurlencode($domain);

        return Domain::fromArray(
            $this->request('GET', "/workspaces/{$workspaceId}/domains/{$encoded}")
        );
    }

    /**
     * Create a sending domain in a workspace.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function createDomain(int $workspaceId, CreateDomainRequest $request): Domain
    {
        return Domain::fromArray(
            $this->request('POST', "/workspaces/{$workspaceId}/domains", $request->toArray())
        );
    }

    /**
     * Update a domain's mutable settings (currently the email track).
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function updateDomain(
        int $workspaceId,
        string $domain,
        UpdateDomainRequest $request
    ): Domain {
        $encoded = rawurlencode($domain);

        return Domain::fromArray(
            $this->request(
                'PATCH',
                "/workspaces/{$workspaceId}/domains/{$encoded}",
                $request->toArray()
            )
        );
    }

    /**
     * Trigger DNS verification for a domain.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function verifyDomain(int $workspaceId, string $domain): Domain
    {
        $encoded = rawurlencode($domain);

        return Domain::fromArray(
            $this->request('POST', "/workspaces/{$workspaceId}/domains/{$encoded}/verify")
        );
    }

    /**
     * Delete a sending domain from a workspace.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function deleteDomain(int $workspaceId, string $domain): SuccessResponse
    {
        $encoded = rawurlencode($domain);

        return SuccessResponse::fromArray(
            $this->request('DELETE', "/workspaces/{$workspaceId}/domains/{$encoded}")
        );
    }

    /**
     * List domains whose spam complaint ratio reached a critical level.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function listDomainSpamRatioRadar(
        ?ListDomainSpamRatioRadarParams $params = null
    ): ListDomainSpamRatioRadarResponse {
        $query = $params?->toQuery() ?? [];

        return ListDomainSpamRatioRadarResponse::fromArray(
            $this->request('GET', '/analytics/radar/domain-spam-ratio', null, $query)
        );
    }

    /**
     * List daily Google Postmaster Tools spam-rate reports.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function listGooglePostmasterSpamReports(
        ?ListGooglePostmasterSpamReportsParams $params = null
    ): ListGooglePostmasterSpamReportsResponse {
        $query = $params?->toQuery() ?? [];

        return ListGooglePostmasterSpamReportsResponse::fromArray(
            $this->request('GET', '/analytics/google-postmaster/spam-reports', null, $query)
        );
    }

    /**
     * List daily Microsoft SNDS reports for the organization's sending IPs.
     *
     * Uses the organization API host (https://api.laneful.net).
     */
    public function listSndsReports(?ListSndsReportsParams $params = null): ListSndsReportsResponse
    {
        $query = $params?->toQuery() ?? [];

        return ListSndsReportsResponse::fromArray(
            $this->request('GET', '/analytics/microsoft-snds/reports', null, $query)
        );
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
    private function getDefaultHeaders(bool $withJson = true): array
    {
        $headers = [
            'Authorization' => "Bearer {$this->authToken}",
            'Accept' => 'application/json',
            'User-Agent' => 'laneful-php/' . self::VERSION,
        ];

        if ($withJson) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
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
     * @param array<string, mixed>|null $json
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     * @throws ApiException
     * @throws HttpException
     */
    private function request(
        string $method,
        string $path,
        ?array $json = null,
        array $query = []
    ): array {
        $url = $this->buildUrl($path);
        $queryString = $this->buildQueryString($query);
        if ($queryString !== '') {
            $url .= '?' . $queryString;
        }

        $options = [
            RequestOptions::HEADERS => $this->getDefaultHeaders($json !== null),
            RequestOptions::TIMEOUT => $this->timeout,
        ];

        if ($json !== null) {
            $options[RequestOptions::JSON] = $json;
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);

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
     * @param array<string, mixed> $params
     */
    private function buildQueryString(array $params): string
    {
        $parts = [];

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === null || $item === '') {
                        continue;
                    }
                    $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $item);
                }
                continue;
            }

            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }

        return implode('&', $parts);
    }

    /**
     * Handle the HTTP response and convert to array.
     *
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
