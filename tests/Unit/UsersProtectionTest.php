<?php

use jalendport\altcha\Altcha as AltchaPlugin;
use jalendport\altcha\integrations\Users;
use jalendport\altcha\services\Altcha as AltchaService;

function altchaUsersGate(bool $enableRegistration, mixed $userIdParam): bool
{
    $settings = altchaSettings();
    $settings->enableUserRegistration = $enableRegistration;
    $settings->enableUserLogin = false;
    $settings->enableUserForgotPassword = false;

    $service = new AltchaService();
    $service->setSettings($settings);

    $plugin = (new ReflectionClass(AltchaPlugin::class))->newInstanceWithoutConstructor();
    $plugin->set('altcha', $service);
    AltchaPlugin::$plugin = $plugin;

    Craft::$app = new class($userIdParam) {
        public function __construct(private mixed $userIdParam)
        {
        }

        public function getRequest(): object
        {
            return new class($this->userIdParam) {
                public function __construct(private mixed $userIdParam)
                {
                }

                public function post(string $name): mixed
                {
                    return $name === 'userId' ? $this->userIdParam : null;
                }
            };
        }
    };

    $method = new ReflectionMethod(Users::class, '_isProtected');

    return $method->invoke(null, 'save-user');
}

test('Craft treats each falsy userId as a new registration that requires protection', function(mixed $userIdParam): void {
    expect(altchaUsersGate(true, $userIdParam))->toBeTrue();
})->with([
    'absent' => [null],
    'empty string' => [''],
    'zero string' => ['0'],
    'zero int' => [0],
]);

test('editing an existing user is not protected', function(): void {
    expect(altchaUsersGate(true, '5'))->toBeFalse();
});

test('registration protection respects the toggle', function(): void {
    expect(altchaUsersGate(false, null))->toBeFalse();
});
