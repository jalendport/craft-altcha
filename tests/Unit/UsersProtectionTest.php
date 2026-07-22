<?php

use jalendport\altcha\Altcha as AltchaPlugin;
use jalendport\altcha\integrations\Users;
use jalendport\altcha\services\Altcha as AltchaService;

/**
 * Wires up the two globals `Users::_isProtected()` reads — the plugin's settings
 * and the request's `userId` param — so the private gate can be exercised
 * without booting Craft.
 */
function altchaUsersGate(bool $enableRegistration, mixed $userIdParam): bool
{
    $settings = altchaSettings();
    $settings->enableUserRegistration = $enableRegistration;
    $settings->protectLogin = false;
    $settings->protectForgotPassword = false;

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
    $method->setAccessible(true);

    return $method->invoke(null, 'save-user');
}

// Craft treats a falsy `userId` as a new public registration ($isNewUser =
// !$userId), so every falsy value must be held to a challenge — a strict
// null-only check would let a bot skip verification by posting `userId=` or
// `userId=0`.
test('registration is protected regardless of userId shape', function(mixed $userIdParam): void {
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
