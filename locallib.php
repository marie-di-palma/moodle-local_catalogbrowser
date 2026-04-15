<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Internal library functions for the catalog_browser plugin.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Synchronises the guest role permission for local/catalog_browser:view
 * with the current value of the allowguests plugin setting.
 *
 * Called after the allowguests setting is saved so that unauthenticated users
 * are granted or denied the capability automatically, without requiring the
 * administrator to visit the role management UI manually.
 *
 * - allowguests enabled  → guest role gets CAP_ALLOW on the system context.
 * - allowguests disabled → explicit override is removed so the archetype
 *                          default (CAP_PREVENT) applies again.
 *
 * @param  bool $allow True to grant the capability to guests, false to revert to default.
 * @return void
 */
function local_catalog_browser_sync_guest_capability(bool $allow): void {
    $systemcontext = context_system::instance();
    $guestrole     = get_guest_role();

    if (!$guestrole) {
        // Guest role is not configured on this site — nothing to do.
        return;
    }

    if ($allow) {
        // Grant an explicit CAP_ALLOW to the guest role at the system context.
        assign_capability(
            'local/catalog_browser:view',
            CAP_ALLOW,
            $guestrole->id,
            $systemcontext->id,
            true // Overwrite any existing value.
        );
    } else {
        // Remove the explicit override so the archetype default (CAP_PREVENT) applies.
        unassign_capability(
            'local/catalog_browser:view',
            $guestrole->id,
            $systemcontext->id
        );
    }

    // Flush the capability cache so the change takes effect immediately.
    $systemcontext->mark_dirty();
}
