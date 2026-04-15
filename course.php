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
 * Course preview page for the catalog_browser plugin.
 * Displays course title, image, full description, and tags to any visitor.
 * Authenticated users with course access are redirected directly to the course.
 * Users without access see a login prompt or an access-denied notice.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Required course ID — hard-fail if absent or invalid.
$courseid  = required_param('id', PARAM_INT);

// Return URL pointing back to the catalog, with filters and pagination preserved.
// PARAM_URL is used rather than PARAM_LOCALURL as the latter strips query parameters
// and fragments, breaking filter and pagination preservation.
$returnurl = optional_param('returnurl', '', PARAM_RAW);

// Reject external URLs to prevent open redirect — only allow URLs on this Moodle site.
if (!empty($returnurl) && strpos($returnurl, $CFG->wwwroot) !== 0) {
    $returnurl = (new moodle_url('/local/catalog_browser/index.php'))->out(false);
}

// Fall back to the plain catalog index if no return URL was provided.
if (empty($returnurl)) {
    $returnurl = (new moodle_url('/local/catalog_browser/index.php'))->out(false);
}

// Load the course record — throws a dml_missing_record_exception if not found.
$course = $DB->get_record('course', ['id' => $courseid, 'visible' => 1], '*', MUST_EXIST);

// Enforce access control: require login unless guest access is enabled in plugin settings.
$allowguests = get_config('local_catalog_browser', 'allowguests');
if (!$allowguests) {
    require_login();
}
require_capability('local/catalog_browser:view', context_system::instance());

// Authenticated non-guest users are always redirected directly to the course page.
// Moodle handles enrolment natively — enrolled users access the course immediately,
// non-enrolled users see the native enrolment options page.
if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

// Configure the Moodle page: context, URL, title, and heading.
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/catalog_browser/course.php', ['id' => $courseid]));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

// Retrieve the first valid overview image URL for the course, if any.
$imgurl    = '';
$courseobj = new core_course_list_element($course);
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
$coursetags = core_tag_tag::get_item_tags('core', 'course', $course->id);
$tagslist   = array_map(fn($t) => ['name' => $t->rawname], array_values($coursetags));

// Render the full course description (not truncated — this is the preview page).
$description = format_text($course->summary ?? '', $course->summaryformat ?? FORMAT_HTML,
    ['context' => context_system::instance()]);

// Determine which access notice to display.
// - Not logged in: invite to log in.
// - Logged in but no access: inform the user they are not enrolled.
$isguest       = !isloggedin() || isguestuser();
$loginurl      = (new moodle_url('/login/index.php',
    ['wantsurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false)]))->out(false);

// Assemble the template data array.
$templatedata = [
    // Course content.
    'fullname'    => format_string($course->fullname),
    'imgurl'      => $imgurl,
    'description' => $description,
    'tags'        => $tagslist,
    'has_tags'    => !empty($tagslist),

    // True for unauthenticated and guest-role users — drives the login notice conditional.
    'is_guest'    => $isguest,

    // URLs.
    'login_url'   => $loginurl,            // Login URL pre-filled with the course as wantsurl.

    // Return URL for the back-to-catalog button, with filters and pagination preserved.
    'return_url' => $returnurl,
];

// Render the page using the active Moodle theme.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_catalog_browser/course_preview', $templatedata);
echo $OUTPUT->footer();
