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
 * Plugin version and requirements declaration for the catalog_browser plugin.
 * This file is the identity card of the plugin — without it, Moodle will not recognise it.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Prevent direct access to this file outside of Moodle.
defined('MOODLE_INTERNAL') || die();

// Unique technical name of the plugin, following the Moodle 'type_name' convention.
// Must match the plugin directory name exactly.
$plugin->component = 'local_catalog_browser';

// Plugin version number in YYYYMMDDXX format (year, month, day, two-digit increment).
// Moodle compares this against the version stored in the database to detect updates.
// Incrementing this number will trigger an upgrade prompt in Moodle.
$plugin->version = 2026022506;

// Minimum Moodle version required to run this plugin (Moodle 4.3).
// The core_external namespace used by this plugin was introduced in Moodle 4.2.
// However, Moodle 4.3 is the first version to officially support PHP 8.3,
// which is the minimum PHP version this plugin has been tested with.
$plugin->requires = 2023100900;

// Human-readable version label — for display purposes only, no technical effect.
$plugin->release = '1.0';

// Maturity level of this release.
// MATURITY_ALPHA = under active development, not recommended for production.
// Change to MATURITY_STABLE when the plugin is ready for production use.
$plugin->maturity = MATURITY_STABLE;
