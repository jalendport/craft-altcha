<?php

use jalendport\altcha\models\Settings;

test('settings defaults pass validation when the required HMAC key is configured', function(): void {
    expect((new Settings(['hmacKey' => 'test-hmac-key']))->validate())->toBeTrue();
});

test('invalid settings fail validation', function(Closure $configure, string $attribute): void {
    $settings = new Settings(['hmacKey' => 'test-hmac-key']);
    $configure($settings);

    expect($settings->validate())->toBeFalse()
        ->and($settings->hasErrors($attribute))->toBeTrue();
})->with([
    'widget display' => [
        function(Settings $settings): void {
            $settings->widgetDisplay = 'modal';
        },
        'widgetDisplay',
    ],
    'widget auto' => [
        function(Settings $settings): void {
            $settings->widgetAuto = 'always';
        },
        'widgetAuto',
    ],
    'complexity' => [
        function(Settings $settings): void {
            $settings->complexity = 999;
        },
        'complexity',
    ],
    'challenge expiry' => [
        function(Settings $settings): void {
            $settings->challengeExpiryMinutes = 0;
        },
        'challengeExpiryMinutes',
    ],
]);
