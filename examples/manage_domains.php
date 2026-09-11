<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Laneful\LanefulClient;
use Laneful\Models\CreateDomainRequest;
use Laneful\Models\ListDomainsParams;
use Laneful\Models\UpdateDomainRequest;

$client = new LanefulClient(
    baseUrl: $_ENV['LANEFUL_BASE_URL'] ?? 'https://api.laneful.net',
    authToken: $_ENV['LANEFUL_AUTH_TOKEN'] ?? 'your-auth-token-here'
);

$workspaceId = (int) ($_ENV['LANEFUL_WORKSPACE_ID'] ?? 1);

try {
    $list = $client->listDomains($workspaceId, new ListDomainsParams(limit: 50));
    echo "Domains: " . count($list->domains) . "\n";

    $domain = $client->createDomain($workspaceId, new CreateDomainRequest(
        domain: 'mydomain.com',
        tracking: 'tracking',
        returnPath: 'return-path'
    ));
    echo "Created {$domain->domain}, verified=" . ($domain->verified ? 'yes' : 'no') . "\n";

    $domain = $client->verifyDomain($workspaceId, 'mydomain.com');
    echo "Verification: dmarc=" . ($domain->dmarcVerified ? 'yes' : 'no') . "\n";

    $trackId = 'e59f0a35-05bc-4516-b585-c06f69c3e67e';
    $domain = $client->updateDomain($workspaceId, 'mydomain.com', new UpdateDomainRequest($trackId));
    echo "Email track: {$domain->emailTrackId}\n";
} catch (\Laneful\Exceptions\LanefulException $e) {
    echo "Domain API error: " . $e->getMessage() . "\n";
}
