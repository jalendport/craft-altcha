<?php
/**
 * Altcha — Spark Craft Lab seed hook
 *
 * Altcha only matters where other plugins host forms, so the lab sets up every
 * compatible integration with a sample form behind /lab-test?page=…. The hook
 * runs in two passes, readtime-style:
 *
 * 1. Bootstrap — the lab only installs the plugin under test, so on first run
 *    the integration plugins (Formie, Comments, Contact Form, Guest Entries)
 *    aren't in vendor. This pass composer-requires and installs them inside
 *    the instance — version constraints come from the plugin's own
 *    require-dev, so composer.json stays the single source of truth — then
 *    re-runs the seed in a fresh process (so the new autoloader is live).
 * 2. Setup — with all plugins loadable: make sure each is installed, allow
 *    guest comments, point Contact Form at Mailpit's catcher, open the
 *    labArticles section to Guest Entries, enable public registration, enable
 *    Altcha as a Formie captcha, and create the `labForm` Formie form.
 *
 * The config written below keeps a hardcoded lab-only HMAC key and the lowest
 * proof-of-work cost so widgets solve in a blink, and switches every
 * integration toggle on. Blanket mode allowlists exactly `entries/save-entry`
 * — deliberately an action no integration covers, because the same payload
 * verified twice trips replay protection.
 *
 * @link https://github.com/jalendport/spark-craft-lab
 */

use verbb\formie\elements\Form;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;

return function ($controller = null): void {
    $out = static function (string $message) use ($controller): void {
        if ($controller !== null && method_exists($controller, 'stdout')) {
            $controller->stdout("  [altcha] $message\n");
        }
    };

    $fail = static function (string $label, $model): void {
        throw new RuntimeException(sprintf(
            'Could not save %s: %s',
            $label,
            json_encode($model->getErrors()) ?: 'unknown validation error',
        ));
    };

    // Written every run rather than guarded: the file is fully owned by the
    // lab, so overwriting it is the idempotent outcome.
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

    // Every integration on, so each /lab-test?page=… flow works out of the
    // box. Flip these off here to smoke the toggles-off behavior.
    'enableComments' => true,
    'enableContactForm' => true,
    'enableUserRegistration' => true,
    'protectLogin' => true,
    'protectForgotPassword' => true,
    'enableGuestEntries' => true,

    // The allowlist stays disjoint from the integration-covered actions: a
    // payload verified by an integration and again by blanket mode would be
    // rejected as a replay.
    'enableBlanketMode' => true,
    'blanketActionAllowlist' => ['entries/save-entry'],

    'widgetDisplay' => 'standard',
    'widgetAuto' => 'off',
    'widgetTheme' => '',
    'widgetHideLogo' => true,
    'widgetHideFooter' => true,
];

