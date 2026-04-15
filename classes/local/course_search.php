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
 * Business logic for the catalog_browser plugin.
 * Provides methods to retrieve configured custom fields and to search courses
 * using a combination of title, tag, category, and custom field filters.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_catalog_browser\local;

/**
 * Core search class for the catalog_browser plugin.
 * All methods are static — this class acts as a service layer with no instance state.
 */
class course_search {

    /**
     * Custom field types supported by the catalog browser filter system.
     * Other types (e.g. date, url) are intentionally excluded.
     *
     * @var array
     */
    const SUPPORTED_TYPES = ['text', 'select', 'textarea', 'checkbox', 'number'];

    /**
     * Returns the custom fields belonging to the configured category,
     * filtered to only include supported types and visible fields.
     *
     * Visibility is read from the field's JSON configdata:
     * 0 = hidden, 1 = visible to teachers only, 2 = visible to everyone.
     * Fields with no visibility config default to visible (2).
     *
     * @param  int   $categoryid The ID of the custom field category configured in plugin settings
     * @return array             Array of custom field records (id, shortname, name, type, configdata, etc.)
     */
    public static function get_customfields(int $categoryid): array {
        global $DB;

        // Retrieve all fields in the category, ordered by their display order.
        $allfields = $DB->get_records(
            'customfield_field',
            ['categoryid' => $categoryid],
            'sortorder ASC',
            'id, shortname, name, type, configdata, description, descriptionformat'
        );

        // Filter out unsupported types and hidden fields.
        return array_filter($allfields, function($field) {
            if (!in_array($field->type, self::SUPPORTED_TYPES)) {
                return false;
            }
            $config = json_decode($field->configdata, true);
            if (!is_array($config)) {
                return true;
            }
            // Visibility 0 means hidden — exclude these fields.
            $visibility = isset($config['visibility']) ? (int)$config['visibility'] : 2;
            return $visibility !== 0;
        });
    }

    /**
     * Searches for visible courses matching the given combination of filters.
     *
     * Builds a dynamic SQL query using EXISTS subqueries for each active custom field filter.
     * Results are sorted in PHP after retrieval (strcoll for locale-aware A→Z,
     * spaceship operator for date-based sorts).
     *
     * @param  array       $customfields    Array of custom field records from get_customfields()
     * @param  array       $filters         Associative array of custom field shortname => filter value
     * @param  string|null $filtertitle     Partial course title to filter by (empty = no filter)
     * @param  array       $selectedtags    Array of tag IDs the course must be tagged with
     * @param  string      $sort            Sort key: 'az' (alphabetical), 'recent' (newest first),
     *                                      or 'moodle' (default DB order)
     * @param  int         $selectedcategory Moodle course category ID to restrict results (0 = all)
     * @return array                        Array of matching course records
     */
    public static function search(
        array $customfields,
        array $filters,
        ?string $filtertitle = '',
        array $selectedtags = [],
        string $sort = 'moodle',
        int $selectedcategory = 0
    ): array {
        global $DB;

        // Base query: exclude the site course (id=1) and hidden courses.
        $sql = "SELECT DISTINCT c.id, c.fullname, c.timecreated, c.summary, c.summaryformat
                  FROM {course} c
                 WHERE c.id != 1
                   AND c.visible = 1";

        // Named parameter array for the prepared statement.
        $params = [];

        // Counter used to generate unique parameter names for each custom field subquery.
        $i = 0;

        // Restrict to a specific Moodle course category if selected.
        if ($selectedcategory !== 0) {
            $sql .= " AND c.category = :categoryid";
            $params['categoryid'] = $selectedcategory;
        }

        // Apply a partial match filter on the course title if provided.
        if ($filtertitle !== '') {
            $sql .= " AND " . $DB->sql_like('c.fullname', ':coursetitle', false);
            $params['coursetitle'] = '%' . $filtertitle . '%';
        }

        // Filter by tags: course must have at least one matching tag instance.
        if (!empty($selectedtags)) {
            // AND logic: the course must have ALL selected tags.
            // Add one independent EXISTS subquery per tag.
            $tagindex = 0;
            foreach ($selectedtags as $tagid) {
                $paramname = 'tagid' . $tagindex;
                $sql .= " AND EXISTS (
                            SELECT 1
                            FROM {tag_instance} ti{$tagindex}
                            WHERE ti{$tagindex}.itemid = c.id
                            AND ti{$tagindex}.itemtype = 'course'
                            AND ti{$tagindex}.tagid = :{$paramname}
                        )";
                $params[$paramname] = $tagid;
                $tagindex++;
            }
        }

        // Apply one EXISTS subquery per active custom field filter.
        foreach ($customfields as $field) {
            // Skip fields with no filter value.
            $value = $filters[$field->shortname] ?? '';
            if ($value === '') {
                continue;
            }

            if (in_array($field->type, ['select', 'checkbox', 'number'])) {
                // Exact match for select, checkbox, and number fields.
                $sql .= " AND EXISTS (
                            SELECT 1
                              FROM {customfield_data} d{$i}
                              JOIN {customfield_field} f{$i} ON f{$i}.id = d{$i}.fieldid
                             WHERE d{$i}.instanceid = c.id
                               AND f{$i}.shortname = :fname{$i}
                               AND d{$i}.value = :fval{$i}
                          )";
                $params["fname{$i}"] = $field->shortname;
                $params["fval{$i}"]  = $value;
            } else {
                // Partial case-insensitive match for text and textarea fields.
                $sql .= " AND EXISTS (
                            SELECT 1
                              FROM {customfield_data} d{$i}
                              JOIN {customfield_field} f{$i} ON f{$i}.id = d{$i}.fieldid
                             WHERE d{$i}.instanceid = c.id
                               AND f{$i}.shortname = :fname{$i}
                               AND " . $DB->sql_like("d{$i}.value", ":fval{$i}", false) . "
                          )";
                $params["fname{$i}"] = $field->shortname;
                $params["fval{$i}"]  = '%' . $value . '%';
            }
            $i++;
        }

        // Execute the query and re-index the results as a flat array.
        $results = array_values($DB->get_records_sql($sql, $params));

        // Sort results in PHP to support locale-aware alphabetical ordering
        // and flexible date-based sorting without adding ORDER BY to the dynamic SQL.
        if ($sort === 'az') {
            // Locale-aware alphabetical sort using strcoll.
            usort($results, fn($a, $b) => strcoll($a->fullname, $b->fullname));
        } else if ($sort === 'recent') {
            // Most recently created courses first.
            usort($results, fn($a, $b) => $b->timecreated <=> $a->timecreated);
        }

        return $results;
    }
}
