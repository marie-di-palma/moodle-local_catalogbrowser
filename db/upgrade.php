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
 * Upgrade script for the catalog_browser plugin.
 * Called by Moodle when the plugin version number increases.
 * Add version-specific upgrade steps here as the plugin evolves.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Handles plugin upgrade steps between versions.
 * Each upgrade block should be wrapped in if ($oldversion < YYYYMMDDXX) to ensure
 * steps are only applied once and in the correct order.
 *
 * @param  int  $oldversion The version number the site is upgrading from
 * @return bool             True on success
 */
function xmldb_local_catalog_browser_upgrade($oldversion) {
    // No upgrade steps required yet.
    // Future upgrade blocks should follow this pattern:
    // check the old version, perform the upgrade step,
    // then call upgrade_plugin_savepoint with the new version number.
    return true;
}
