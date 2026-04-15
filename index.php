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
 * Main page for the catalog_browser plugin.
 * Handles filter input, course search, pagination, and template rendering.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Required Moodle bootstrap — loads global configuration and framework.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once('../../config.php');

// Business logic class for course searching and custom field retrieval.
use local_catalog_browser\local\course_search;

// Enforce access control: require login unless guest access is enabled in plugin settings.
$allowguests = get_config('local_catalog_browser', 'allowguests');
if (!$allowguests && (!isloggedin() || isguestuser())) {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/local/catalog_browser/index.php'));
    $PAGE->set_title(get_string('pagetitle', 'local_catalog_browser'));
    $PAGE->set_heading(get_string('pagetitle', 'local_catalog_browser'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('guestdenied', 'local_catalog_browser'), 'error');
    echo $OUTPUT->footer();
    die();
}
require_capability('local/catalog_browser:view', context_system::instance());

// Register the AMD module that handles autocomplete and dynamic search.
$PAGE->requires->js_call_amd('local_catalog_browser/search', 'init');

// Retrieve the custom field category configured in plugin settings.
// If none is configured, custom field filters are simply omitted — the catalog
// still works with the title, category, and tag filters if they are enabled.
$categoryid   = get_config('local_catalog_browser', 'categoryid');
$customfields = $categoryid ? course_search::get_customfields($categoryid) : [];

// Retrieve the custom field category configured in plugin settings.
// If none is configured, custom field filters are simply omitted — the catalog
// still works with the title, category, and tag filters if they are enabled.
$categoryid   = get_config('local_catalog_browser', 'categoryid');
$customfields = $categoryid ? course_search::get_customfields($categoryid) : [];

// Collect submitted filter values from the URL for each custom field.
$filters = [];
foreach ($customfields as $field) {
    $filters[$field->shortname] = optional_param($field->shortname, '', PARAM_TEXT);
}

// Course title filter (plain text partial match).
$filtertitle = optional_param('coursetitle', '', PARAM_TEXT) ?? '';

// Selected tag IDs for tag-based filtering.
$selectedtags = array_map('intval', optional_param_array('tags', [], PARAM_INT) ?? []);

// Pagination: current page index and number of results per page (from plugin settings).
$page    = optional_param('page', 0, PARAM_INT);
$perpage = get_config('local_catalog_browser', 'perpage');
$perpage = ($perpage === false || $perpage === '') ? 10 : (int)$perpage;

// Active sort key: az (alphabetical), recent (newest first), or moodle (default DB order).
$sort = optional_param('sort', 'az', PARAM_ALPHA);

// Active filter toggles — each can be disabled independently in plugin settings.
$showtitlefilter    = (bool)get_config('local_catalog_browser', 'showtitlefilter');
$showcategoryfilter = (bool)get_config('local_catalog_browser', 'showcategoryfilter');
$showtagfilter      = (bool)get_config('local_catalog_browser', 'showtagfilter');
$selectedcategory   = (int)(optional_param('categoryid', 0, PARAM_INT) ?? 0);

// Maximum selectable tags — clamped to 1–25, default 3.
$maxtagselection = (int)get_config('local_catalog_browser', 'maxtagselection');
$maxtagselection = ($maxtagselection < 1 || $maxtagselection > 25) ? 3 : $maxtagselection;

// Popular tag suggestions — enabled/disabled and count from plugin settings, default 3.
$showpopulartags  = (bool)get_config('local_catalog_browser', 'showpopulartags');
$populartagscount = (int)get_config('local_catalog_browser', 'populartagscount');
$populartagscount = ($populartagscount < 1) ? 3 : $populartagscount;

// Configure the Moodle page: context, URL, title, and heading.
// set_url() includes all active filter parameters so that returnurl in course links
// points back to the exact filtered and paginated state the user was in.
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/catalog_browser/index.php', array_merge(
    $filters,
    [
        'coursetitle' => $filtertitle,
        'sort'        => $sort,
        'page'        => $page,
        'categoryid'  => $selectedcategory,
    ]
)));
$PAGE->set_title(get_string('pagetitle', 'local_catalog_browser'));
$PAGE->set_heading(get_string('pagetitle', 'local_catalog_browser'));

