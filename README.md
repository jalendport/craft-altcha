<p align="center"><img src="src/icon.svg" alt="Altcha" width="80" height="80"></p>

<h1 align="center">Altcha</h1>

Altcha is a Craft CMS plugin that protects your forms with [ALTCHA](https://altcha.org/) proof-of-work spam protection. A visitor's browser solves a small cryptographic challenge instead of picking out traffic lights, and the whole exchange stays on your own site — the challenge is issued and verified by your Craft install, with no third-party service, no cookies, and no tracking to disclose under GDPR.

## Features

- **Formie captcha** — registers as a native captcha in [Formie](https://verbb.io/craft-plugins/formie), switched on per form from Formie's own captcha settings, with the widget injected into the form for you.
- **Integrations for Craft's form plugins** — [Comments](https://verbb.io/craft-plugins/comments), [Contact Form](https://github.com/craftcms/contact-form), [Guest Entries](https://github.com/craftcms/guest-entries), and Craft's own registration, login, and forgot-password forms, each behind its own toggle.
- **Blanket mode** — enforce a solved challenge on any front-end POST you list by action path, for form plugins with no captcha API of their own.
- **Replay protection** — a solved payload is accepted exactly once, so a captured submission can't be posted again.
- **Environment-backed HMAC key** — generate a signing key with a console command and reference it from your `.env`, so the secret never lands in project config.
- **The widget ships with the plugin** — ALTCHA's widget v3 is vendored into the plugin's asset bundle, so nothing loads from a CDN.
- **Per-widget options** — override the display mode, theme, auto-solve behavior, or any other widget attribute for a single form.
- **Inert until you say so** — every integration is off by default, so installing the plugin changes nothing about your site until you opt a form in.

## Installation

### Requirements

This plugin requires **Craft CMS 5.0.0 or later** and **PHP 8.2 or later**.

### Plugin Store

Log into your control panel and click on "Plugin Store". Search for "Altcha", then click "Install".

### Composer

Open your terminal, go to your Craft project, and run:

```bash
composer require jalendport/craft-altcha && php craft plugin/install altcha
```

## Usage

### Generate an HMAC key

Altcha signs every challenge it issues with an HMAC key and verifies solutions against the same key. There's no default — the plugin fails closed until you set one, refusing to issue or verify anything.

Generate a key from your project root:

```bash
php craft altcha/generate-hmac-key
```

The command writes a random 32-byte key to your `.env` file as `ALTCHA_HMAC_KEY`. Then go to **Settings → Altcha → General Settings** and set **HMAC key** to `$ALTCHA_HMAC_KEY`, so the setting holds the environment variable's name rather than the secret itself. Every environment needs its own key: a solution signed with one key won't verify against another.

### Rendering the widget

Drop the widget into any form template:

```twig
<form method="post">
	{{ csrfInput() }}
	{{ actionInput('…') }}

	{# … your fields … #}

	{{ craft.altcha.renderWidget() }}

	<button type="submit">Submit</button>
</form>
```

The widget renders as an `<altcha-widget>` element that fetches a fresh challenge from the plugin's own action URL, solves it in the browser, and posts the result as a hidden `altcha` field. Every check the plugin performs reads that field, so the widget has to sit *inside* the `<form>` it protects. Formie is the exception — it injects the widget itself.

Keep to one widget per form. If you do render several inside a single `<form>`, give each a distinct `name` attribute and wire up your own verification for them, because the built-in integrations only ever read the payload posted as `altcha`.

#### Per-call options

Anything you pass to `renderWidget()` becomes an attribute on the widget, overriding the configured defaults for that one form:

```twig
{{ craft.altcha.renderWidget({
	display: 'bar',
	auto: 'onfocus',
	theme: 'dark',
}) }}
```

Two of the plugin's settings — hiding the ALTCHA logo and hiding the widget footer — aren't attributes in the widget's v3 API. They're configuration properties, so they travel in a JSON-encoded `configuration` attribute that the plugin builds for you. To override them per call, pass a `configuration` hash and it will be merged into that JSON:

```twig
{{ craft.altcha.renderWidget({
	configuration: {
		hideLogo: false,
		hideFooter: false,
	},
}) }}
```

### Formie

Formie is the only supported form plugin with a captcha API of its own, so Altcha plugs straight into it. Go to **Formie → Settings → Captchas**, enable **Altcha**, and then switch it on for individual forms from each form's own captcha settings.

Nothing goes in your templates: Formie renders the widget into the form for you, and a submission that fails verification is marked as spam with a reason, exactly like Formie's other captchas. GraphQL submissions verify too.

### Comments

Turn on **Comments** under **Settings → Altcha → Integrations**. New comments posted through the front-end form then need a solved challenge; editing, trashing, voting, and flagging are left alone, as is anything saved from the control panel or a console command.

The Comments plugin renders its own form, so add the widget to its template: copy the plugin's templates into your own template folder (see **Comments → Settings → Templates**), then add `{{ craft.altcha.renderWidget() }}` inside the `<form>` in `_includes/form.html`.

A comment that fails verification isn't saved, and the form shows a validation error on the `altcha` attribute.

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

A message that fails verification is flagged as spam rather than rejected, which is Contact Form's own convention: the sender sees the usual success message, and the message goes nowhere.

### User registration, login, and forgot password

Three separate toggles live under **Settings → Altcha → Integrations**: **User registration**, **Protect the login form**, and **Protect the forgot-password form**. Registration covers Craft's public registration form only — a logged-in user saving their own account isn't held to a challenge.

Add the widget to whichever of your own user form templates you've protected:

```twig
<form method="post">
	{{ csrfInput() }}
	{{ actionInput('users/save-user') }}

	{# … your fields … #}

	{{ craft.altcha.renderWidget() }}

	<button type="submit">Register</button>
</form>
```

These forms are checked before Craft's controller runs, and there's no model yet to hang a validation error on, so a submission that fails verification gets a 400 response instead.

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

Blanket mode is the escape hatch for form plugins that expose no captcha API of their own, such as [Freeform](https://docs.solspace.com/craft/freeform/) and [Campaign](https://putyourlightson.com/plugins/campaign). It hooks the front-end request itself, so it can enforce Altcha on any controller action without the host plugin's cooperation.

Turn on **Blanket mode** under **Settings → Altcha → Integrations**, then list the action paths to enforce it on, one per row:

```
freeform/submit
campaign/forms/*
```

Each row is either an exact `controller/action` path or a prefix ending in `*`, matched case-insensitively. Altcha's own action paths are never enforced, so the challenge endpoint always stays reachable. Anything not on the list is untouched.

> **Don't allowlist an action a dedicated integration already covers.** A solved payload is only good once, so if blanket mode and one of the built-in integrations (Formie, Comments, Contact Form, Guest Entries, or the user forms) both verify the same submission, the second one sees the payload as already spent and rejects a legitimate form as a replay. Blanket mode is for form plugins that have *no* dedicated integration; leave the rest to their own toggles.

Blanket mode only applies to front-end POST requests. Control panel requests, console commands, GET requests, and live preview all pass through, so previewing a protected form doesn't break.

You still have to get the widget into the form. Freeform and Campaign both render their own markup, so add `{{ craft.altcha.renderWidget() }}` through whichever mechanism the plugin gives you for custom form markup — Freeform's form formatting templates, or your own template around Campaign's form. A POST to an allowlisted path with a missing or invalid solution is rejected with a 400.

> **A note on AJAX submissions.** A solved payload is only good once. If a form submits over AJAX and the page isn't reloaded afterwards, the widget still holds the payload it already spent, and a second submission from the same page will be rejected as a replay. Either turn off the form plugin's AJAX mode for allowlisted forms, or re-render the widget after each submission.

### Skipping verification for a comment

The Comments integration fires an event before it verifies a comment, so you can wave through comments posted from a form that doesn't carry a widget — a members-only form, say:

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

The event carries the `Comment` about to be saved as `$event->comment`. Setting `skipVerification` to `true` saves the comment without looking for a solution at all.

## Configuration

### Settings

| Setting | Default | Description |
| --- | --- | --- |
| `hmacKey` | _(empty)_ | The secret key used to sign and verify challenges. Set it to an environment variable such as `$ALTCHA_HMAC_KEY`; the plugin refuses to issue or verify anything while it's empty or unresolved. |
| `challengeExpiryMinutes` | `20` | How many minutes an issued challenge stays valid, from 1 to 1440. ALTCHA recommends 20 to 60 minutes. |
| `complexity` | `50000` | The proof-of-work cost a browser spends per solution attempt: `1000` (low), `50000` (medium), or `100000` (high). Higher costs bots more, and slows real visitors down on older devices. |
| `registerWidgetJs` | `true` | Whether the plugin registers its bundled ALTCHA script on pages that render the widget. See [Bundling the widget script yourself](#bundling-the-widget-script-yourself). |
| `enableComments` | `false` | Whether front-end comments submitted through the Comments plugin require a solved challenge. |
| `enableContactForm` | `false` | Whether messages sent through the Contact Form plugin require a solved challenge. |
| `enableGuestEntries` | `false` | Whether entries submitted through the Guest Entries plugin require a solved challenge. |
| `enableUserRegistration` | `false` | Whether Craft's front-end registration form requires a solved challenge. |
| `protectLogin` | `false` | Whether Craft's front-end login form requires a solved challenge. |
| `protectForgotPassword` | `false` | Whether Craft's front-end forgot-password form requires a solved challenge. |
| `enableBlanketMode` | `false` | Whether blanket mode enforces Altcha on the allowlisted action paths. |
| `blanketActionAllowlist` | `[]` | The action paths blanket mode enforces Altcha on. Each is an exact `controller/action` path or a trailing-`*` wildcard, e.g. `['freeform/submit', 'campaign/forms/*']`. |
| `widgetDisplay` | `'standard'` | The widget's display mode: `standard`, `bar`, `floating`, `overlay`, or `invisible`. Invisible solves in the background with no visible checkbox, so pair it with an auto-solve mode. |
| `widgetAuto` | `'off'` | When the widget starts solving: `off`, `onfocus`, `onload`, or `onsubmit`. Off means the visitor starts it themselves by checking the box. |
| `widgetTheme` | _(empty)_ | The widget's color scheme: `light`, `dark`, or empty to follow the visitor's system preference. |
| `widgetHideLogo` | `true` | Whether the ALTCHA logo inside the widget is hidden. |
| `widgetHideFooter` | `true` | Whether the "Protected by ALTCHA" footer beneath the widget is hidden. |

The plugin's three settings pages — **General Settings**, **Integrations**, and **Widget Options** — are admin-only, since the HMAC key lives on them.

> **Replay protection uses Craft's cache.** A spent payload is recorded through the cache component, so the "accepted exactly once" guarantee is as strong as the configured cache driver's atomic writes. Redis and Memcached add entries atomically; the default file cache is check-then-set, so a busy site fielding deliberately concurrent replays of one payload is better served by a Redis or Memcached cache. It stops casual replay either way.

### Overriding plugin settings

These settings can be changed in the plugin settings in the control panel, or overridden with a config file.

If you create a [config file](https://craftcms.com/docs/5.x/configure.html#config-files) in your `config` folder called `altcha.php`, you can override the plugin's settings in the control panel. Since that config file is fully [multi-environment](https://craftcms.com/docs/5.x/configure.html#multi-environment-configs) aware, this is a handy way to have different settings across multiple environments. A commented template you can copy is included at [`src/config.php`](src/config.php).

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

The plugin vendors ALTCHA's widget script and registers it as a Craft asset bundle on any page that renders the widget, so there's nothing to install and nothing loads from a CDN.

If you'd rather bundle the `altcha` package into your own front-end JavaScript, turn off **Register the widget script** under **Settings → Altcha → Widget Options** (or set `registerWidgetJs` to `false`). The plugin then renders the `<altcha-widget>` element and leaves loading the script to you.

If you're writing the widget markup yourself too, `{{ craft.altcha.challengeUrl }}` returns the plugin's challenge endpoint, which is what your own `<altcha-widget>` needs on its `challenge` attribute.

## FAQ

### Why isn't Altcha a native Freeform captcha?

Because Freeform has no API for registering a third-party captcha. Formie exposes one, which is why Altcha shows up in Formie's captcha list like any first-party option; Freeform's captcha support is a fixed list of the services it ships with, with no event or interface a plugin can hook into to add another.

[Blanket mode](#blanket-mode) is the route for Freeform, and for any other form plugin in the same position. It works one level lower — on the request itself — so it doesn't need the form plugin to cooperate.

## Support

Found a bug or need help? Open an [issue](https://github.com/jalendport/craft-altcha/issues).

<hr>

<p align="center">Made by <a href="https://jalendport.com">Jalen Davenport</a></p>
