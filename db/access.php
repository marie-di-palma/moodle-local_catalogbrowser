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
 * Capability definitions for the catalog_browser plugin.
 * Defines who can access the course catalog browser page.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Prevent direct access to this file outside of Moodle.
defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Allows a user to view and use the catalog browser.
    // Granted to all standard authenticated roles by default.
    'local/catalog_browser:view' => [
        'captype'      => 'read',          // Read-only capability — does not modify any data.
        'contextlevel' => CONTEXT_SYSTEM,  // Applies at the system level (not course or category).
        'archetypes' => [
            'guest'          => CAP_PREVENT, // Guests: controlled dynamically via the allowguests setting.
            'user'           => CAP_ALLOW,   // Authenticated users.
            'student'        => CAP_ALLOW,   // Students enrolled in courses.
            'teacher'        => CAP_ALLOW,   // Non-editing teachers.
            'editingteacher' => CAP_ALLOW,   // Editing teachers.
            'manager'        => CAP_ALLOW,   // Site managers.
        ],
    ],

];
