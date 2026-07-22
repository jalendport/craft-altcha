<?php
/**
 * Altcha — Spark Craft Lab seed hook
 *
 * Altcha is useless without an HMAC key, and the lab's throwaway install has no
 * .env to point one at. This hook writes a `config/altcha.php` (see
 * src/config.php for the full template) with a hardcoded lab-only key and a low
 * proof-of-work cost, so /lab-test can issue real challenges and the embedded
 * widget solves in a blink. Integration toggles stay off — the smoke page only
 * exercises the challenge endpoint and the Twig variable.
 *
 * @link https://github.com/jalendport/spark-craft-lab
 */

return function ($controller = null): void {
    $out = static function (string $message) use ($controller): void {
        if ($controller !== null && method_exists($controller, 'stdout')) {
            $controller->stdout("  [altcha] $message\n");
        }
    };

    $configPath = Craft::getAlias('@config') . '/altcha.php';

    $config = <<<'PHP'
<?php

/**
 * Lab-only Altcha config. The HMAC key below is a throwaway fixture value that
 * only ever lives inside a disposable lab instance — a real site sets this to
 * an environment variable, e.g. '$ALTCHA_HMAC_KEY'.
 */

return [
    'hmacKey' => 'lab-4f9c1ba27e6d48a0b3517cde90f2a86d',
    'challengeExpiryMinutes' => 20,

    // The cheapest supported cost, so the widget solves near-instantly while
    // you're poking at the page.
    'complexity' => 1000,
    'registerWidgetJs' => true,

    // Integrations stay off; the lab smoke page covers the challenge endpoint
    // and the `craft.altcha` Twig variable.
    'enableComments' => false,
    'enableContactForm' => false,
    'enableUserRegistration' => false,
    'protectLogin' => false,
    'protectForgotPassword' => false,
    'enableGuestEntries' => false,
    'enableBlanketMode' => false,
    'blanketActionAllowlist' => [],

    'widgetDisplay' => 'standard',
    'widgetAuto' => 'off',
    'widgetTheme' => '',
    'widgetHideLogo' => true,
    'widgetHideFooter' => true,
];

PHP;

    // Rewritten every run rather than guarded: the file is fully owned by the
    // lab, so overwriting it is the idempotent outcome.
    file_put_contents($configPath, $config);
    $out("Wrote $configPath");
};