// Run the course search with all active filters.
$courses     = course_search::search($customfields, $filters, $filtertitle, $selectedtags, $sort, $selectedcategory);
$totalcount  = count($courses);
$totalpages  = (int)ceil($totalcount / $perpage);

// Clamp the page index to valid bounds.
$page        = max(0, min($page, max(0, $totalpages - 1)));

// Slice the full result set to the current page.
$coursespaged = array_slice($courses, $page * $perpage, $perpage);

// Retrieve all tags currently used on at least one course, sorted alphabetically.
$tagsdata = [];
$tags = $DB->get_records_sql("
    SELECT DISTINCT t.id, t.rawname
      FROM {tag} t
      JOIN {tag_instance} ti ON ti.tagid = t.id
     WHERE ti.itemtype = 'course'
     ORDER BY t.rawname ASC
");
foreach ($tags as $tag) {
    $tagsdata[] = [
        'id'      => $tag->id,
        'rawname' => $tag->rawname,
        // Pre-check tags that were selected in the current request.
        'checked' => in_array($tag->id, $selectedtags),
    ];
}

// Retrieve the most-used tags, ranked by number of course tag instances.
// Only shown when no tag is already selected and the feature is enabled.
$populartagsdata = [];
if ($showtagfilter && $showpopulartags && empty($selectedtags)) {
    $populartags = $DB->get_records_sql("
        SELECT t.id, t.rawname, COUNT(ti.id) AS usecount
          FROM {tag} t
          JOIN {tag_instance} ti ON ti.tagid = t.id
         WHERE ti.itemtype = 'course'
         GROUP BY t.id, t.rawname
         ORDER BY usecount DESC, t.rawname ASC
    ", [], 0, $populartagscount);
    foreach ($populartags as $tag) {
        $populartagsdata[] = [
            'id'      => $tag->id,
            'rawname' => $tag->rawname,
        ];
    }
}

// Build the field data array for the template.
// Each entry contains type flags, current value, placeholder, and options (for select fields).
$fields = [];
foreach ($customfields as $field) {
    $currentvalue = $filters[$field->shortname] ?? '';

    // Use the field description as placeholder text, falling back to the field name.
    $description = format_text(
        $field->description,
        $field->descriptionformat,
        ['context' => context_system::instance()]
    );
    $description = trim(strip_tags($description));
    $placeholder = !empty($description) ? $description : $field->name;

    // Truncate long placeholders to keep the UI clean.
    if (strlen($placeholder) > 30) {
        $placeholder = substr($placeholder, 0, 27) . '...';
    }

    $fielddata = [
        'shortname'       => $field->shortname,
        'name'            => $field->name,
        'placeholder'     => $placeholder,
        'current_value'   => $currentvalue,
        'is_select'       => $field->type === 'select',   // Rendered as a dropdown.
        'is_checkbox'     => $field->type === 'checkbox', // Rendered as a yes/no dropdown.
        'is_number'       => $field->type === 'number',   // Rendered as a number input.
        'is_textarea'     => $field->type === 'textarea', // Rendered as a plain text input.
        'is_text'         => $field->type === 'text',     // Rendered as a text input with autocomplete.
        'is_yes_selected' => ($currentvalue === '1'),     // Pre-selects "Yes" for checkbox fields.
        'is_no_selected'  => ($currentvalue === '0'),     // Pre-selects "No" for checkbox fields.
    ];

    // For select fields, parse the newline-separated options from configdata.
    if ($field->type === 'select') {
        $config  = json_decode($field->configdata, true);
        $options = [];
        if (!empty($config['options'])) {
            $raw = array_filter(array_map('trim', preg_split('/\\r\\n|\\r|\\n/', $config['options'])));
            $idx = 1;
            foreach ($raw as $opt) {
                $options[] = [
                    'index'    => $idx,
                    'label'    => $opt,
                    // Pre-select the option matching the current filter value.
                    'selected' => ((string)$currentvalue === (string)$idx),
                ];
                $idx++;
            }
        }
        $fielddata['options'] = $options;
    }

    $fields[] = $fielddata;
}

// Build the course card data array for the current page.
$coursesdata = [];
foreach ($coursespaged as $course) {

    // Retrieve the first valid overview image URL for the course, if any.
    $imgurl    = '';
    $courseobj = new \core_course_list_element($course);
    foreach ($courseobj->get_course_overviewfiles() as $file) {
        if ($file->is_valid_image()) {
            $imgurl = file_encode_url(
                new moodle_url('/pluginfile.php'),
                '/' . $file->get_contextid() . '/' . $file->get_component() .
                '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename(),
                false
            );
            break;
        }
    }

    // Retrieve native Moodle tags associated with the course.
    $coursetags = \core_tag_tag::get_item_tags('core', 'course', $course->id);
    $tagslist   = array_map(fn($t) => ['name' => $t->rawname], array_values($coursetags));

    // Strip HTML from the summary and truncate to 200 characters.
    $summary = strip_tags(format_text($course->summary ?? '', $course->summaryformat ?? FORMAT_HTML));
    $summary = trim($summary);
    if (mb_strlen($summary) > 200) {
        $summary = mb_substr($summary, 0, 197) . '...';
    }

    // Build the returnurl manually — moodle_url encodes nested URLs passed as parameters,
    // and does not support array parameters such as tags[].
    $returnurlparams = array_merge(
        $filters,
        [
            'coursetitle' => $filtertitle,
            'sort'        => $sort,
            'page'        => $page,
            'categoryid'  => $selectedcategory,
        ]
    );
    $returnurl = (new moodle_url('/local/catalog_browser/index.php', $returnurlparams))->out(false);
    foreach ($selectedtags as $tagid) {
        $returnurl .= '&tags[]=' . $tagid;
    }

    // Points to the catalog preview page rather than course/view.php directly.
    // returnurl preserves filters, sort, pagination, and selected tags for the back-to-catalog button.
    $coursesdata[] = [
        'fullname' => format_string($course->fullname),
        'url'      => (new moodle_url('/local/catalog_browser/course.php', [
            'id'        => $course->id,
            'returnurl' => $returnurl,
        ]))->out(false),
        'imgurl'   => $imgurl,
        'tags'     => $tagslist,
        'summary'  => $summary,
    ];
}

// Build the base sort URL parameters, preserving all active filters.
$sorturlparams = array_merge($filters, [
    'coursetitle' => $filtertitle,
    'categoryid'  => $selectedcategory,
    'page'        => 0,
]);

// Tags cannot be passed as an array via moodle_url, so they are appended manually.
$tagstring      = '';
foreach ($selectedtags as $tagid) {
    $tagstring .= '&tags[]=' . $tagid;
}

// Sort URLs for each sort option, with tags appended manually.
$sorturlaz     = (new moodle_url('/local/catalog_browser/index.php',
    array_merge($sorturlparams, ['sort' => 'az'])))->out(false) . $tagstring;
$sorturlrecent = (new moodle_url('/local/catalog_browser/index.php',
    array_merge($sorturlparams, ['sort' => 'recent'])))->out(false) . $tagstring;
$sorturlmoodle = (new moodle_url('/local/catalog_browser/index.php',
    array_merge($sorturlparams, ['sort' => 'moodle'])))->out(false) . $tagstring;

// Build the pagination data array for the template.
$pagination = [];
for ($i = 0; $i < $totalpages; $i++) {
    $urlparams = array_merge($filters, [
        'coursetitle' => $filtertitle,
        'sort'        => $sort,
        'page'        => $i,
        'categoryid'  => $selectedcategory,
    ]);
    $baseurl   = new moodle_url('/local/catalog_browser/index.php', $urlparams);

    // Append tag parameters manually for the same reason as sort URLs.
    $tagstring = '';
    foreach ($selectedtags as $tagid) {
        $tagstring .= '&tags[]=' . $tagid;
    }

    $pagination[] = [
        'num'    => $i + 1,                          // Human-readable page number (1-based).
        'page'   => $i,                              // Zero-based page index.
        'active' => $i === $page,                   // Whether this is the current page.
        'url'    => $baseurl->out(false) . $tagstring, // Full URL for this page link.
    ];
}

// Build the Moodle course category list for the category filter dropdown.
$categoriesdata = [];
if ($showcategoryfilter) {
    $allcategories = \core_course_category::make_categories_list();
    foreach ($allcategories as $catid => $catname) {
        $categoriesdata[] = [
            'id'       => $catid,
            'name'     => $catname,
            // Pre-select the category matching the current filter value.
            'selected' => $catid === $selectedcategory,
        ];
    }
}

// Assemble the full template data array passed to the Mustache template.
$templatedata = [
    // Filter form fields.
    'fields'               => $fields,                  // Custom field filter inputs.
    'filtertitle'          => $filtertitle,              // Current course title filter value.

    // Tag filter.
    'tags'                 => $tagsdata,                 // All available tags with checked state.
    'has_tags'             => $showtagfilter && !empty($tagsdata), // Whether to show the tag filter.
    'maxtags'              => $maxtagselection,          // Maximum number of selectable tags.
    // Message shown when limit is reached.
    'limitmsgtags'         => get_string('filtertags_limit', 'local_catalog_browser', $maxtagselection),
    'tags_json'            => json_encode(array_values($tagsdata)), // JSON for JS tag selector.
    'popular_tags'         => $populartagsdata,               // Popular tag suggestions.
    'has_popular_tags'     => !empty($populartagsdata),       // Whether to show the suggestions block.
    'populartags_label'    => get_string('populartags_label', 'local_catalog_browser'),

    // Title filter.
    'show_title_filter'    => $showtitlefilter,           // Whether to show the course title filter.

    // Category filter.
    'has_category_filter'  => $showcategoryfilter,       // Whether to show the category filter.
    'categories'           => $categoriesdata,           // List of categories for the dropdown.
    'selected_category'    => $selectedcategory,         // Currently selected category ID.

    // Course results.
    'results_count'        => $totalcount,               // Total number of matching courses.
    'has_courses'          => !empty($coursespaged),     // Whether any courses are on this page.
    'no_results'           => empty($courses),           // Whether the search returned no results at all.
    'courses'              => $coursesdata,              // Course card data for the current page.

    // Sorting.
    'sort_az'              => $sort === 'az',            // True when alphabetical sort is active.
    'sort_recent'          => $sort === 'recent',        // True when most-recent sort is active.
    'sort_moodle'          => $sort === 'moodle',        // True when default sort is active.
    'sorturl_az'           => $sorturlaz,               // URL for alphabetical sort.
    'sorturl_recent'       => $sorturlrecent,           // URL for most-recent sort.
    'sorturl_moodle'       => $sorturlmoodle,           // URL for default sort.

    // Pagination.
    'pagination'           => $pagination,               // Array of page link data.
    'has_pagination'       => $totalpages > 1,           // Whether to show the pagination nav.
    'perpage'              => $perpage,                  // Results per page (passed to JS via data attribute).

    // Localised strings passed to JS via data attributes on the results container.
    'results_dynamic_string' => get_string('results', 'local_catalog_browser', '__COUNT__'),
    'noresults_string'       => get_string('noresults', 'local_catalog_browser'),
    'remove_tag_string'      => get_string('removetag', 'local_catalog_browser'),

    // Plugin page URL used for the reset button and sort links.
    'search_url'           => (new moodle_url('/local/catalog_browser/index.php'))->out(false),
];

// Render the page using the active Moodle theme.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog_browser/catalog_browser', $templatedata);
echo $OUTPUT->footer();
