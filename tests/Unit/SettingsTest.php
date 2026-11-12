<?php

use jalendport\altcha\models\Settings;

test('settings defaults pass validation when the required HMAC key is configured', function(): void {
    expect((new Settings(['hmacKey' => 'test-hmac-key']))->validate())->toBeTrue();
});

test('deprecated setting attributes set the new names', function(): void {
    $settings = new Settings();
    $settings->setAttributes(['protectLogin' => true, 'protectForgotPassword' => true], false);

    expect($settings->enableUserLogin)->toBeTrue()
        ->and($settings->enableUserForgotPassword)->toBeTrue();
});

test('deprecated setting attributes take priority over the new names', function(string $oldName, string $newName): void {
    $settings = new Settings();
    $settings->setAttributes([$newName => false, $oldName => true], false);

    expect($settings->$newName)->toBeTrue();
})->with([
    ['protectLogin', 'enableUserLogin'],
    ['protectForgotPassword', 'enableUserForgotPassword'],
]);

test('deprecated settings work through constructor and magic access', function(string $oldName, string $newName): void {
    $settings = new Settings(['hmacKey' => 'k', $oldName => true]);

    expect($settings->$newName)->toBeTrue()
        ->and($settings->$oldName)->toBeTrue();

    $settings->$oldName = false;

    expect($settings->$newName)->toBeFalse()
        ->and($settings->$oldName)->toBeFalse();
})->with([
    ['protectLogin', 'enableUserLogin'],
    ['protectForgotPassword', 'enableUserForgotPassword'],
]);

test('only the new setting names are attributes and serialized', function(): void {
    $settings = new Settings();

    expect($settings->attributes())->toContain('enableUserLogin', 'enableUserForgotPassword')
        ->not->toContain('protectLogin', 'protectForgotPassword')
        ->and($settings->toArray())->toHaveKeys(['enableUserLogin', 'enableUserForgotPassword'])
        ->not->toHaveKey('protectLogin')
        ->not->toHaveKey('protectForgotPassword');
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
