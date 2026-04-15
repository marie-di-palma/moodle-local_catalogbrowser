# Catalog Browser (local_catalog_browser)

![Moodle](https://img.shields.io/badge/Moodle-4.3+-orange?logo=moodle)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-GPL%20v3-green?logo=gnu)
![Version](https://img.shields.io/badge/Version-1.0.0-blue)

A Moodle local plugin providing a browsable and filterable course catalog. Users can search for courses using custom field values, course title, native Moodle tags, and course category — all from a single page, with results updating dynamically without page reload.

## Features

- Filter courses by custom field values (text, select, checkbox, number, textarea)
- Filter courses by course title with AJAX autocomplete suggestions (optional)
- Filter courses by native Moodle tags with a pill-based selector and inline autocomplete (optional, configurable)
- Filter courses by Moodle course category (optional, configurable)
- Dynamic AJAX search: results update without page reload as filters change
- Sort results alphabetically (A→Z), by most recently created, or by default Moodle order (oldest first)
- Paginated results with configurable page size
- Course cards display the overview image, course title, tags, and a truncated summary from the course description
- Course preview page for unauthenticated users, with full description, tags, and back-to-catalog navigation
- Only visible courses are returned in search results
- Custom fields with visibility set to "Nobody" are automatically excluded from filters
- Placeholder text generated from the custom field description, with fallback to the field name
- Keyboard navigation in all autocomplete dropdowns (arrow keys, Enter, Escape)
- Reset button to clear all active filters
- Optional guest access (non-logged-in users can be allowed to browse the catalog)
- English and French language strings included

## Supported Field Types

| Field type | Filter type | Autocomplete | Match behaviour |
|---|---|---|---|
| Text | Text input | Yes | Contains (case-insensitive) |
| Textarea | Text input | No | Contains (case-insensitive) |
| Select | Dropdown | No | Exact match |
| Checkbox | Dropdown (Yes / No) | No | Exact match |
| Number | Number input | No | Exact match |

> **Note on date fields:** Date fields are intentionally not supported as filters. Filtering by date would require a range-based UI (from / to inputs) which adds significant complexity for limited practical value in a browsable catalog context. Date fields present on courses are silently ignored by the filter system.

## Requirements

- Moodle 4.2 or higher
- PHP 8.0 or higher
- At least one custom field category configured under Site administration → Courses → Course custom fields

## Installation

### Method 1: Via Moodle Admin Interface (Recommended)

1. Download `local_catalog_browser.zip`
2. Log in to Moodle as administrator
3. Navigate to **Site administration → Plugins → Install plugins**
4. Upload the ZIP file and click **Install plugin from the ZIP file**
5. Follow the on-screen prompts to complete the installation

### Method 2: Manual Installation

1. Extract the ZIP file
2. Copy the `catalog_browser` folder into `/path/to/moodle/local/`
3. Set proper permissions for your web server user (e.g. `chown -R www-data:www-data catalog_browser`)
4. Visit your Moodle site as administrator — the upgrade process will start automatically

## Configuration

After installation, go to **Site administration → Plugins → Local plugins → Catalog Browser** and configure the following settings:

> The direct URL to the catalog page is displayed at the top of the settings page for easy access and sharing.

| Setting | Description | Default |
|---|---|---|
| Category | Custom field category whose fields will be used as filters | — |
| Show title filter | Show a course title search input on the catalog page | Yes |
| Show category filter | Show a Moodle course category filter on the catalog page | Yes |
| Show tag filter | Show the tag filter on the catalog page | Yes |
| Maximum selectable tags | Maximum number of tags a user can select at once (1–25). Has no effect if the tag filter is disabled. | 3 |
| Show popular tag suggestions | Display the most-used tags as clickable suggestions below the tag search input, when no tag has been selected | Yes |
| Number of popular tags to display | Number of popular tag suggestions shown. Has no effect if popular tag suggestions or the tag filter are disabled | 3 |
| Results per page | Number of course results displayed per page | 10 |
| Allow guest access | Allow non-logged-in users to access the catalog | No |

## Usage

The catalog URL is available directly in the plugin settings page under **Site administration → Plugins → Local plugins → Catalog Browser**.

### Setting up custom field filters

The catalog filters are driven by Moodle course custom fields. To set them up:

1. Go to **Site administration → Courses → Course custom fields** and create a custom field category (e.g. "Catalog filters").
2. Add fields to that category. Each field will appear as a filter on the catalog page, in the order defined here.
3. In the plugin settings, select that category under **Category**.
4. To exclude a field from the catalog without deleting it, set its **Visibility** to **Nobody** — the plugin will skip it automatically.
5. The placeholder text shown inside each filter input is taken from the field's **Description**. If no description is set, the field name is used as a fallback.

Only fields of a supported type (text, textarea, select, checkbox, number) are shown. Fields of other types, including date fields, are silently ignored. If no category is selected or the selected category contains no eligible fields, the custom field filter section simply does not appear on the catalog page.

### Filtering courses

Filters are presented in the following fixed order on the catalog page:

1. **Course title** (if enabled) — always first
2. **Course category** (if enabled) — always second
3. **Custom field filters** — in the order defined in the custom field category
4. **Tags** (if enabled) — always last

This layout is intentional: title and category provide broad, immediate narrowing at the top, while tags — being a cross-cutting dimension — are placed at the end where they complement the other filters without interfering with the custom field order. This order is fixed and cannot be changed without modifying the template.

- **Course title**: type any part of the title — suggestions appear automatically after 2 characters
- **Category**: select a Moodle course category from the dropdown
- **Custom fields**: fill in or select values for any of the available filters
- **Tags**: type part of a tag name to see matching suggestions, then click to add it as a pill — click × on a pill to remove it
- Results update dynamically as filters change — no need to click Search for most filter types
- Click **Search** to apply the active filters explicitly, or press **Enter** in any text input (title or custom field filters) to trigger the same update immediately
- Click **Reset** to clear all active filters and return to the default view

### Sorting results

Three sort options are available via the sort bar above the results:

| Option | Description |
|---|---|
| A→Z | Alphabetical order by course title (locale-aware) |
| Most recent | Most recently created courses first |
| Oldest first | Default Moodle database order |

### Course preview page

The course preview page displays the course title, overview image, full description, and tags, along with a back-to-catalog button that preserves the active filters and pagination state.

The purpose of this page is to let administrators control what information is visible before authentication. Users can read a course description and decide whether to enrol without being exposed to any course content. This is particularly relevant when guest access is enabled.

- If the user is authenticated, they are redirected directly to `course/view.php` — Moodle handles access and enrolment natively from there.
- If the user is not logged in, the preview page is shown with a login prompt.

### Notes on search behaviour

- All active filters are combined: a course must match **all** of them to appear in results
- Text and textarea fields use a **contains** search (case-insensitive) — results include any course whose field value contains the search term, not just exact matches
- Select, checkbox and number fields use an **exact match**
- When multiple tags are selected, results include only courses matching **all** of the selected tags (AND logic)
- Hidden courses are never returned, regardless of the user's role
- When no tag has been selected yet, the most frequently used tags are displayed as clickable suggestions below the tag search input (if enabled in settings)

## Permissions and Capabilities

| Capability | Default roles | Description |
|---|---|---|
| `local/catalog_browser:view` | All authenticated users | Access the catalog browser and course preview pages |

### Guest access and automatic capability sync

Guest access is controlled via the **Allow guest access** setting in the plugin configuration. When this setting is toggled, the plugin automatically updates the `local/catalog_browser:view` capability on the Moodle guest role at the system context — no manual role editing is required.

- **Enabled**: the guest role receives an explicit `CAP_ALLOW` override for the capability.
- **Disabled**: the override is removed and the guest role reverts to its archetype default (`CAP_PREVENT`).

Changes take effect immediately after saving, without needing to visit **Site administration → Users → Permissions → Define roles**.

## Troubleshooting

### No custom field filters appear on the catalog page
No custom field category has been selected in the plugin settings, or the selected category contains no fields with a supported type, or all fields have their visibility set to "Nobody". Check your configuration under **Site administration → Plugins → Local plugins → Catalog Browser** and your custom fields under **Site administration → Courses → Course custom fields**.

### A filter appears but returns no results
Verify that courses have values entered for that custom field, and that those courses are visible. Hidden courses are excluded from all results.

### The tag filter does not appear
Either no tags have been assigned to any visible course, or the **Show tag filter** setting is disabled. Check your tag assignments or update the setting in **Site administration → Plugins → Local plugins → Catalog Browser**.

### The title filter does not appear
The **Show title filter** setting may be disabled. Go to **Site administration → Plugins → Local plugins → Catalog Browser** and enable it.

### The category filter does not appear
The **Show category filter** setting may be disabled. Go to **Site administration → Plugins → Local plugins → Catalog Browser** and enable it.

### Autocomplete suggestions do not appear
Autocomplete requires at least 2 characters to be typed. If suggestions still do not appear, purge Moodle caches under **Site administration → Development → Purge all caches** and ensure JavaScript is enabled in the browser.

### Results do not update dynamically
Dynamic search requires JavaScript. Ensure JavaScript is enabled in the browser and that Moodle caches have been purged after any update to the plugin files.

## Author
Marie Di Palma
Developed with AI assistance (Claude by Anthropic).

## License

This plugin is licensed under the [GNU GPL v3 or later](https://www.gnu.org/licenses/gpl-3.0.html).