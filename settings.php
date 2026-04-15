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
 * Admin settings for the catalog_browser plugin.
 * Registers the plugin settings page under Site administration > Plugins > Local plugins.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/catalog_browser/locallib.php');

if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'local_catalog_browser',
        get_string('pluginname', 'local_catalog_browser')
    );


    // Catalog URL — displayed at the top for easy access and sharing.

    $catalogurl = (new moodle_url('/local/catalog_browser/index.php'))->out(false);
    $settings->add(new admin_setting_description(
        'local_catalog_browser/catalogurl',
        get_string('setting_catalogurl', 'local_catalog_browser'),
        get_string('setting_catalogurl_desc', 'local_catalog_browser', $catalogurl)
    ));


    // Section 1: Custom field filters.

    $settings->add(new admin_setting_heading(
        'local_catalog_browser/heading_fields',
        get_string('setting_section_fields', 'local_catalog_browser'),
        ''
    ));

    global $DB;

    $categories = $DB->get_records_menu('customfield_category', null, 'name ASC', 'id, name');

    if (empty($categories)) {
        // No custom field categories exist yet — show an informational notice.
        $settings->add(new admin_setting_description(
            'local_catalog_browser/nocategory',
            get_string('setting_category', 'local_catalog_browser'),
            get_string('setting_category_none', 'local_catalog_browser')
        ));
    } else {
        // Prepend an empty option so the admin can explicitly disable custom field filters.
        $categoryoptions = ['' => get_string('none', 'moodle')] + $categories;
        $settings->add(new admin_setting_configselect(
            'local_catalog_browser/categoryid',
            get_string('setting_category', 'local_catalog_browser'),
            get_string('setting_category_desc', 'local_catalog_browser'),
            '',
            $categoryoptions
        ));
    }


    // Section 2: Active filters.

    $settings->add(new admin_setting_heading(
        'local_catalog_browser/heading_filters',
        get_string('setting_section_filters', 'local_catalog_browser'),
        ''
    ));

    // Show or hide the course title filter (default: enabled).
    $settings->add(new admin_setting_configcheckbox(
        'local_catalog_browser/showtitlefilter',
        get_string('setting_showtitlefilter', 'local_catalog_browser'),
        get_string('setting_showtitlefilter_desc', 'local_catalog_browser'),
        1
    ));

    // Show or hide the Moodle course category filter (default: enabled).
    $settings->add(new admin_setting_configcheckbox(
        'local_catalog_browser/showcategoryfilter',
        get_string('setting_showcategoryfilter', 'local_catalog_browser'),
        get_string('setting_showcategoryfilter_desc', 'local_catalog_browser'),
        1
    ));

    // Show or hide the tag filter (default: enabled).
    $settings->add(new admin_setting_configcheckbox(
        'local_catalog_browser/showtagfilter',
        get_string('setting_showtagfilter', 'local_catalog_browser'),
        get_string('setting_showtagfilter_desc', 'local_catalog_browser'),
        1
    ));

    // Maximum number of tags selectable at once (between 1 and 25, default: 3).
    // Has no effect if the tag filter is disabled.
    $settings->add(new admin_setting_configtext(
        'local_catalog_browser/maxtagselection',
        get_string('setting_maxtagselection', 'local_catalog_browser'),
        get_string('setting_maxtagselection_desc', 'local_catalog_browser'),
        '3',
        PARAM_INT
    ));

    // Show popular tag suggestions below the tag search input (default: enabled).
    // Has no effect if the tag filter is disabled.
    $settings->add(new admin_setting_configcheckbox(
        'local_catalog_browser/showpopulartags',
        get_string('setting_showpopulartags', 'local_catalog_browser'),
        get_string('setting_showpopulartags_desc', 'local_catalog_browser'),
        1
    ));

    // Number of popular tags to display as suggestions (default: 3).
    // Has no effect if popular tag suggestions or the tag filter are disabled.
    $settings->add(new admin_setting_configtext(
        'local_catalog_browser/populartagscount',
        get_string('setting_populartagscount', 'local_catalog_browser'),
        get_string('setting_populartagscount_desc', 'local_catalog_browser'),
        '3',
        PARAM_INT
    ));


    // Section 3: Results display.

    $settings->add(new admin_setting_heading(
        'local_catalog_browser/heading_results',
        get_string('setting_section_results', 'local_catalog_browser'),
        ''
    ));

    // Number of course results displayed per page (default: 10).
    $settings->add(new admin_setting_configtext(
        'local_catalog_browser/perpage',
        get_string('setting_perpage', 'local_catalog_browser'),
        get_string('setting_perpage_desc', 'local_catalog_browser'),
        '10',
        PARAM_INT
    ));


    // Section 4: Access control.

    $settings->add(new admin_setting_heading(
        'local_catalog_browser/heading_access',
        get_string('setting_section_access', 'local_catalog_browser'),
        ''
    ));

    // Allow non-authenticated users to access the catalog browser (default: disabled).
    // Uses an inline subclass to trigger guest capability sync after each save.
    $settings->add(new class(
        'local_catalog_browser/allowguests',
        get_string('setting_allowguests', 'local_catalog_browser'),
        get_string('setting_allowguests_desc', 'local_catalog_browser'),
        0
    ) extends admin_setting_configcheckbox {
        /**
         * Saves the setting value and synchronises the guest role capability.
         *
         * @param  string $data The submitted value ('0' or '1').
         * @return string       Empty string on success, error message on failure.
         */
        public function write_setting($data): string {
            $result = parent::write_setting($data);
            if ($result === '') {
                local_catalog_browser_sync_guest_capability((bool)(int)$data);
            }
            return $result;
        }
    });

    $ADMIN->add('localplugins', $settings);
}
