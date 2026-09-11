<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Laneful\LanefulClient;
use Laneful\Models\ListUnsubscribeGroupsParams;

$client = new LanefulClient(
    baseUrl: $_ENV['LANEFUL_BASE_URL'] ?? 'https://api.laneful.net',
    authToken: $_ENV['LANEFUL_AUTH_TOKEN'] ?? 'your-auth-token-here'
);

$workspaceId = (int) ($_ENV['LANEFUL_WORKSPACE_ID'] ?? 1);

try {
    $created = $client->createUnsubscribeGroup($workspaceId, 'Newsletters');
    echo "Created group {$created->unsubscribeGroupId}: {$created->name}\n";

    $updated = $client->updateUnsubscribeGroup(
        $workspaceId,
        $created->unsubscribeGroupId,
        'Weekly Newsletters'
    );
    echo "Updated name: {$updated->name}\n";

    $list = $client->listUnsubscribeGroups(
        $workspaceId,
        new ListUnsubscribeGroupsParams(limit: 50)
    );
    foreach ($list->unsubscribeGroups as $group) {
        echo "- {$group->unsubscribeGroupId} {$group->name}\n";
    }
} catch (\Laneful\Exceptions\LanefulException $e) {
    echo "Unsubscribe group API error: " . $e->getMessage() . "\n";
}
