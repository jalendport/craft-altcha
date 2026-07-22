<?php

require_once dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
require_once dirname(__DIR__) . '/vendor/craftcms/cms/src/Craft.php';

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\Solution;
use jalendport\altcha\models\Settings;
use jalendport\altcha\services\Altcha;
use yii\caching\ArrayCache;

function altchaSettings(string $hmacKey = 'test-hmac-key', int $complexity = 1): Settings
{
    return new Settings([
        'hmacKey' => $hmacKey,
        'complexity' => $complexity,
    ]);
}

function altchaService(string $hmacKey = 'test-hmac-key', ?ArrayCache $cache = null): Altcha
{
    $service = new Altcha();
    $service->setSettings(altchaSettings($hmacKey));
    $service->setCache($cache ?? new ArrayCache());

    return $service;
}

function altchaSolve(Challenge $challenge): string
{
    $algorithm = new Pbkdf2();
    $parameters = $challenge->parameters;
    $nonce = hex2bin($parameters->nonce) ?: '';
    $salt = hex2bin($parameters->salt) ?: '';
    $keyPrefix = hex2bin($parameters->keyPrefix) ?: '';
    $keyPrefixLength = strlen($keyPrefix);

    for ($counter = 0; $counter <= 100000; $counter++) {
        $password = $nonce . pack('N', $counter);
        $derivedKey = $algorithm->deriveKey($parameters, $salt, $password)->derivedKey;

        if (substr($derivedKey, 0, $keyPrefixLength) === $keyPrefix) {
            return (new Payload(
                $challenge,
                new Solution($counter, bin2hex($derivedKey)),
            ))->toBase64();
        }
    }

    throw new RuntimeException('Unable to solve the test challenge.');
}

afterEach(function(): void {
    Craft::$app = null;
});
