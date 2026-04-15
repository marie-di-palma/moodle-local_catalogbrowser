/**
 * AMD module providing autocomplete and dynamic search for the catalog_browser plugin.
 * Handles custom field autocomplete dropdowns, tag selector with pills,
 * and AJAX-based course search with pagination and sorting.
 *
 * @module     local_catalog_browser/search
 * @copyright  2026 Marie Di Palma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// define() is the standard RequireJS module declaration used by Moodle AMD modules.
// 'core/ajax' is the Moodle module that handles AJAX calls to external functions.
define(['core/ajax'], function(Ajax) {

    /**
     * Attaches an autocomplete dropdown to a custom field text input.
     * Fetches suggestions via AJAX and supports keyboard navigation.
     *
     * @param {HTMLElement} input     The text input element to enhance
     * @param {string}      fieldname The custom field name used as the AJAX query parameter
     */
    function attachAutocomplete(input, fieldname) {

        // Create the accessible dropdown list element.
        var dropdown = document.createElement('ul');
        dropdown.setAttribute('role', 'listbox');
        dropdown.setAttribute('aria-label', fieldname);
        dropdown.style.cssText = 'position:absolute;background:white;border:1px solid #ccc;' +
            'border-radius:4px;list-style:none;margin:0;padding:0;min-width:200px;' +
            'box-shadow:0 2px 8px rgba(0,0,0,0.15);z-index:1000;display:none;';

        // Make the parent container relative so the dropdown positions correctly.
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(dropdown);

        // Timer reference used to debounce AJAX calls on input.
        var debounceTimer;

        // Index of the currently highlighted dropdown option (-1 = none).
        var activeIndex = -1;

        /**
         * Highlights the active item in the dropdown and updates aria-selected attributes.
         *
         * @param {NodeList} items The list of <li> elements in the dropdown
         */
        function updateActive(items) {
            items.forEach(function(li, idx) {
                if (idx === activeIndex) {
                    li.style.background = '#f0f0f0';
                    li.setAttribute('aria-selected', 'true');
                } else {
                    li.style.background = 'white';
                    li.setAttribute('aria-selected', 'false');
                }
            });
        }

        // Handle keyboard navigation: arrow keys, Enter to select, Escape to close.
        input.addEventListener('keydown', function(e) {
            var items = dropdown.querySelectorAll('li');

            // When there are no dropdown items, only handle Enter to trigger a search;
            // arrow keys have nothing to navigate so they are ignored.
            if (!items.length) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    input.dispatchEvent(new Event('change'));
                }
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                updateActive(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                updateActive(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIndex >= 0 && items[activeIndex]) {
                    // Select the highlighted option and trigger a search update.
                    input.value = items[activeIndex].textContent;
                    dropdown.style.display = 'none';
                    input.setAttribute('aria-expanded', 'false');
                    activeIndex = -1;
                    input.dispatchEvent(new Event('change'));
                } else {
                    // No suggestion selected: close the dropdown and trigger a search
                    // with the raw value currently typed in the input.
                    dropdown.style.display = 'none';
                    input.setAttribute('aria-expanded', 'false');
                    input.dispatchEvent(new Event('change'));
                }
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
                input.setAttribute('aria-expanded', 'false');
                activeIndex = -1;
            }
        });

        // Fetch autocomplete suggestions after a 300ms debounce delay (min 2 characters).
        input.addEventListener('input', function() {
            var query = input.value.trim();

            if (query.length < 2) {
                dropdown.style.display = 'none';
                input.setAttribute('aria-expanded', 'false');
                activeIndex = -1;
                return;
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                Ajax.call([{
                    methodname: 'local_catalog_browser_search',
                    args: {fieldname: fieldname, query: query},
                    done: function(results) {
                        dropdown.innerHTML = '';
                        activeIndex = -1;

                        if (results.length === 0) {
                            dropdown.style.display = 'none';
                            input.setAttribute('aria-expanded', 'false');
                            return;
                        }

                        // Build one accessible <li> option per result.
                        results.forEach(function(item) {
                            var li = document.createElement('li');
                            li.textContent = item.value;
                            li.setAttribute('role', 'option');
                            li.setAttribute('aria-selected', 'false');
                            li.style.cssText = 'padding:8px 12px;cursor:pointer;';
                            li.addEventListener('mouseenter', function() {
                                li.style.background = '#f0f0f0';
                            });
                            li.addEventListener('mouseleave', function() {
                                li.style.background = 'white';
                            });
                            // On click, populate the input and trigger a search update.
                            li.addEventListener('mousedown', function() {
                                input.value = item.value;
                                dropdown.style.display = 'none';
                                input.setAttribute('aria-expanded', 'false');
                                input.dispatchEvent(new Event('change'));
                            });
                            dropdown.appendChild(li);
                        });

                        dropdown.style.display = 'block';
                        input.setAttribute('aria-expanded', 'true');
                    },
                    fail: function(error) {
                        console.error('Autocomplete AJAX error:', error); // eslint-disable-line no-console
                    }
                }]);
            }, 300);
        });

        // When the input loses focus, close the dropdown and trigger a dynamic search update.
        input.addEventListener('blur', function() {
            dropdown.style.display = 'none';
            input.setAttribute('aria-expanded', 'false');
            input.dispatchEvent(new Event('change'));
        });

        // Close the dropdown when clicking outside the input's parent container.
        document.addEventListener('click', function(e) {
            if (!input.parentNode.contains(e.target)) {
                dropdown.style.display = 'none';
                input.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /**
     * Initialises the tag selector widget.
     * Provides a searchable dropdown, selected tag pills with remove buttons,
     * hidden inputs for form submission, and a maximum tag limit.
     *
     * @return {Object|undefined} Public API exposing getSelectedTagIds(), or undefined if
     *                            the tag selector element is not present in the DOM.
     */
    function initTagSelector() {
        // Root container holding tag data and configuration attributes.
        var container = document.getElementById('tag-selector');
        if (!container) {
            return;
        }

        // Localised string for the remove button aria-label, injected via data attribute.
        var removeString = container.dataset.removeString || 'Remove';

        // Full list of available tags decoded from the data-tags JSON attribute.
        var allTags = JSON.parse(container.dataset.tags || '[]');

        // Array of currently selected tag objects.
        var selectedTags = [];

        // DOM references for the tag search input, suggestion dropdown,
        // selected pills container, and hidden inputs container.
        var searchInput = document.getElementById('tag-search');
        var dropdown = document.getElementById('tag-dropdown');
        var selectedContainer = document.getElementById('tag-selected');
        var hiddenContainer = document.getElementById('tag-hidden-inputs');

        // Maximum number of selectable tags (0 = disabled, -1 = unlimited).
        var maxTags = parseInt(searchInput.dataset.maxtags, 10);

        // Index of the currently highlighted dropdown option (-1 = none).
        var activeIndex = -1;

        // Inline message shown when the tag limit is reached.
        var limitMsg = document.createElement('p');
        limitMsg.textContent = searchInput.dataset.limitMsg || '';
        limitMsg.className = 'text-muted small mb-0';
        limitMsg.style.display = 'none';
        searchInput.parentNode.insertBefore(limitMsg, searchInput.nextSibling);

        // Pre-select any tags that were already selected on page load (e.g. from URL params).
        allTags.forEach(function(tag) {
            if (tag.checked) {
                addTag(tag);
            }
        });

        /**
         * Highlights the active item in the tag dropdown and updates aria-selected attributes.
         *
         * @param {NodeList} items The list of <li> elements in the tag dropdown
         */
        function updateTagActive(items) {
            items.forEach(function(li, idx) {
                if (idx === activeIndex) {
                    li.style.background = '#f0f0f0';
                    li.setAttribute('aria-selected', 'true');
                } else {
                    li.style.background = 'white';
                    li.setAttribute('aria-selected', 'false');
                }
            });
        }

        // Handle keyboard navigation in the tag dropdown: arrow keys, Enter, Escape.
        searchInput.addEventListener('keydown', function(e) {
            var items = dropdown.querySelectorAll('li');
            if (!items.length) {
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                updateTagActive(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                updateTagActive(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIndex >= 0 && items[activeIndex]) {
                    // Resolve the tag object by its ID and add it.
                    var tagId = parseInt(items[activeIndex].dataset.tagid, 10);
                    var tag = allTags.find(function(t) {
                        return parseInt(t.id, 10) === tagId;
                    });
                    if (tag) {
                        addTag(tag);
                        searchInput.value = '';
                        dropdown.style.display = 'none';
                        searchInput.setAttribute('aria-expanded', 'false');
                        activeIndex = -1;
                    }
                }
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
                searchInput.setAttribute('aria-expanded', 'false');
                activeIndex = -1;
            }
        });

        // Filter and display matching tags as the user types (min 1 character).
        searchInput.addEventListener('input', function() {
            var query = searchInput.value.trim().toLowerCase();
            dropdown.innerHTML = '';
            activeIndex = -1;

            // Hide popular tag suggestions while the user is typing.
            var suggestionsBlock = document.getElementById('popular-tags-suggestions');
            if (suggestionsBlock) {
                suggestionsBlock.style.display = (query.length > 0 || selectedTags.length > 0) ? 'none' : '';
            }

            if (query.length < 1) {
                dropdown.style.display = 'none';
                searchInput.setAttribute('aria-expanded', 'false');
                return;
            }

            // Filter out already-selected tags and those not matching the query.
            var filtered = allTags.filter(function(tag) {
                return tag.rawname.toLowerCase().indexOf(query) !== -1 &&
                    !selectedTags.find(function(t) {
                        return t.id === tag.id;
                    });
            });

            if (filtered.length === 0) {
                dropdown.style.display = 'none';
                searchInput.setAttribute('aria-expanded', 'false');
                return;
            }

            // Build one accessible <li> option per matching tag.
            filtered.forEach(function(tag) {
                var li = document.createElement('li');
                li.textContent = tag.rawname;
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', 'false');
                li.dataset.tagid = tag.id;
                li.style.cssText = 'padding:8px 12px;cursor:pointer;';
                li.addEventListener('mouseenter', function() {
                    li.style.background = '#f0f0f0';
                });
                li.addEventListener('mouseleave', function() {
                    li.style.background = 'white';
                });
                // On click, add the tag and clear the search input.
                li.addEventListener('mousedown', function() {
                    addTag(tag);
                    searchInput.value = '';
                    dropdown.style.display = 'none';
                    searchInput.setAttribute('aria-expanded', 'false');
                });
                dropdown.appendChild(li);
            });

            dropdown.style.display = 'block';
            searchInput.setAttribute('aria-expanded', 'true');
        });

        // Close the tag dropdown when clicking outside the tag selector container.
        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                dropdown.style.display = 'none';
                searchInput.setAttribute('aria-expanded', 'false');
            }
        });

        /**
         * Adds a tag to the selection: creates a pill, a hidden input, and enforces the limit.
         * Dispatches a 'tagchange' custom event to trigger a dynamic search update.
         *
         * @param {Object} tag        The tag object with id and rawname properties
         */
        function addTag(tag) {
            // Enforce maximum tag limit if configured.
            if (maxTags > 0 && selectedTags.length >= maxTags) {
                return;
            }
            // Prevent duplicate selection.
            if (selectedTags.find(function(t) {
                return t.id === tag.id;
            })) {
                return;
            }

            selectedTags.push(tag);

            // Hide popular tag suggestions as soon as the first tag is selected.
            var suggestionsBlock = document.getElementById('popular-tags-suggestions');
            if (suggestionsBlock) {
                suggestionsBlock.style.display = 'none';
            }

            // Hide the search input and show the limit message when the limit is reached.
            if (maxTags > 0 && selectedTags.length >= maxTags) {
                searchInput.style.display = 'none';
                limitMsg.style.display = '';
            }

            // Create a hidden input so the tag is included in form submissions.
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'tags[]';
            hidden.value = parseInt(tag.id, 10);
            hiddenContainer.appendChild(hidden);

            // Create the visible pill badge with an accessible remove button.
            var pill = document.createElement('span');
            pill.textContent = tag.rawname;
            pill.className = 'badge bg-secondary d-inline-flex align-items-center me-1';
            pill.style.fontSize = '1rem';

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = '×';
            btn.setAttribute('aria-label', removeString.replace('{$a}', tag.rawname));
            btn.className = 'btn btn-sm btn-link p-0 ms-1';
            btn.style.color = 'rgba(0,0,0,0.6)';
            btn.addEventListener('click', function() {
                removeTag(tag.id, pill, hidden);
            });
            pill.appendChild(btn);
            selectedContainer.appendChild(pill);

            // Notify the dynamic search module that the tag selection has changed.
            container.dispatchEvent(new CustomEvent('tagchange'));
        }

        /**
         * Removes a tag from the selection, restores the search input if below the limit,
         * and dispatches a 'tagchange' event to trigger a dynamic search update.
         *
         * @param {number}      tagId  The ID of the tag to remove
         * @param {HTMLElement} pill   The pill badge element to remove from the DOM
         * @param {HTMLElement} hidden The hidden input element to remove from the DOM
         */
        function removeTag(tagId, pill, hidden) {
            selectedTags = selectedTags.filter(function(t) {
                return t.id !== tagId;
            });
            pill.remove();
            hidden.remove();

            // Restore popular tag suggestions when all tags have been removed.
            var suggestionsBlock = document.getElementById('popular-tags-suggestions');
            if (suggestionsBlock && selectedTags.length === 0) {
                suggestionsBlock.style.display = '';
            }

            // Restore the search input if the tag count is now below the limit.
            searchInput.style.display = '';
            if (maxTags > 0 && selectedTags.length < maxTags) {
                limitMsg.style.display = 'none';
            }

            // Notify the dynamic search module that the tag selection has changed.
            container.dispatchEvent(new CustomEvent('tagchange'));
        }

        /**
         * Returns the IDs of all currently selected tags as an array of integers.
         *
         * @return {Array} Array of selected tag IDs
         */
        function getSelectedTagIds() {
            return selectedTags.map(function(t) {
                return parseInt(t.id, 10);
            });
        }

        // Wire up popular tag suggestion buttons.
        // Each button adds the corresponding tag as if the user had selected it from the dropdown.
        document.querySelectorAll('[data-popular-tagid]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tagId = parseInt(btn.dataset.popularTagid, 10);
                var tag = allTags.find(function(t) {
                    return parseInt(t.id, 10) === tagId;
                });
                if (tag) {
                    addTag(tag);
                }
            });
        });

        return {getSelectedTagIds: getSelectedTagIds};
    }

    /**
     * Initialises the dynamic search system.
     * Listens for filter changes and triggers AJAX searches without page reload.
     * Handles results rendering, pagination, and sort button state.
     *
     * @param {Object} tagSelector The tag selector instance exposing getSelectedTagIds()
     */
    function initDynamicSearch(tagSelector) {
        // Container element where search results are rendered.
        var resultsContainer = document.getElementById('catalog-results');

        // The search form element, identified by its aria-label attribute.
        var form = document.querySelector('form[aria-label]');

        if (!resultsContainer || !form) {
            return;
        }

        // Number of results to display per page, read from a data attribute.
        var perpage = parseInt(resultsContainer.dataset.perpage, 10) || 10;

        // Localised result count string with __COUNT__ placeholder, injected via data attribute.
        var resultsString = resultsContainer.dataset.resultsString || '__COUNT__ result(s)';

        // Localised no-results message, injected via data attribute.
        var noresultsString = resultsContainer.dataset.noresultsString || 'No results found.';

        // Currently displayed page index (0-based).
        var currentPage = 0;

        // Currently active sort key ('az', 'recent', or 'moodle').
        var currentSort = 'az';

        /**
         * Collects all active filter values from the form.
         * Excludes the course title and category fields, which are handled separately.
         *
         * @return {Object} Key-value map of filter field names to their current values
         */
        function collectFilters() {
            var filters = {};
            form.querySelectorAll('input[data-fieldname], select, input[type="number"], input[type="text"]').forEach(function(el) {
                if (el.name && el.name !== 'coursetitle' && el.id !== 'tag-search' && el.type !== 'hidden') {
                    filters[el.name] = el.value;
                }
            });
            return filters;
        }

        /**
         * Builds the HTML string for a single course result card.
         * Displays the course image (or a placeholder icon), title, tags, and summary.
         *
         * @param  {Object} course Course data object returned by the AJAX endpoint
         * @return {string}        HTML string for the course card
         */
        function buildCourseCard(course) {
            // Use the course overview image if available, otherwise show a placeholder icon.
            var imgHtml = course.imgurl
                ? '<div class="flex-shrink-0 rounded overflow-hidden" style="width:140px;height:105px;">' +
                '<img src="' + course.imgurl + '" alt="' + course.fullname + '"' +
                ' style="width:100%;height:100%;object-fit:cover;display:block;"></div>'
                : '<div class="rounded flex-shrink-0 bg-light d-flex align-items-center' +
                ' justify-content-center" style="width:140px;height:105px;" role="presentation">' +
                '<i class="fa fa-graduation-cap text-muted fa-2x"></i></div>';

            // Build badge HTML for each tag associated with the course.
            var tagsHtml = '';
            if (course.tags) {
                course.tags.split(', ').forEach(function(tag) {
                    if (tag) {
                        tagsHtml += '<span class="badge bg-secondary me-1" style="font-size:0.85rem;">' +
                            tag + '</span>';
                    }
                });
            }

            return '<div class="list-group-item list-group-item-action p-3 h-auto">' +
                '<div class="d-flex align-items-start">' +
                imgHtml +
                '<div class="flex-grow-1" style="padding-left:1.5rem;">' +
                '<a href="' + course.url + '" class="h4 mb-1 d-block text-decoration-none">' + course.fullname + '</a>' +
                '<div class="mb-2" aria-label="Tags">' + tagsHtml + '</div>' +
                '<p class="mb-0" style="font-size:0.95rem;">' + course.summary + '</p>' +
                '</div></div></div>';
        }

        /**
         * Builds the Bootstrap pagination HTML for navigating between result pages.
         * Returns an empty string if there is only one page.
         *
         * @param  {number} totalpages   Total number of pages
         * @param  {number} currentpage  Currently active page index (0-based)
         * @return {string}              HTML string for the pagination nav
         */
        function buildPagination(totalpages, currentpage) {
            if (totalpages <= 1) {
                return '';
            }
            var html = '<nav aria-label="Pagination"><ul class="pagination">';
            for (var i = 0; i < totalpages; i++) {
                var isActive = i === currentpage;
                var ariaCurrent = isActive ? ' aria-current="page"' : '';
                var activeClass = isActive ? ' active' : '';
                html += '<li class="page-item' + activeClass + '">' +
                '<a class="page-link" href="#" data-page="' + i + '"' + ariaCurrent + '>' +
                (i + 1) + '</a></li>';
            }
            html += '</ul></nav>';
            return html;
        }

        /**
         * Executes an AJAX search with the current filter state and renders the results.
         * Optionally scrolls to the sort bar when navigating between pages.
         *
         * @param {number}  page         The page index to fetch (0-based)
         */
        function doSearch(page) {
            currentPage = page || 0;

            // Collect current filter values from the form.
            var filters = collectFilters();
            var filtertitle = form.querySelector('#coursetitle') ? form.querySelector('#coursetitle').value : '';
            var categoryel = form.querySelector('#categoryid');
            var selectedcategory = categoryel ? parseInt(categoryel.value, 10) : 0;
            var selectedtags = tagSelector ? tagSelector.getSelectedTagIds() : [];
            delete filters.categoryid;

            Ajax.call([{
                methodname: 'local_catalog_browser_search_courses',
                args: {
                    filters:          JSON.stringify(filters),
                    filtertitle:      filtertitle,
                    selectedtags:     JSON.stringify(selectedtags),
                    sort:             currentSort,
                    selectedcategory: selectedcategory,
                    page:             currentPage,
                    perpage:          perpage,
                },
                done: function(data) {

                    // Build the result count line — must be after the onlyAccessible filter
                    // so that the count reflects the filtered results.
                    var html = '<p class="text-muted">' + resultsString.replace('__COUNT__', data.totalcount) + '</p>';

                    if (data.courses.length > 0) {
                        // Render each course as a card inside a list group.
                        html += '<div class="list-group mb-3">';
                        data.courses.forEach(function(course) {
                            html += buildCourseCard(course);
                        });
                        html += '</div>';
                    } else {
                        // Show the no-results message when the query returns nothing.
                        html += '<div class="alert alert-info">' + noresultsString + '</div>';
                    }

                    html += buildPagination(data.totalpages, data.page);

                    // Build the new content in a detached element first, then swap in a single
                    // synchronous operation to avoid any visible flash during DOM replacement.
                    var temp = document.createElement('div');
                    temp.innerHTML = html;
                    resultsContainer.replaceChildren(...temp.childNodes);

                    // Update the browser URL with the current filter state.
                    var params = new URLSearchParams();
                    var filters = collectFilters();
                    Object.keys(filters).forEach(function(key) {
                        if (filters[key] !== '') {
                            params.set(key, filters[key]);
                        }
                    });
                    var coursetitle = form.querySelector('#coursetitle');
                    if (coursetitle && coursetitle.value) {
                        params.set('coursetitle', coursetitle.value);
                    }
                    var categoryel = form.querySelector('#categoryid');
                    if (categoryel && categoryel.value !== '0') {
                        params.set('categoryid', categoryel.value);
                    }
                    if (currentSort !== 'az') {
                        params.set('sort', currentSort);
                    }
                    if (currentPage > 0) {
                        params.set('page', currentPage);
                    }
                    if (tagSelector) {
                        tagSelector.getSelectedTagIds().forEach(function(id) {
                            params.append('tags[]', id);
                        });
                    }
                    var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                    history.pushState(null, '', newUrl);

                    // Re-attach click handlers to the newly rendered pagination links.
                    resultsContainer.querySelectorAll('[data-page]').forEach(function(link) {
                        link.addEventListener('click', function() {
                            doSearch(parseInt(link.dataset.page, 10));
                        });
                    });

                    // Update sort button styles to reflect the currently active sort.
                    document.querySelectorAll('a[href*="sort="]').forEach(function(link) {
                        var match = link.href.match(/sort=([a-z]+)/);
                        if (match) {
                            if (match[1] === currentSort) {
                                link.classList.remove('btn-outline-secondary');
                                link.classList.add('btn-secondary');
                            } else {
                                link.classList.remove('btn-secondary');
                                link.classList.add('btn-outline-secondary');
                            }
                        }
                    });
                },
                fail: function(error) {
                    console.error('Search error:', error); // eslint-disable-line no-console
                }
            }]);
        }

        // Trigger a new search from page 0 whenever a select or number input changes.
        form.querySelectorAll('select, input[type="number"]').forEach(function(el) {
            el.addEventListener('change', function() {
                doSearch(0);
            });
        });

        // Trigger a debounced search on input for text fields without autocomplete (e.g. textarea fields).
        form.querySelectorAll('input[type="text"]').forEach(function(el) {
            if (el.id !== 'tag-search' && !el.dataset.fieldname) {
                var debounce;
                el.addEventListener('input', function() {
                    clearTimeout(debounce);
                    debounce = setTimeout(function() {
                        doSearch(0);
                    }, 500);
                });
            }
        });

        // Trigger a new search when a text input (other than the tag search) loses focus.
        form.querySelectorAll('input[type="text"]').forEach(function(el) {
            if (el.id !== 'tag-search') {
                el.addEventListener('change', function() {
                    doSearch(0);
                });
            }
        });

        // Also trigger a new search when the user presses Enter in any text input
        // (except the tag search, where Enter is reserved for selecting a suggestion).
        form.querySelectorAll('input[type="text"]').forEach(function(el) {
            if (el.id !== 'tag-search') {
                el.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        doSearch(0);
                    }
                });
            }
        });

        // Trigger a new search whenever the tag selection changes.
        var tagContainer = document.getElementById('tag-selector');
        if (tagContainer) {
            tagContainer.addEventListener('tagchange', function() {
                doSearch(0);
            });
        }

        // Handle sort link clicks: update currentSort and re-run the search from page 0.
        document.querySelectorAll('a[href*="sort="]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var match = link.href.match(/sort=([a-z]+)/);
                if (match) {
                    currentSort = match[1];
                    doSearch(0);
                }
            });
        });

        // Intercept pagination links rendered by the Mustache template on first load
        // so they trigger AJAX navigation instead of a full page reload.
        document.querySelectorAll('.pagination .page-link[href]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var match = link.href.match(/page=(\d+)/);
                if (match) {
                    doSearch(parseInt(match[1], 10));
                }
            });
        });
    }

    return {
        /**
         * Entry point called by Moodle via $PAGE->requires->js_call_amd().
         * Initialises autocomplete on all custom field inputs, the tag selector,
         * and the dynamic search system.
         */
        init: function() {
            // Attach autocomplete to every custom field text input.
            document.querySelectorAll('input[data-fieldname]').forEach(function(input) {
                attachAutocomplete(input, input.dataset.fieldname);
            });

            // Initialise the tag selector and pass it to the dynamic search module.
            var tagSelector = initTagSelector();
            initDynamicSearch(tagSelector);
        }
    };
});