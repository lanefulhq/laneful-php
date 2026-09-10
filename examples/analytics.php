<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Laneful\LanefulClient;
use Laneful\Models\ListDomainSpamRatioRadarParams;
use Laneful\Models\ListGooglePostmasterSpamReportsParams;
use Laneful\Models\ListSndsReportsParams;

$client = new LanefulClient(
    baseUrl: $_ENV['LANEFUL_BASE_URL'] ?? 'https://api.laneful.net',
    authToken: $_ENV['LANEFUL_AUTH_TOKEN'] ?? 'your-auth-token-here'
);

try {
    $radar = $client->listDomainSpamRatioRadar(new ListDomainSpamRatioRadarParams(
        startDate: (new DateTimeImmutable('-7 days'))->format('Y-m-d'),
        endDate: (new DateTimeImmutable())->format('Y-m-d')
    ));
    foreach ($radar->radar as $entry) {
        echo "{$entry->date} {$entry->domain} @{$entry->esp}: {$entry->spamRatio}%\n";
    }

    $postmaster = $client->listGooglePostmasterSpamReports(
        new ListGooglePostmasterSpamReportsParams(domain: 'example.com')
    );
    foreach ($postmaster->spamReports as $report) {
        echo "{$report->date} {$report->domain}: {$report->spamRatio}%\n";
    }

    $snds = $client->listSndsReports(new ListSndsReportsParams());
    foreach ($snds->sndsReports as $report) {
        echo "{$report->date} {$report->ip}: filter={$report->filterResult} complaint={$report->complaintRate}%\n";
    }
} catch (\Laneful\Exceptions\LanefulException $e) {
    echo "Analytics API error: " . $e->getMessage() . "\n";
}