PHP;

    file_put_contents($configPath, $config);
    $out("Wrote $configPath");

    $root = Craft::getAlias('@root');
    $run = static function (string $command) use ($root, $out): void {
        // This process's pending project-config changes hold a lock that would
        // block any child craft process — flush (and thereby release the lock)
        // before shelling out.
        Craft::$app->getProjectConfig()->saveModifiedConfigData();

        $out("> $command");
        exec(sprintf('cd %s && %s 2>&1', escapeshellarg($root), $command), $output, $code);

        if ($code !== 0) {
            throw new RuntimeException("Command failed ($code): $command\n" . implode("\n", $output));
        }
    };

    $supportedPlugins = [
        'formie' => ['package' => 'verbb/formie', 'class' => 'verbb\formie\Formie'],
        'comments' => ['package' => 'verbb/comments', 'class' => 'verbb\comments\Comments'],
        'contact-form' => ['package' => 'craftcms/contact-form', 'class' => 'craft\contactform\Plugin'],
        'guest-entries' => ['package' => 'craftcms/guest-entries', 'class' => 'craft\guestentries\Plugin'],
    ];

    // The version constraints live in the plugin's own require-dev; read them
    // from the mounted working copy so there's one place to bump them.
    $pluginDir = getenv('LAB_PLUGIN_DIR') ?: '/plugin';
    $composerJson = json_decode((string)file_get_contents($pluginDir . '/composer.json'), true);
    $requireDev = is_array($composerJson) ? ($composerJson['require-dev'] ?? []) : [];

    // Phase 1 — bootstrap missing integration plugins into the instance.
    $missing = array_filter($supportedPlugins, static fn(array $plugin): bool => !class_exists($plugin['class']));

    if ($missing !== []) {
        if (getenv('LAB_ALTCHA_BOOTSTRAPPED') !== false) {
            throw new RuntimeException(sprintf(
                'Integration plugins still missing after bootstrap: %s',
                implode(', ', array_keys($missing)),
            ));
        }

        $packages = array_map(
            static fn(array $plugin): string => escapeshellarg(
                $plugin['package'] . ':' . ($requireDev[$plugin['package']] ?? '*'),
            ),
            array_values($missing),
        );
        $run('composer require ' . implode(' ', $packages) . ' --no-interaction --no-progress');

        foreach (array_keys($missing) as $handle) {
            $run("php craft plugin/install $handle");
        }

        // Re-run the full seed in a fresh process so the new packages autoload;
        // this closure then takes the phase-2 path there.
        $run('LAB_ALTCHA_BOOTSTRAPPED=1 php craft lab/seed');

        return;
    }

    // Phase 2 — plugins are loadable; make sure they're installed (idempotent).
    $plugins = Craft::$app->getPlugins();

    foreach (array_keys($supportedPlugins) as $handle) {
        if (!$plugins->isPluginInstalled($handle)) {
            $out("Installing $handle");
            $plugins->installPlugin($handle);
        }
    }

    $savePluginSettings = static function (string $handle, array $settings) use ($plugins, $fail): void {
        $plugin = $plugins->getPlugin($handle);

        if ($plugin === null) {
            throw new RuntimeException("Plugin $handle is not available to configure.");
        }

        if (!$plugins->savePluginSettings($plugin, $settings)) {
            $fail("$handle settings", $plugin->getSettings());
        }
    };

    // Comments: let anonymous visitors post, skip moderation so a saved
    // comment is immediately visible on the test page, and turn off the
    // plugin's own honeypot — the lab form is a raw POST, and the thing under
    // test here is Altcha's verification, not Comments' native spam check.
    $savePluginSettings('comments', [
        'allowGuest' => true,
        'requireModeration' => false,
        'enableSpamChecks' => false,
    ]);
    $out('Comments: guest commenting on, moderation and native spam checks off');

    // Contact Form: any recipient works — the instance's mailer delivers
    // everything to Mailpit.
    $savePluginSettings('contact-form', [
        'toEmail' => 'lab@lab.test',
    ]);
    $out('Contact Form: toEmail set');

    // Guest Entries: open the generic articles section to guest submissions,
    // authored as the lab admin.
    $section = Craft::$app->getEntries()->getSectionByHandle('labArticles');
    $author = Craft::$app->getUsers()->getUserByUsernameOrEmail('admin');

    if ($section === null || $author === null) {
        throw new RuntimeException('Generic lab seed missing: labArticles section or admin user not found.');
    }

    $savePluginSettings('guest-entries', [
        'sections' => [
            $section->uid => [
                'sectionUid' => $section->uid,
                'allowGuestSubmissions' => true,
                'authorUid' => $author->uid,
                'enabledByDefault' => true,
                'runValidation' => false,
            ],
        ],
    ]);
    $out('Guest Entries: labArticles open to guests');

    // Users: public registration with no verification step, so a registered
    // smoke user is immediately active and can log straight in.
    $projectConfig = Craft::$app->getProjectConfig();
    $projectConfig->set('users.allowPublicRegistration', true);
    $projectConfig->set('users.requireEmailVerification', false);
    $out('Users: public registration on, email verification off');

    // Formie: enable Altcha in the global captcha settings, then make sure the
    // labForm form exists with the captcha on.
    $formieIntegrations = Formie::$plugin->getIntegrations();
    $captcha = $formieIntegrations->getCaptchaByHandle('altcha');

    if ($captcha === null) {
        throw new RuntimeException('Formie did not report the Altcha captcha — is the plugin registered?');
    }

    if (!$captcha->getEnabled()) {
        $captcha->setEnabled(true);
        $formieIntegrations->saveCaptcha($captcha);
        $out('Formie: Altcha captcha enabled globally');
    }

    $form = Formie::$plugin->getForms()->getFormByHandle('labForm');

    if ($form === null) {
        $form = new Form();
        $form->title = 'Lab Form';
        $form->handle = 'labForm';
        $form->getFormLayout()->setPages([
            [
                'label' => 'Page 1',
                'settings' => [],
                'rows' => [
                    [
                        'fields' => [
                            [
                                'type' => SingleLineText::class,
                                'label' => 'Your Name',
                                'handle' => 'yourName',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $form->settings->integrations['altcha']['enabled'] = true;

        if (!Craft::$app->getElements()->saveElement($form)) {
            $fail('Formie form labForm', $form);
        }

        $out('Formie: created labForm');
    } elseif (!($form->settings->integrations['altcha']['enabled'] ?? false)) {
        $form->settings->integrations['altcha']['enabled'] = true;

        if (!Craft::$app->getElements()->saveElement($form)) {
            $fail('Formie form labForm', $form);
        }

        $out('Formie: enabled Altcha on labForm');
    }
};
