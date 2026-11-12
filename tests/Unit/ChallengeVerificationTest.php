<?php

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha as AltchaClient;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\CreateChallengeOptions;

test('challenge solutions complete a round trip', function(): void {
    $service = altchaService();
    $challenge = $service->createChallenge();

    expect($challenge)->toBeInstanceOf(Challenge::class);

    if (!$challenge instanceof Challenge) {
        throw new RuntimeException('Challenge issuance failed.');
    }

    expect($service->verifySolution(altchaSolve($challenge)))->toBeTrue();
});

test('expired challenge solutions fail verification', function(): void {
    $client = new AltchaClient(hmacSignatureSecret: 'test-hmac-key');
    $challenge = $client->createChallenge(new CreateChallengeOptions(
        algorithm: new Pbkdf2(),
        cost: 1,
        expiresAt: time() - 1,
    ));

    expect(altchaService()->verifySolution(altchaSolve($challenge)))->toBeFalse();
});

test('challenge solutions cannot be replayed', function(): void {
    $service = altchaService(cache: new yii\caching\ArrayCache());
    $challenge = $service->createChallenge();

    if (!$challenge instanceof Challenge) {
        throw new RuntimeException('Challenge issuance failed.');
    }

    $payload = altchaSolve($challenge);

    expect($service->verifySolution($payload))->toBeTrue()
        ->and($service->verifySolution($payload))->toBeFalse();
});

test('re-encoded challenge solutions cannot be replayed', function(): void {
    $service = altchaService(cache: new yii\caching\ArrayCache());
    $challenge = $service->createChallenge();

    if (!$challenge instanceof Challenge) {
        throw new RuntimeException('Challenge issuance failed.');
    }

    $payload = altchaSolve($challenge);
    $json = base64_decode($payload, true);
    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    $extraKey = $data;
    $extraKey['extra'] = 'ignored';
    $reordered = $data;
    $reordered['solution'] = array_reverse($reordered['solution'], true);

    expect($service->verifySolution($payload))->toBeTrue()
        ->and($service->verifySolution(base64_encode($json . ' ')))->toBeFalse()
        ->and($service->verifySolution(base64_encode(json_encode($extraKey, JSON_THROW_ON_ERROR))))->toBeFalse()
        ->and($service->verifySolution(base64_encode(json_encode($reordered, JSON_THROW_ON_ERROR))))->toBeFalse();
});

test('unusable HMAC keys fail closed', function(string $hmacKey): void {
    $service = altchaService($hmacKey);

    expect($service->createChallenge())->toBeNull()
        ->and($service->verifySolution('payload'))->toBeFalse();
})->with([
    'empty key' => '',
    'unresolved environment key' => '$UNRESOLVED_ENV',
]);

test('malformed payloads fail without throwing', function(string $payload): void {
    expect(altchaService()->verifySolution($payload))->toBeFalse();
})->with([
    'empty payload' => '',
    'garbage base64' => 'not-base64!',
    'invalid JSON' => base64_encode('not JSON'),
]);

test('challenges signed with the wrong key fail verification', function(): void {
    $client = new AltchaClient(hmacSignatureSecret: 'signing-key');
    $challenge = $client->createChallenge(new CreateChallengeOptions(
        algorithm: new Pbkdf2(),
        cost: 1,
    ));

    expect(altchaService('different-key')->verifySolution(altchaSolve($challenge)))->toBeFalse();
});
