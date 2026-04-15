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
 * External function declarations for the catalog_browser plugin.
 * Registers the AJAX-callable PHP functions with Moodle's external API system.
 * Without these declarations, the JavaScript module cannot call the server.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Prevent direct access to this file outside of Moodle.
defined('MOODLE_INTERNAL') || die();

// Registry of external functions exposed by this plugin.
// Each key is the function name used in JavaScript Ajax.call() methodname.
$functions = [

    // Autocomplete suggestions for custom field and course title inputs.
    'local_catalog_browser_search' => [
        'classname'     => 'local_catalog_browser\external\search_autocomplete',
        'methodname'    => 'execute',          // Standard Moodle external function convention.
        'description'   => 'Returns autocomplete suggestions for catalog browser filter fields.',
        'type'          => 'read',             // Read-only — does not modify any data.
        'ajax'          => true,               // Allows calls from the browser via core/ajax.
        'loginrequired' => false,              // Guest access allowed; enforced manually in execute().
    ],

    // Dynamic course search with filters, pagination, and sorting.
    'local_catalog_browser_search_courses' => [
        'classname'     => 'local_catalog_browser\external\search_courses',
        'methodname'    => 'execute',          // Standard Moodle external function convention.
        'description'   => 'Returns a paginated list of courses matching the given filters.',
        'type'          => 'read',             // Read-only — does not modify any data.
        'ajax'          => true,               // Allows calls from the browser via core/ajax.
        'loginrequired' => false,              // Guest access allowed; enforced manually in execute().
    ],

];
