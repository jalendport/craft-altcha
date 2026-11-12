<p align="center"><img src="src/icon.svg" alt="Altcha" width="80" height="80"></p>

<h1 align="center">Altcha</h1>

Altcha is a Craft CMS plugin that protects your forms with [ALTCHA](https://altcha.org/) proof-of-work spam protection. The visitor’s browser solves a small cryptographic challenge that your own Craft install issues and verifies, so there’s no third-party service, no cookies, and no tracking to disclose under GDPR.

## Features

- **Formie captcha**: registers as a native captcha in [Formie](https://verbb.io/craft-plugins/formie) that you switch on per form in Formie’s captcha settings, and the widget is injected into the form for you.
- **Integrations**: [Comments](https://verbb.io/craft-plugins/comments), [Contact Form](https://github.com/craftcms/contact-form), [Guest Entries](https://github.com/craftcms/guest-entries), and Craft’s own registration, login, and forgot-password forms, each behind its own toggle.
- **Blanket mode**: enforces a solved challenge on any front-end POST that you list by action path, for form plugins with no captcha API of their own.
- **Replay protection**: a solved payload is accepted exactly once, so a captured submission can’t be posted again.
- **HMAC key from the environment**: a console command generates the signing key into your `.env`, so the secret never lands in project config.
- **Bundled widget**: ALTCHA’s widget v3 is vendored into the plugin’s asset bundle, so nothing loads from a CDN.
- **Widget language and text**: the widget uses the current site’s language, and your site’s translation files can change each string.
- **Per-widget options**: override the display mode, theme, auto-solve behavior, or any other widget attribute for one form.
- **Off by default**: every integration is off, so installing the plugin changes nothing on your site until you opt a form in.

## Installation

### Requirements

This plugin requires **Craft CMS 5.0.0 or later** and **PHP 8.2 or later**.

### Plugin Store

In the control panel, go to “Plugin Store”, search for “Altcha”, and click “Install”.

### Composer

Run this from your Craft project:

```bash
composer require jalendport/craft-altcha && php craft plugin/install altcha
```

## Usage

### Generate an HMAC key

Altcha signs each challenge with an HMAC key and verifies solutions against the same key. There’s no default, and until you set one the plugin fails closed (it refuses to issue or verify anything).

Generate a key from your project root:

```bash
php craft altcha/generate-hmac-key
```

The command writes a random 32-byte key to your `.env` file as `ALTCHA_HMAC_KEY`. Then go to **Settings → Altcha → General Settings** and set **HMAC key** to `$ALTCHA_HMAC_KEY`, so the setting holds the variable’s name and not the secret. Each environment needs its own key, because a solution signed with one key won’t verify against another.

### Rendering the widget

Add the widget to any form template:

```twig
<form method="post">
	{{ csrfInput() }}
	{{ actionInput('…') }}

	{# … your fields … #}

	{{ craft.altcha.renderWidget() }}

	<button type="submit">Submit</button>
</form>
```

The widget renders as an `<altcha-widget>` element that fetches a fresh challenge from the plugin’s action URL, solves it in the browser, and posts the result as a hidden `altcha` field. Every check the plugin performs reads that field, so the widget must sit *inside* the `<form>` it protects (Formie is the exception, since it injects the widget itself).

Use one widget per form. If you render several inside one `<form>`, give each a distinct `name` attribute and verify them yourself, because the built-in integrations read only the payload posted as `altcha`.

#### Per-call options

Each key in the hash you pass to `renderWidget()` becomes an attribute on `<altcha-widget>` and wins over the configured default for that form. Any attribute that the bundled ALTCHA widget v3 knows is allowed:

```twig
{{ craft.altcha.renderWidget({
	display: 'bar',
	auto: 'onfocus',
	theme: 'dark',
}) }}
```

A value of `true` renders a bare attribute, and `false` or `null` leaves the attribute out. The widget settings map to these options: `widgetDisplay` → `display`, `widgetAuto` → `auto`, `widgetTheme` → `theme`, `widgetHideLogo` → `configuration.hideLogo`, and `widgetHideFooter` → `configuration.hideFooter`.

The logo and footer settings aren’t attributes in the widget’s v3 API but configuration properties, so the plugin sends them in a JSON-encoded `configuration` attribute. A `configuration` hash that you pass merges into that JSON:

```twig
{{ craft.altcha.renderWidget({
	configuration: {
		hideLogo: false,
		hideFooter: false,
	},
}) }}
```

#### Widget language

The bundled widget includes ALTCHA’s translations and uses the current site’s language. A regional language code also matches its primary language (so `de-AT` uses German), and English is the fallback when ALTCHA has no translation.

To force a language for one form, pass the `language` option:

```twig
{{ craft.altcha.renderWidget({ language: 'de' }) }}
```

#### Widget text

To change the widget’s text, add translations to your site’s `translations/<language>/altcha.php` file. For example, in `translations/de/altcha.php`:

```php
<?php

return [
    'widget.label' => 'Ich bin ein Mensch',
];
```

The plugin reads these entries in the widget’s language, so a widget with a different `language` option uses that language’s translation file. A string with no translation, or an empty translation, keeps ALTCHA’s own translation. This also works for a language that ALTCHA doesn’t have (strings with no translation stay English), and an English widget uses `translations/en/altcha.php` to change the English words.

The 17 message keys are `widget.ariaLinkLabel`, `widget.cancel`, `widget.enterCode`, `widget.enterCodeAria`, `widget.enterCodeFromImage`, `widget.error`, `widget.expired`, `widget.footer`, `widget.getAudioChallenge`, `widget.label`, `widget.loading`, `widget.reload`, `widget.verify`, `widget.verificationRequired`, `widget.verified`, `widget.verifying`, and `widget.waitAlert`. The `widget.footer` string is HTML; the others are text.

These overrides apply to every widget with the same language, including Formie forms, and also when you load the ALTCHA script yourself with `registerWidgetJs` set to `false`. They use an inline script, so a site with a strict Content Security Policy must permit it.

#### Widget errors

By default, visitors see network and server errors as `Error: <message>` (e.g. `Error: Server responded with 500.`), and other errors as the widget’s error text. When the site translates `widget.error`, the widget shows that text for every error. For other behavior, call `configure({ formatError })` on the widget element in your own JavaScript.

### Formie

Go to **Formie → Settings → Captchas**, enable **Altcha**, and then switch it on per form in each form’s captcha settings. Formie is the only supported form plugin with its own captcha API, so Altcha plugs straight into it.

Nothing goes in your templates, because Formie renders the widget into the form. A submission that fails verification is marked as spam with a reason, like Formie’s other captchas, and GraphQL submissions are verified too.

### Comments

Turn on **Comments** under **Settings → Altcha → Integrations**. New comments posted through the front-end form then need a solved challenge; editing, trashing, voting, flagging, and anything saved from the control panel or a console command are left alone.

The Comments plugin renders its own form, so copy its templates into your own template folder (see **Comments → Settings → Templates**) and add `{{ craft.altcha.renderWidget() }}` inside the `<form>` in `_includes/form.html`.

A comment that fails verification isn’t saved, and the form shows a validation error on the `altcha` attribute.

#### Skipping verification for a comment

The Comments integration fires an event before it verifies a comment, so you can let through comments from a form that has no widget (e.g. a members-only form):

```php
use Craft;
use jalendport\altcha\events\VerifyCommentEvent;
use jalendport\altcha\integrations\Comments;
use yii\base\Event;

Event::on(
    Comments::class,
    Comments::EVENT_BEFORE_VERIFY_COMMENT,
    function(VerifyCommentEvent $event) {
        if (Craft::$app->getUser()->getIdentity()) {
            $event->skipVerification = true;
        }
    },
);
```

The event carries the `Comment` about to be saved as `$event->comment`, and setting `skipVerification` to `true` saves the comment without looking for a solution.

### Contact Form

Turn on **Contact Form** under **Settings → Altcha → Integrations**, and add the widget to your contact form template:

```twig
<form method="post">
	{{ csrfInput() }}
	{{ actionInput('contact-form/send') }}

	{# … your fields … #}

	{{ craft.altcha.renderWidget() }}

	<button type="submit">Send</button>
</form>
```

A message that fails verification is flagged as spam rather than rejected, which is Contact Form’s own convention: the sender sees the usual success message, and the message goes nowhere.

### User registration, login, and forgot password

Three toggles under **Settings → Altcha → Integrations** protect Craft’s user forms: **Craft’s registration form** (`users/save-user`), **Craft’s login form** (`users/login`), and **Craft’s forgot-password form** (`users/send-password-reset-email`). Registration covers Craft’s public registration form only, so a logged-in user saving their own account isn’t held to a challenge.

Add the widget to each user form template that you protect:

```twig
<form method="post">
	{{ csrfInput() }}
	{{ actionInput('users/save-user') }}

	{# … your fields … #}

	{{ craft.altcha.renderWidget() }}

	<button type="submit">Register</button>
</form>
```

These forms are checked before Craft’s controller runs, when there’s no model to hold a validation error, so a submission that fails verification gets a 400 response.

### Guest Entries

Turn on **Guest Entries** under **Settings → Altcha → Integrations**, and add the widget to your guest entry form:

```twig
<form method="post">
	{{ csrfInput() }}
	{{ actionInput('guest-entries/save') }}

	{# … your fields … #}

	{{ craft.altcha.renderWidget() }}

	<button type="submit">Submit</button>
</form>
```

As with Contact Form, a submission that fails verification is flagged as spam: the visitor gets the success response, and nothing is saved.

### Blanket mode

Blanket mode is for form plugins that expose no captcha API of their own, such as [Freeform](https://docs.solspace.com/craft/freeform/) (which has no API to register a third-party captcha) and [Campaign](https://putyourlightson.com/plugins/campaign). It hooks the front-end request itself, so it can enforce Altcha on any controller action without the host plugin’s cooperation.

Turn on **Blanket mode** under **Settings → Altcha → Integrations**, then list the action paths to enforce, one per row:

```
freeform/submit
campaign/forms/*
```

Each row is an exact `controller/action` path or a prefix ending in `*`, matched case-insensitively. Altcha’s own action paths are never enforced, so the challenge endpoint stays reachable, and anything not on the list is untouched.

> **Don’t list an action that a built-in integration already covers.** A solved payload is good only once, so if blanket mode and a built-in integration (Formie, Comments, Contact Form, Guest Entries, or the user forms) both verify the same submission, the second check sees a spent payload and rejects a legitimate form as a replay. Use blanket mode only for form plugins with no built-in integration.

Blanket mode applies only to front-end POST requests. Control panel requests, console commands, GET requests, and live preview all pass through, so previewing a protected form still works.

You still need to add the widget to the form. Freeform and Campaign render their own markup, so add `{{ craft.altcha.renderWidget() }}` through whatever the plugin gives you for custom form markup (Freeform’s form formatting templates, or your own template around Campaign’s form). A POST to a listed path with a missing or invalid solution is rejected with a 400.

> **AJAX submissions.** If a form submits over AJAX and the page doesn’t reload, the widget still holds the payload it already spent, so a second submission from that page is rejected as a replay. Turn off the form plugin’s AJAX mode for listed forms, or re-render the widget after each submission.

## Configuration

### Settings

| Setting | Default | Description |
| --- | --- | --- |
| `hmacKey` | _(empty)_ | The secret key that signs and verifies challenges. Set it to an environment variable such as `$ALTCHA_HMAC_KEY`; while it’s empty or unresolved, the plugin refuses to issue or verify anything. |
| `challengeExpiryMinutes` | `20` | How many minutes an issued challenge stays valid, from 1 to 1440. ALTCHA recommends 20 to 60 minutes. |
| `complexity` | `50000` | The proof-of-work cost a browser spends per solution attempt: `1000` (low), `50000` (medium), or `100000` (high). A higher value costs bots more, and also slows real visitors on older devices. |
| `registerWidgetJs` | `true` | Whether the plugin registers its bundled ALTCHA script on pages that render the widget. See [Bundling the widget script yourself](#bundling-the-widget-script-yourself). |
| `enableComments` | `false` | Whether front-end comments submitted through the Comments plugin require a solved challenge. |
| `enableContactForm` | `false` | Whether messages sent through the Contact Form plugin require a solved challenge. |
| `enableGuestEntries` | `false` | Whether entries submitted through the Guest Entries plugin require a solved challenge. |
| `enableUserRegistration` | `false` | Whether Craft’s front-end registration form requires a solved challenge. |
| `enableUserLogin` | `false` | Whether Craft’s front-end login form requires a solved challenge. |
| `enableUserForgotPassword` | `false` | Whether Craft’s front-end forgot-password form requires a solved challenge. |
| `enableBlanketMode` | `false` | Whether blanket mode enforces Altcha on the listed action paths. |
| `blanketActionAllowlist` | `[]` | The action paths blanket mode enforces Altcha on, each an exact `controller/action` path or a trailing-`*` wildcard (e.g. `['freeform/submit', 'campaign/forms/*']`). |
| `widgetDisplay` | `'standard'` | The widget’s display mode: `standard`, `bar`, `floating`, `overlay`, or `invisible`. Invisible solves in the background with no visible checkbox, so pair it with an auto-solve mode. |
| `widgetAuto` | `'off'` | When the widget starts solving: `off`, `onfocus`, `onload`, or `onsubmit`. With `off`, the visitor starts it by checking the box. |
| `widgetTheme` | _(empty)_ | The widget’s color scheme: `light`, `dark`, or empty to follow the visitor’s system preference. |
| `widgetHideLogo` | `true` | Whether the ALTCHA logo inside the widget is hidden. |
| `widgetHideFooter` | `true` | Whether the “Protected by ALTCHA” footer beneath the widget is hidden. |

In 1.1.0, `protectLogin` and `protectForgotPassword` were renamed to `enableUserLogin` and `enableUserForgotPassword`. The update migrates your stored settings, and the old names still work in `config/altcha.php` but log a deprecation warning.

The plugin’s three settings pages (**General Settings**, **Integrations**, and **Widget Options**) are admin-only, since the HMAC key lives on them.

> **Replay protection uses Craft’s cache.** A spent payload is recorded through the cache component, so the “accepted exactly once” guarantee is only as strong as the cache driver’s atomic writes. Redis and Memcached add entries atomically, but the default file cache is check-then-set, so a busy site facing deliberately concurrent replays of one payload is better served by Redis or Memcached. Either way, it stops casual replay.

### Overriding plugin settings

You can change these settings in the control panel, or override them with a [config file](https://craftcms.com/docs/5.x/configure.html#config-files) called `altcha.php` in your `config` folder. That file is fully [multi-environment](https://craftcms.com/docs/5.x/configure.html#multi-environment-configs) aware, so each environment can have different settings, and [`src/config.php`](src/config.php) is a commented template you can copy.

```php
<?php

return [
    'hmacKey' => '$ALTCHA_HMAC_KEY',
    'challengeExpiryMinutes' => 20,
    'complexity' => 50000,
    'enableBlanketMode' => true,
    'blanketActionAllowlist' => [
        'freeform/submit',
    ],
];
```

Anything set here is locked in the control panel and flagged there as overridden.

### Bundling the widget script yourself

The plugin vendors ALTCHA’s widget script and registers it as a Craft asset bundle on any page that renders the widget, so there’s nothing to install and nothing loads from a CDN.

To bundle the `altcha` package into your own front-end JavaScript, turn off **Register the widget script** under **Settings → Altcha → Widget Options** (or set `registerWidgetJs` to `false`). The plugin then renders the `<altcha-widget>` element and leaves loading the script to you.

If you write the widget markup yourself too, `{{ craft.altcha.challengeUrl }}` returns the plugin’s challenge endpoint for your `<altcha-widget>`’s `challenge` attribute.

## Support

Found a bug or need help? Open an [issue](https://github.com/jalendport/craft-altcha/issues).

<hr>

<p align="center">Made by <a href="https://jalendport.com">Jalen Davenport</a></p>
