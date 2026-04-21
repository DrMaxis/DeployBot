<?php

declare(strict_types=1);

use Afria\Deploybot\Slack\SlackSignatureVerifier;

/**
 * End-to-end test of the full webhook path: HTTP POST → middleware →
 * controller → dispatcher → built-in HelpCommand → JSON response.
 *
 * Exercises the signature middleware's happy path (sign the body
 * correctly, expect 200) and its rejection path (bad signature → 401).
 */
it('accepts a correctly signed slash-command request and returns the dispatcher response', function (): void {
    $body = http_build_query([
        'team_id' => 'T1',
        'team_domain' => 'afriatech',
        'channel_id' => 'C1',
        'channel_name' => 'general',
        'user_id' => 'U1',
        'user_name' => 'antwi',
        'command' => '/alverium',
        'text' => 'help',
        'response_url' => 'https://hooks.slack.com/…',
        'trigger_id' => 'trig1',
    ]);

    $ts = time();
    $verifier = new SlackSignatureVerifier(signingSecret: 'test-signing-secret');
    $sig = $verifier->sign($body, $ts);

    $response = $this->call(
        method: 'POST',
        uri: '/deploybot/slack/command',
        parameters: [],
        cookies: [],
        files: [],
        server: [
            'HTTP_X-Slack-Request-Timestamp' => (string) $ts,
            'HTTP_X-Slack-Signature' => $sig,
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ],
        content: $body,
    );

    expect($response->status())->toBe(200);
    $json = $response->json();
    expect($json)->toHaveKey('response_type');
    expect($json['response_type'])->toBe('ephemeral');
    expect($json['text'])->toContain('Available commands');
});

it('rejects an unsigned slash-command request with 401', function (): void {
    $response = $this->call(
        method: 'POST',
        uri: '/deploybot/slack/command',
        parameters: ['team_id' => 'T1', 'text' => 'help'],
    );

    expect($response->status())->toBe(401);
    expect($response->json('error'))->toContain('Invalid Slack signature');
});

it('rejects a slash-command request with a tampered body', function (): void {
    $body = 'team_id=T1&text=help';
    $ts = time();
    $verifier = new SlackSignatureVerifier(signingSecret: 'test-signing-secret');
    $sig = $verifier->sign($body, $ts);

    $response = $this->call(
        method: 'POST',
        uri: '/deploybot/slack/command',
        parameters: [],
        cookies: [],
        files: [],
        server: [
            'HTTP_X-Slack-Request-Timestamp' => (string) $ts,
            'HTTP_X-Slack-Signature' => $sig,
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ],
        content: 'team_id=T1&text=tampered',
    );

    expect($response->status())->toBe(401);
});
