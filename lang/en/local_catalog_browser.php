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
 * English language strings for the catalog_browser plugin.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Prevent direct access to this file outside of Moodle.
defined('MOODLE_INTERNAL') || die();


// Section: Core plugin strings.


// Plugin name displayed in the Moodle administration interface.
$string['pluginname'] = 'Catalog Browser';

// Page title displayed at the top of the catalog browser page.
$string['pagetitle'] = 'Search in the catalog';

// Label for the search submit button.
$string['search'] = 'Search';

// Displayed when no courses match the active filters (Mustache and JS).
$string['noresults'] = 'No resources or courses match these criteria.';

// Displayed above the results list; {$a} is replaced by the total result count.
$string['results'] = '{$a} result(s) found';

// Displayed when a guest tries to access the catalog and guest access is disabled.
$string['guestdenied'] = 'Access to the catalog is not available to anonymous visitors. Please log in.';

// Displayed in the Moodle privacy registry to explain that this plugin stores no personal data.
$string['privacy:metadata'] = 'The Catalog Browser plugin does not store any personal data. It only reads course data and custom field values to display the catalog.';


// Section: Admin settings strings.


// Introductory description shown at the top of the plugin settings page.
$string['setting_catalogurl'] = 'Catalog page URL';

// Description displaying the full URL to the catalog browser page.
$string['setting_catalogurl_desc'] = 'Direct link to the catalog browser page: <a href="{$a}" target="_blank">{$a}</a>';


// Section: Custom field filters.


// Heading for the custom field category section.
$string['setting_section_fields'] = 'Custom field filters';

// Label for the custom field category selector.
$string['setting_category'] = 'Custom field category';

// Description for the custom field category selector.
$string['setting_category_desc'] = 'Choose the custom field category whose fields will be used as filters on the catalog browser page. Leave empty to disable custom field filters.';

// Displayed when no custom field category exists yet.
$string['setting_category_none'] = 'No custom field category available. Please create one in the course custom fields settings.';


// Section: Active filters.


// Heading for the active filters section.
$string['setting_section_filters'] = 'Active filters';

// Label for the course title filter toggle.
$string['setting_showtitlefilter'] = 'Show course title filter';

// Description for the course title filter toggle.
$string['setting_showtitlefilter_desc'] = 'If enabled, a course title text filter will be displayed on the catalog browser page.';

// Label for the category filter toggle.
$string['setting_showcategoryfilter'] = 'Show category filter';

// Description for the category filter toggle.
$string['setting_showcategoryfilter_desc'] = 'If enabled, a Moodle course category filter will be displayed on the catalog browser page.';

// Label for the tag filter toggle.
$string['setting_showtagfilter'] = 'Show tag filter';

// Description for the tag filter toggle.
$string['setting_showtagfilter_desc'] = 'If enabled, a tag filter will be displayed on the catalog browser page.';

// Label for the maximum tag selection setting.
$string['setting_maxtagselection'] = 'Maximum selectable tags';

// Description for the maximum tag selection setting.
$string['setting_maxtagselection_desc'] = 'Maximum number of tags a user can select at once (between 1 and 25). Has no effect if the tag filter is disabled.';

// Label for the popular tags suggestions toggle.
$string['setting_showpopulartags'] = 'Show popular tag suggestions';

// Description for the popular tags suggestions toggle.
$string['setting_showpopulartags_desc'] = 'If enabled, the most frequently used tags are displayed as clickable suggestions below the tag search input, when no tag has been selected yet.';

// Label for the popular tags count setting.
$string['setting_populartagscount'] = 'Number of popular tags to display';

// Description for the popular tags count setting.
$string['setting_populartagscount_desc'] = 'Number of popular tags shown as suggestions. Has no effect if popular tag suggestions or the tag filter are disabled.';

// Label displayed before the popular tag suggestion pills.
$string['populartags_label'] = 'Popular tags:';


// Section: Results display.


// Heading for the results display section.
$string['setting_section_results'] = 'Results display';

// Label for the results per page setting.
$string['setting_perpage'] = 'Results per page';

// Description for the results per page setting.
$string['setting_perpage_desc'] = 'Number of results displayed per page. Default: 10.';


// Section: Access control.


// Heading for the access control section.
$string['setting_section_access'] = 'Access control';

// Label for the guest access toggle.
$string['setting_allowguests'] = 'Allow guest access';

// Description for the guest access toggle.
$string['setting_allowguests_desc'] = 'If enabled, non-logged-in users can access the catalog browser page.';


// Section: Filter field labels and placeholders.


// Label for the course title text filter.
$string['filtertitle'] = 'Course title';

// Placeholder text shown inside the course title filter input.
$string['filtertitle_placeholder'] = 'Search by title...';

// Label for the tag filter.
$string['filtertags'] = 'Tags';

// Placeholder text shown inside the tag search input.
$string['filtertags_placeholder'] = 'Search for a tag...';

// Message shown when the user has reached the maximum number of selectable tags; {$a} is the limit.
$string['filtertags_limit'] = 'Maximum {$a} tags selected.';

// Label for the Moodle course category filter.
$string['filtercategory'] = 'Category';

// Default option in the category filter dropdown (no category restriction).
$string['filtercategory_all'] = 'All categories';


// Section: Sorting strings.


// Label preceding the sort buttons.
$string['sortby'] = 'Sort by:';

// Sort button label for alphabetical ascending order.
$string['sort_az'] = 'A→Z';

// Sort button label for most recently created courses first.
$string['sort_recent'] = 'Most recent';

// Sort button label for oldest courses first.
$string['sort_oldest'] = 'Oldest first';


// Section: Miscellaneous UI strings.


// Accessible label for the remove button on a selected tag pill; {$a} is the tag name.
$string['removetag'] = 'Remove {$a}';

// Label for the back-to-catalog button on the course preview page.
$string['backtocatalog'] = 'Back to catalog';

// Notice shown to unauthenticated users on the course preview page.
$string['preview_loginnotice'] = 'Log in to access this course.';

// Label for the login button on the course preview page.
$string['preview_loginbutton'] = 'Log in';

// Notice shown to authenticated users who are not yet enrolled in the course.
$string['preview_enrolnotice'] = 'You are not enrolled in this course yet. Click below to access the enrolment page.';

// Label for the enrolment button on the course preview page.
$string['preview_enrolbutton'] = 'Access course';
