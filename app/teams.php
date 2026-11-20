<?php
/**
 * Microsoft Teams: posts Adaptive Cards to a channel through a Teams Workflows webhook
 * ("Post to a channel when a webhook request is received").
 */
declare(strict_types=1);

function teams_webhook_url(): string
{
    return decrypt_secret(setting('teams_webhook_url'));
}

function teams_configured(): bool
{
    return teams_webhook_url() !== '';
}

/** Post one Adaptive Card. Returns null on success or an error message. */
function teams_post(array $cardBody, array $actions = []): ?string
{
    $url = teams_webhook_url();
    if ($url === '') {
        return 'Teams webhook URL is not set.';
    }
    $card = [
        '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
        'type'    => 'AdaptiveCard',
        'version' => '1.4',
        'msteams' => ['width' => 'Full'],
        'body'    => $cardBody,
    ];
    if ($actions) {
        $card['actions'] = $actions;
    }
    $payload = json_encode([
        'type'        => 'message',
        'attachments' => [['contentType' => 'application/vnd.microsoft.card.adaptive', 'contentUrl' => null, 'content' => $card]],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    [$code, $body, $err] = http_post_json($url, $payload);
    if ($err) {
        return 'Teams post failed: ' . $err;
    }
    if ($code < 200 || $code >= 300) {
        return "Teams post failed (HTTP $code): " . mb_strimwidth(trim(strip_tags((string)$body)), 0, 300, '…');
    }
    return null;
}

/** @return array{0:int,1:string,2:?string} [status code, body, transport error] */
function http_post_json(string $url, string $json): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $body = curl_exec($ch);
        $err = $body === false ? curl_error($ch) : null;
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$code, (string)$body, $err];
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $json,
        'timeout' => 15, 'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
            $code = (int)$m[1];
        }
    }
    return [$code, (string)$body, $body === false && !$code ? 'Could not connect' : null];
}

/* ---------- Card helpers ---------- */

function card_text(string $text, array $opts = []): array
{
    return array_merge(['type' => 'TextBlock', 'text' => $text, 'wrap' => true], $opts);
}

function card_facts(array $facts): array
{
    $out = [];
    foreach ($facts as $k => $v) {
        $out[] = ['title' => (string)$k, 'value' => (string)$v];
    }
    return ['type' => 'FactSet', 'facts' => $out];
}

function card_open_url(string $title, string $url): array
{
    return ['type' => 'Action.OpenUrl', 'title' => $title, 'url' => $url];
}
