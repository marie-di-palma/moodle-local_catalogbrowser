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
 * French language strings for the catalog_browser plugin.
 *
 * @package    local_catalog_browser
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Prevent direct access to this file outside of Moodle.
defined('MOODLE_INTERNAL') || die();


// Section: Core plugin strings.


// Plugin name displayed in the Moodle administration interface.
$string['pluginname'] = 'Navigateur de catalogue';

// Page title displayed at the top of the catalog browser page.
$string['pagetitle'] = 'Rechercher dans le catalogue';

// Label for the search submit button.
$string['search'] = 'Rechercher';

// Displayed when no courses match the active filters (Mustache and JS).
$string['noresults'] = 'Aucune ressource ou cours ne correspond aux critères.';

// Displayed above the results list; {$a} is replaced by the total result count.
$string['results'] = '{$a} résultat(s) trouvé(s)';

// Displayed when a guest tries to access the catalog and guest access is disabled.
$string['guestdenied'] = "L'administrateur n'a pas autorisé l'accès au catalogue pour les visiteurs anonymes. Veuillez vous connecter.";

// Displayed in the Moodle privacy registry to explain that this plugin stores no personal data.
$string['privacy:metadata'] = 'Le plugin Catalog Browser ne stocke aucune donnée personnelle. Il lit uniquement les données de cours et les valeurs de champs personnalisés pour afficher le catalogue.';


// Section: Admin settings strings.


// Introductory description shown at the top of the plugin settings page.
$string['setting_catalogurl'] = 'URL de la page du catalogue';

// Description displaying the full URL to the catalog browser page.
$string['setting_catalogurl_desc'] = 'Lien direct vers la page du catalogue : <a href="{$a}" target="_blank">{$a}</a>';


// Section: Custom field filters.


// Heading for the custom field category section.
$string['setting_section_fields'] = 'Filtres sur les champs personnalisés';

// Label for the custom field category selector.
$string['setting_category'] = 'Catégorie de champs personnalisés';

// Description for the custom field category selector.
$string['setting_category_desc'] = 'Sélectionnez la catégorie de champs personnalisés dont les champs seront utilisés comme filtres. Laissez vide pour désactiver les filtres sur les champs personnalisés.';

// Displayed when no custom field category exists yet.
$string['setting_category_none'] = 'Aucune catégorie de champs personnalisés disponible. Veuillez en créer une depuis les paramètres des champs personnalisés de cours.';


// Section: Active filters.


// Heading for the active filters section.
$string['setting_section_filters'] = 'Filtres actifs';

// Label for the course title filter toggle.
$string['setting_showtitlefilter'] = 'Afficher le filtre par titre';

// Description for the course title filter toggle.
$string['setting_showtitlefilter_desc'] = 'Si activé, un filtre sur le titre du cours sera affiché sur la page du catalogue.';

// Label for the category filter toggle.
$string['setting_showcategoryfilter'] = 'Afficher le filtre par catégorie';

// Description for the category filter toggle.
$string['setting_showcategoryfilter_desc'] = 'Si activé, un filtre par catégorie Moodle sera affiché sur la page du catalogue.';

// Label for the tag filter toggle.
$string['setting_showtagfilter'] = 'Afficher le filtre par tags';

// Description for the tag filter toggle.
$string['setting_showtagfilter_desc'] = 'Si activé, un filtre par tags sera affiché sur la page du catalogue.';

// Label for the maximum tag selection setting.
$string['setting_maxtagselection'] = 'Nombre maximum de tags sélectionnables';

// Description for the maximum tag selection setting.
$string['setting_maxtagselection_desc'] = 'Nombre maximum de tags que l\'utilisateur peut sélectionner simultanément (entre 1 et 25). Sans effet si le filtre par tags est désactivé.';

// Label for the popular tags suggestions toggle.
$string['setting_showpopulartags'] = 'Afficher les suggestions de tags populaires';

// Description for the popular tags suggestions toggle.
$string['setting_showpopulartags_desc'] = 'Si activé, les tags les plus utilisés sont affichés sous forme de suggestions cliquables sous le champ de recherche de tags, tant qu\'aucun tag n\'a encore été sélectionné.';

// Label for the popular tags count setting.
$string['setting_populartagscount'] = 'Nombre de tags populaires à afficher';

// Description for the popular tags count setting.
$string['setting_populartagscount_desc'] = 'Nombre de tags populaires affichés en suggestions. Sans effet si les suggestions de tags populaires ou le filtre par tags sont désactivés.';

// Label displayed before the popular tag suggestion pills.
$string['populartags_label'] = 'Tags populaires :';


// Section: Results display.


// Heading for the results display section.
$string['setting_section_results'] = 'Affichage des résultats';

// Label for the results per page setting.
$string['setting_perpage'] = 'Résultats par page';

// Description for the results per page setting.
$string['setting_perpage_desc'] = 'Nombre de résultats affichés par page. Par défaut : 10.';


// Section: Access control.


// Heading for the access control section.
$string['setting_section_access'] = 'Contrôle d\'accès';

// Label for the guest access toggle.
$string['setting_allowguests'] = 'Autoriser les invités';

// Description for the guest access toggle.
$string['setting_allowguests_desc'] = 'Si activé, les utilisateurs non connectés peuvent accéder à la page du catalogue.';


// Section: Filter field labels and placeholders.


// Label for the course title text filter.
$string['filtertitle'] = 'Nom du cours';

// Placeholder text shown inside the course title filter input.
$string['filtertitle_placeholder'] = 'Rechercher par nom de cours...';

// Label for the tag filter.
$string['filtertags'] = 'Tags';

// Placeholder text shown inside the tag search input.
$string['filtertags_placeholder'] = 'Rechercher un tag...';

// Message shown when the user has reached the maximum number of selectable tags; {$a} is the limit.
$string['filtertags_limit'] = 'Maximum {$a} tags sélectionnés.';

// Label for the Moodle course category filter.
$string['filtercategory'] = 'Catégorie';

// Default option in the category filter dropdown (no category restriction).
$string['filtercategory_all'] = 'Toutes les catégories';


// Section: Sorting strings.


// Label preceding the sort buttons.
$string['sortby'] = 'Trier par :';

// Sort button label for alphabetical ascending order.
$string['sort_az'] = 'A→Z';

// Sort button label for most recently created courses first.
$string['sort_recent'] = 'Plus récents';

// Sort button label for oldest courses first.
$string['sort_oldest'] = 'Plus anciens';


// Section: Miscellaneous UI strings.


// Accessible label for the remove button on a selected tag pill; {$a} is the tag name.
$string['removetag'] = 'Supprimer {$a}';

// Label for the back-to-catalog button on the course preview page.
$string['backtocatalog'] = 'Retour au catalogue';

// Notice shown to unauthenticated users on the course preview page.
$string['preview_loginnotice'] = 'Connectez-vous pour accéder à ce cours.';

// Label for the login button on the course preview page.
$string['preview_loginbutton'] = 'Se connecter';

// Notice shown to authenticated users who are not yet enrolled in the course.
$string['preview_enrolnotice'] = 'Vous n\'êtes pas encore inscrit(e) à ce cours. Cliquez ci-dessous pour accéder à la page d\'inscription.';

// Label for the enrolment button on the course preview page.
$string['preview_enrolbutton'] = 'Accéder au cours';
