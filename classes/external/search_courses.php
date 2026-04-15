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
 * External function returning paginated course search results for the catalog_browser plugin.
 * Called via AJAX by the dynamic search module to update results without a page reload.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_catalog_browser\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use local_catalog_browser\local\course_search;

/**
 * External API class for the dynamic course search endpoint.
 */
class search_courses extends external_api {

    /**
     * Declares the parameters accepted by the execute() method.
     * All filter data is passed as JSON strings to keep the AJAX call simple.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            // JSON-encoded key-value map of custom field filters.
            'filters'          => new external_value(PARAM_RAW, 'JSON-encoded custom field filters'),
            // Plain text filter applied to the course title.
            'filtertitle'      => new external_value(PARAM_TEXT, 'Course title filter', VALUE_DEFAULT, ''),
            // JSON-encoded array of selected tag IDs.
            'selectedtags'     => new external_value(PARAM_RAW, 'JSON-encoded array of selected tag IDs', VALUE_DEFAULT, '[]'),
            // Sort key: az, recent, or moodle.
            'sort'             => new external_value(PARAM_ALPHA, 'Sort key (az, recent, moodle)', VALUE_DEFAULT, 'moodle'),
            // ID of the Moodle course category to filter by (0 = all categories).
            'selectedcategory' => new external_value(PARAM_INT, 'Moodle course category ID (0 = all)', VALUE_DEFAULT, 0),
            // Zero-based page index.
            'page'             => new external_value(PARAM_INT, 'Page index (0-based)', VALUE_DEFAULT, 0),
            // Number of results to return per page.
            'perpage'          => new external_value(PARAM_INT, 'Number of results per page', VALUE_DEFAULT, 10),
        ]);
    }

    /**
     * Executes the course search and returns a paginated list of results.
     *
     * Enforces guest access restrictions based on plugin configuration.
     * Decodes JSON filter parameters, delegates the search to course_search::search(),
     * then builds enriched course data including image URL, tags, and truncated summary.
     *
     * @param  string $filters          JSON-encoded custom field filters
     * @param  string $filtertitle      Course title filter string
     * @param  string $selectedtags     JSON-encoded array of selected tag IDs
     * @param  string $sort             Sort key (az, recent, moodle)
     * @param  int    $selectedcategory Moodle course category ID (0 = all)
     * @param  int    $page             Zero-based page index
     * @param  int    $perpage          Number of results per page
     * @return array                    Paginated results with totalcount, totalpages, page, and courses
     */
    public static function execute(
        string $filters,
        string $filtertitle,
        string $selectedtags,
        string $sort,
        int $selectedcategory,
        int $page,
        int $perpage
    ): array {

        // Enforce guest access restriction based on the plugin setting.
        $allowguests = get_config('local_catalog_browser', 'allowguests');
        if (!$allowguests && !isloggedin()) {
            throw new \moodle_exception('nopermissions');
        }

        // Validate and sanitise all incoming parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'filters'          => $filters,
            'filtertitle'      => $filtertitle,
            'selectedtags'     => $selectedtags,
            'sort'             => $sort,
            'selectedcategory' => $selectedcategory,
            'page'             => $page,
            'perpage'          => $perpage,
        ]);

        // Decode the JSON filter map into a PHP array (empty array on failure).
        $filtersarr = json_decode($params['filters'], true) ?: [];

        // Decode and cast tag IDs to integers.
        $tagsarr = array_map('intval', json_decode($params['selectedtags'], true) ?: []);

        // Retrieve the configured custom field category ID and its field definitions.
        $categoryid   = get_config('local_catalog_browser', 'categoryid');
        $customfields = course_search::get_customfields($categoryid);

        // Run the full search (returns all matching courses, unsorted).
        $courses = course_search::search(
            $customfields,
            $filtersarr,
            $params['filtertitle'],
            $tagsarr,
            $params['sort'],
            $params['selectedcategory']
        );

        // Compute pagination values.
        $totalcount = count($courses);
        $totalpages = (int)ceil($totalcount / $params['perpage']);

        // Clamp the requested page to valid bounds.
        $page = max(0, min($params['page'], max(0, $totalpages - 1)));

        // Slice the result set to the requested page.
        $paged = array_slice($courses, $page * $params['perpage'], $params['perpage']);

        // Build the enriched course data array for the current page.
        $coursesdata = [];
        foreach ($paged as $course) {

            // Retrieve the first valid overview image URL for the course, if any.
            $imgurl    = '';
            $courseobj = new \core_course_list_element($course);
            foreach ($courseobj->get_course_overviewfiles() as $file) {
                if ($file->is_valid_image()) {
                    $imgurl = file_encode_url(
                        new \moodle_url('/pluginfile.php'),
                        '/' . $file->get_contextid() . '/' . $file->get_component() .
                        '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename(),
                        false
                    );
                    break;
                }
            }

            // Retrieve native Moodle tags associated with the course.
            $coursetags = \core_tag_tag::get_item_tags('core', 'course', $course->id);
            $tagslist   = array_map(fn($t) => $t->rawname, array_values($coursetags));

            // Strip HTML from the summary and truncate to 200 characters.
            $summary = strip_tags(format_text($course->summary ?? '', $course->summaryformat ?? FORMAT_HTML));
            $summary = trim($summary);
            if (mb_strlen($summary) > 200) {
                $summary = mb_substr($summary, 0, 197) . '...';
            }

            // Build the return URL server-side from the decoded filter parameters
            // to avoid any double-encoding introduced by passing a JS-built URL through rawurlencode().
            $returnparams = [];
            foreach ($filtersarr as $key => $val) {
                if ($val !== '') {
                    $returnparams[$key] = $val;
                }
            }
            if ($params['filtertitle'] !== '') {
                $returnparams['coursetitle'] = $params['filtertitle'];
            }
            if ($params['selectedcategory'] !== 0) {
                $returnparams['categoryid'] = $params['selectedcategory'];
            }
            $returnparams['sort'] = $params['sort'];
            $returnparams['page'] = $page;

            // Build the base return URL using moodle_url, then append tags[] manually
            // because moodle_url does not support array parameters with bracket notation.
            $returnurlbase = (new \moodle_url('/local/catalog_browser/index.php', $returnparams))->out(false);
            foreach ($tagsarr as $tagid) {
                $returnurlbase .= '&tags%5B%5D=' . $tagid;
            }

            // Build the course preview URL with the return URL appended as a raw query string
            // parameter to avoid double-encoding of the return URL.
            $previewbase = (new \moodle_url('/local/catalog_browser/course.php', ['id' => $course->id]))->out(false);
            $previewurl = $previewbase . '&returnurl=' . rawurlencode($returnurlbase);

            $coursesdata[] = [
                'fullname'  => format_string($course->fullname),
                // URL to the catalog preview page with return URL appended as a raw query string.
                'url'       => $previewurl,
                'imgurl'    => $imgurl,
                'tags'      => implode(', ', $tagslist),
                'summary'   => $summary,
            ];
        }

        return [
            'totalcount' => $totalcount,
            'totalpages' => $totalpages,
            'page'       => $page,
            'courses'    => $coursesdata,
        ];
    }

    /**
     * Declares the structure of the value returned by execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            // Total number of courses matching the current filters.
            'totalcount' => new external_value(PARAM_INT, 'Total number of matching courses'),
            // Total number of pages given the current perpage setting.
            'totalpages' => new external_value(PARAM_INT, 'Total number of pages'),
            // Actual page index returned (may differ from requested if out of bounds).
            'page'       => new external_value(PARAM_INT, 'Current page index'),
            // Array of course data objects for the current page.
            'courses'    => new external_multiple_structure(
                new external_single_structure([
                    // Formatted course full name.
                    'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                    // URL to the catalog preview page, including the return URL for the back-to-catalog button.
                    'url' => new external_value(PARAM_RAW, 'Course preview URL with return URL'),
                    // URL of the course overview image (empty string if none).
                    'imgurl'   => new external_value(PARAM_RAW, 'Course overview image URL'),
                    // Comma-separated list of tag names.
                    'tags'     => new external_value(PARAM_TEXT, 'Comma-separated tag names'),
                    // Plain-text summary truncated to 200 characters.
                    'summary'  => new external_value(PARAM_RAW, 'Truncated plain-text course summary'),
                ])
            ),
        ]);
    }
}
