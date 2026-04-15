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
 * External function providing autocomplete suggestions for catalog_browser filter fields.
 * Accepts a field name and a partial query string, and returns matching distinct values
 * from either the course title or a configured custom field category.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_catalog_browser\external;

// Moodle external API base class and parameter type declarations.
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_external\external_single_structure;

/**
 * External API class for the autocomplete suggestions endpoint.
 * Inherits security and parameter validation from external_api.
 */
class search_autocomplete extends external_api {

    /**
     * Declares the parameters accepted by the execute() method.
     * Moodle validates and sanitises these automatically before calling execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            // The shortname of the custom field to search (e.g. 'language', 'author'),
            // or the special value 'coursetitle' to search course titles instead.
            'fieldname' => new external_value(PARAM_ALPHANUMEXT, 'Custom field shortname or coursetitle'),
            // The partial string typed by the user in the autocomplete input.
            'query'     => new external_value(PARAM_TEXT, 'Partial search string entered by the user'),
        ]);
    }

    /**
     * Returns autocomplete suggestions matching the given query for the specified field.
     *
     * Handles two cases:
     * - 'coursetitle': queries course full names directly from the {course} table.
     * - Any other field name: queries distinct values from {customfield_data},
     *   filtered by the configured custom field category.
     *
     * Enforces guest access restrictions based on the plugin configuration.
     *
     * @param  string $fieldname Custom field shortname or 'coursetitle'
     * @param  string $query     Partial string to match against
     * @return array             Array of suggestion objects with a 'value' key
     */
    public static function execute(string $fieldname, string $query): array {
        global $DB;

        // Block unauthenticated users if guest access is disabled in the plugin settings.
        $allowguests = get_config('local_catalog_browser', 'allowguests');
        if (!$allowguests && !isloggedin()) {
            throw new \moodle_exception('nopermissions');
        }

        // Validate and sanitise all incoming parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'fieldname' => $fieldname,
            'query'     => $query,
        ]);

        // Special case: autocomplete on the course title field.
        if ($params['fieldname'] === 'coursetitle') {
            $sql = "SELECT DISTINCT c.fullname AS value
                      FROM {course} c
                     WHERE c.id != 1
                       AND c.visible = 1
                       AND " . $DB->sql_like('c.fullname', ':query', false);

            $records = $DB->get_records_sql($sql, [
                'query' => '%' . $params['query'] . '%',
            ], 0, 5);

            return array_values(array_map(fn($r) => ['value' => $r->value], $records));
        }

        // General case: search distinct values in the configured custom field category.
        // JOIN with {customfield_field} to filter by shortname.
        // DISTINCT prevents duplicate suggestions when multiple courses share the same value.
        // categoryid restricts the search to the custom field category configured in plugin settings.
        $sql = "SELECT DISTINCT d.value
                  FROM {customfield_data} d
                  JOIN {customfield_field} f ON f.id = d.fieldid
                 WHERE f.shortname = :fieldname
                   AND f.categoryid = :categoryid
                   AND " . $DB->sql_like('d.value', ':query', false);

        $records = $DB->get_records_sql($sql, [
            'fieldname'  => $params['fieldname'],
            'categoryid' => get_config('local_catalog_browser', 'categoryid'),
            'query'      => '%' . $params['query'] . '%',
        ], 0, 5);

        // Return a flat array of value objects (e.g. [['value' => 'English'], ['value' => 'French']]).
        return array_values(array_map(fn($r) => ['value' => $r->value], $records));
    }

    /**
     * Declares the structure of the value returned by execute().
     * Moodle uses this to validate and document the output before sending it to the JS client.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                // A single autocomplete suggestion value.
                'value' => new external_value(PARAM_TEXT, 'Suggested field value'),
            ])
        );
    }
}
