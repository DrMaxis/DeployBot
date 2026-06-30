<?php

declare(strict_types=1);

use DrMaxis\Deploybot\Slack\SlackSignatureVerifier;

it('accepts a correctly signed request within the skew window', function (): void {
    $verifier = new SlackSignatureVerifier(signingSecret: 'secret');
    $ts = 1_700_000_000;
    $body = 'token=abc&team_id=T1&user_id=U1';
    $sig = $verifier->sign($body, $ts);

    expect($verifier->isValid($body, (string) $ts, $sig, now: $ts))->toBeTrue();
});

it('rejects a tampered body even with a valid-looking signature', function (): void {
    $verifier = new SlackSignatureVerifier(signingSecret: 'secret');
    $ts = 1_700_000_000;
    $sig = $verifier->sign('original body', $ts);

    expect($verifier->isValid('tampered body', (string) $ts, $sig, now: $ts))->toBeFalse();
});

it('rejects a signature computed with a different secret', function (): void {
    $ours = new SlackSignatureVerifier(signingSecret: 'ours');
    $theirs = new SlackSignatureVerifier(signingSecret: 'theirs');
    $ts = 1_700_000_000;
    $body = 'body';

    $theirSig = $theirs->sign($body, $ts);

    expect($ours->isValid($body, (string) $ts, $theirSig, now: $ts))->toBeFalse();
});

it('rejects a replayed request older than the skew window', function (): void {
    $verifier = new SlackSignatureVerifier(signingSecret: 'secret', maxSkewSeconds: 300);
    $originalTs = 1_700_000_000;
    $sig = $verifier->sign('body', $originalTs);

    $laterNow = $originalTs + 601;

    expect($verifier->isValid('body', (string) $originalTs, $sig, now: $laterNow))->toBeFalse();
});

it('rejects a future-dated request outside the skew window (skew-symmetric)', function (): void {
    $verifier = new SlackSignatureVerifier(signingSecret: 'secret', maxSkewSeconds: 60);
    $ts = 1_700_000_000;
    $sig = $verifier->sign('body', $ts);
    $earlierNow = $ts - 121;

    expect($verifier->isValid('body', (string) $ts, $sig, now: $earlierNow))->toBeFalse();
});

it('rejects a malformed signature header (missing v0= prefix)', function (): void {
    $verifier = new SlackSignatureVerifier(signingSecret: 'secret');
    $ts = 1_700_000_000;

    expect($verifier->isValid('body', (string) $ts, 'deadbeef', now: $ts))->toBeFalse();
});

it('rejects a non-numeric timestamp', function (): void {
    $verifier = new SlackSignatureVerifier(signingSecret: 'secret');

    expect($verifier->isValid('body', 'not-a-timestamp', 'v0=deadbeef', now: 123))->toBeFalse();
});

it('rejects when the signing secret is empty (fail-closed on misconfig)', function (): void {
    $verifier = new SlackSignatureVerifier(signingSecret: '');
    $ts = 1_700_000_000;

    expect($verifier->isValid('body', (string) $ts, 'v0='.hash_hmac('sha256', 'v0:'.$ts.':body', ''), now: $ts))
        ->toBeFalse();
});

it('rejects missing headers', function (): void {
    $verifier = new SlackSignatureVerifier(signingSecret: 'secret');

    expect($verifier->isValid('body', '', 'v0=abc', now: 123))->toBeFalse();
    expect($verifier->isValid('body', '123', '', now: 123))->toBeFalse();
});
