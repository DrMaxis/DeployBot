<?php

declare(strict_types=1);

use Afria\Deploybot\Slack\SlackSignatureVerifier;

/**
 * Covers the full decision matrix for the signature check — valid,
 * replay, tampered, malformed, plus the guard against empty config.
 * These are pure-function tests; no Laravel container involved.
 */
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

    // 10 minutes later → outside the 5-minute window.
    $laterNow = $originalTs + 601;

    expect($verifier->isValid('body', (string) $originalTs, $sig, now: $laterNow))->toBeFalse();
});

it('rejects a future-dated request outside the skew window (skew-symmetric)', function (): void {
    $verifier = new SlackSignatureVerifier(signingSecret: 'secret', maxSkewSeconds: 60);
    $ts = 1_700_000_000;
    $sig = $verifier->sign('body', $ts);

    // Receiver's clock is 2 minutes BEHIND the request timestamp. Most
    // attacks aren't in the "future" direction but skew should reject
    // symmetrically.
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

    // Even if we somehow computed a valid HMAC with an empty secret,
    // the `signingSecret === ''` short-circuit above rejects outright.
    expect($verifier->isValid('body', (string) $ts, 'v0='.hash_hmac('sha256', 'v0:'.$ts.':body', ''), now: $ts))
        ->toBeFalse();
});

it('rejects missing headers', function (): void {
    $verifier = new SlackSignatureVerifier(signingSecret: 'secret');

    expect($verifier->isValid('body', '', 'v0=abc', now: 123))->toBeFalse();
    expect($verifier->isValid('body', '123', '', now: 123))->toBeFalse();
});
