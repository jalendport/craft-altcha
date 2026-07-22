# Altcha — manual smoke checklist

Pest covers challenge issuance and verification, expiry, replay protection, fail-closed HMAC keys, malformed payloads, allowlist matching, settings validation, and the `VerifyCommentEvent` flag without booting a Craft app. Everything below is Craft-coupled — plugin installation, HTTP requests, integrations, element saves, event-handler gating, permissions, control-panel settings, and widget behavior — and can only be verified against a real Craft install. Run through this before tagging a release, and note any gaps you hit.

Commands assume you're inside a Craft site with the plugin installed. On Jalen's Spark/Docker setup, prefix each `php craft …` with `docker compose exec php` (e.g. `docker compose exec php php craft plugin/install altcha`).

## Install and defaults

- [ ] **Fresh install.** In a clean Craft 5 site, require the plugin from the local path or Packagist: `composer require jalendport/craft-altcha`.
- [ ] **Plugin install.** `php craft plugin/install altcha` completes without error, and `php craft plugin/list` shows Altcha as installed.
- [ ] With every integration toggle off, front-end comments, forms, user actions, and guest entries continue working without an Altcha payload.
- [ ] The challenge endpoint returns a server error instead of issuing a challenge when the HMAC key is empty or references an unset environment variable.

## Formie

- [ ] Enable Altcha in Formie's captcha settings, render a front-end form, solve the challenge, and submit successfully.
- [ ] Resubmit the same payload and confirm Formie treats the replay as spam.
- [ ] Wait until the challenge expires and confirm the submission is rejected.
- [ ] Submit the form through a GraphQL mutation and confirm it passes without browser widget verification.

## Comments

- [ ] Enable the Comments integration, add the widget to a guest comment form, solve the challenge, and save the comment successfully.
- [ ] Submit without a payload and confirm the verification error appears and the comment is not saved.
- [ ] Edit and trash a front-end comment without a widget and confirm both actions still work.
- [ ] Register an `EVENT_BEFORE_VERIFY_COMMENT` listener that sets `skipVerification = true`, submit a new front-end comment without a payload, and confirm the handler bypasses verification and saves the comment.
- [ ] Remove the bypass listener, repeat the payload-free submission, and confirm verification runs and blocks the save.

## Contact Form, users, and Guest Entries

- [ ] Enable the Contact Form integration, solve and submit successfully, then submit without a payload and confirm the request is rejected.
- [ ] Enable user-registration protection, solve and register successfully, then register without a payload and confirm validation fails.
- [ ] Enable login protection, solve and log in successfully, then log in without a payload and confirm validation fails.
- [ ] Enable forgot-password protection, solve and request a reset successfully, then repeat without a payload and confirm validation fails.
- [ ] Enable the Guest Entries integration, solve and save an entry successfully, then submit without a payload and confirm validation fails.

## Blanket mode

- [ ] Add one real form action to the blanket allowlist, solve and submit successfully, then submit without a payload and confirm the request is rejected.
- [ ] Submit to an action that is not allowlisted and confirm blanket mode leaves it untouched.
- [ ] Confirm `altcha/*` endpoints remain reachable even if `*` is allowlisted.
- [ ] Confirm blanket mode does not enforce Altcha on control-panel, console, non-POST, or live-preview requests.

## Settings, permissions, and environment

- [ ] Save each valid widget display and auto mode in the control panel and confirm the rendered widget receives the selected options.
- [ ] Confirm a non-admin control-panel user cannot open `settings/altcha/*`.
- [ ] Run `php craft altcha/generate-hmac-key`, store its output in `ALTCHA_HMAC_KEY`, configure `$ALTCHA_HMAC_KEY` in the setting, and confirm issued challenges verify successfully.

## Release

- [ ] Tag `1.0.0-beta.1` with the cut-release flow only after every applicable item above passes.
