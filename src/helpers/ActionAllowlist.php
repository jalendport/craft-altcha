<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Matches action IDs against blanket-POST mode's allowlist.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\helpers;

/**
 * Decides whether blanket-POST mode enforces Altcha on a given action.
 *
 * Deliberately free of any Craft dependency: everything it needs arrives as
 * arguments, so the matching rules can be unit tested without an application.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class ActionAllowlist
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether an action is covered by the blanket-mode allowlist.
     *
     * Patterns are matched case-insensitively, either exactly
     * (`freeform/submit`) or as a prefix when they end in `*`
     * (`campaign/forms/*`). This plugin's own actions are never covered: the
     * challenge endpoint has to stay reachable for the widget to solve anything.
     *
     * @param string $actionUniqueId the action's unique ID, e.g. `freeform/submit`
     * @param string[] $allowlist the configured action paths and wildcards
     * @return bool whether Altcha should be enforced on the action
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public static function matches(string $actionUniqueId, array $allowlist): bool
    {
        $actionUniqueId = self::_normalize($actionUniqueId);

        if ($actionUniqueId === 'altcha' || str_starts_with($actionUniqueId, 'altcha/')) {
            return false;
        }

        foreach ($allowlist as $pattern) {
            if (self::_patternMatches($actionUniqueId, self::_normalize($pattern))) {
                return true;
            }
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Reduces an action path to the form both sides of a comparison use.
     *
     * @param string $path the raw action path or pattern
     * @return string the normalized path
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private static function _normalize(string $path): string
    {
        return strtolower(trim(trim($path), '/'));
    }

    /**
     * Returns whether a single normalized pattern covers an action path.
     *
     * @param string $actionUniqueId the normalized action path
     * @param string $pattern the normalized allowlist pattern
     * @return bool whether the pattern covers the action
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    private static function _patternMatches(string $actionUniqueId, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        if (!str_ends_with($pattern, '*')) {
            return $actionUniqueId === $pattern;
        }

        $prefix = substr($pattern, 0, -1);

        return $prefix === '' || str_starts_with($actionUniqueId, $prefix);
    }
}
