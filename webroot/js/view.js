
'use strict';

class View {

    constructor(elm, app) {
        // store the native DOM element, not jQuery
        this.element = elm;
        this.app = app;

        this.addHandlers();
    }

    addHandlers() {
        // table row expansion
        $(document).on('click', '#table .course-row', function (e) {
            let targetRow = $(e.target).closest('.course-row');
            let id = targetRow.attr('data-id');

            if (this.toggleRow(id)) {
                this.app.map.openMarker(id);
                this.app.hash.push(id);
            } else {
                this.app.map.closeMarker();
                this.app.hash.remove();
            }
        }.bind(this));

        // table row expansion handlers
        $(document).on('click', '#table .show_view', function (e) {
            e.preventDefault();
            let id = $(e.target).attr('data-id');
            this.app.setCourse(id);
            this.app.map.openMarker(id);
            // this differs from map show_view action on mobiles -> definition in Map.addHandlers
        }.bind(this));
        $(document).on('click', '#table .show_map', function (e) {
            let id = $(e.target).attr('data-id');
            this.app.slider.setPosition('map');
            this.app.map.openMarker(id);    // should already be open
        }.bind(this));

        // close view
        $(document).on('click', '.close_view', function (e) {
            e.preventDefault();
            this.closeView();
        }.bind(this));

        $(document).on('click', '.show_sort_options', function (e) {
            this.app.filter.helper.createSortModal();
        }.bind(this));

        $(document).on('click', '.show_filter_options', function (e) {
            this.app.filter.helper.createFilterModal();
        }.bind(this));

        $(document).on('click', '.home_button', function (e) {
            $(location).attr('href', '/');
        }.bind(this));
    }

    createFilterPanel() {
        let buttons = $('<div id="filter-buttons"></div>');
        buttons.append($('<button><span class="glyphicon glyphicon-home"></span></button>').addClass('blue x-small home_button'));
        buttons.append($('<button>Filter</button>').addClass('blue x-small show_filter_options'));
        buttons.append($('<button>Sort</button>').addClass('blue x-small show_sort_options'));
        if (!this.app.filter.isEmpty() || this.app.filter.selected.sort.length > 0) {
            buttons.append($('<a>Clear All</a>').addClass('x-small blue clear button')
                .attr('href', BASE_URL).attr('id', 'reset'));
        }
        buttons.append('&nbsp;&nbsp;&nbsp;');
        buttons.append('<input type="text" id="searchField" placeholder="Find a course directly by name or university" class="typeahead tt-query" autocomplete="off" spellcheck="false">');
        $(this.element).append(buttons);
        $(document).ready(function () {
            var courses = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.whitespace,
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: 'search_list.json'
            });
            $('.typeahead').typeahead(
                {
                    hint: false,
                    highlight: true,    /* Enable substring highlighting */
                    minLength: 1        /* Specify minimum characters required for showing result */
                }, {
                name: 'courses',
                source: courses,
                limit: 15               /* Specify maximum number of suggestions to be displayed */
            });
            $('#searchField').on('typeahead:selected', function (e, searchKey) {
                var courseTypePos = searchKey.lastIndexOf("  -  ");
                var courseType = searchKey.slice(courseTypePos + 4).trim();
                var remainingKey = searchKey.slice(0, courseTypePos).trim();
                var institutionPos = remainingKey.lastIndexOf("  -  ");
                var institutionName = remainingKey.slice(institutionPos + 4).trim();
                var courseName = remainingKey.slice(0, institutionPos).trim();

                window.location.href = "/courses/find/" + encodeURIComponent(courseName) + '/' + encodeURIComponent(institutionName) + '/' + encodeURIComponent(courseType);
            });
            $('#searchField').focus();
        });
    }

    createTable() {
        this.clearView();
        this.createFilterPanel();
        if (Object.keys(this.app.data) <= 0) {
            // do not clear View/call handleError to keep the filter buttons clear button available
            $(this.element).addClass('error').append('<p>No courses match your filter conditions.</p>');
            return;
        }

        // create table
        let table = $('<table></table>');
        let headrow = $('<tr></tr>');
        table.append(headrow);

        let name = $('<th class="name show_sort_options"></th>');
        name.html('Name' + this.app.filter.helper.getSortIndicator('Courses.name'));
        let uni = $('<th class="university show_sort_options"></th>');
        uni.html('University' + this.app.filter.helper.getSortIndicator('Institutions.name'));
        let loc = $('<th class="location show_sort_options"></th>');
        loc.html('Place' + this.app.filter.helper.getSortIndicator('Countries.name'));
        let date = $('<th class="period show_sort_options"></th>');
        date.html('Date' + this.app.filter.helper.getSortIndicator('Courses.start_date'));
        let type = $('<th class="type show_sort_options"></th>');
        type.html('Type' + this.app.filter.helper.getSortIndicator('CourseTypes.name'));

        headrow.append(name, uni, loc, date, type);

        for (let i = 0; courses.length > i; i++) {
            let id = courses[i].id;
            let row = this.createTableRow(this.app.data[id]);
            if (row) table.append(row);
        }
        $(this.element).append(table);
        if (this.app.hash.fragment && !isNaN(this.app.hash.fragment)) {
            let id = this.app.hash.fragment;
            if (this.openRow(id)) {
                this.scrollToRow(id);
                // we need a timeout to wait until map has stopped moving (and closing open popups)
                setTimeout(function () {
                    this.app.map.openMarker(id);
                }.bind(this), 1500);
            }
        }
    }

    scrollToRow(id) {
        let row = document.getElementById('course-row-' + id);
        if (typeof row != 'undefined' && row)
            $(this.element).animate({ scrollTop: row.offsetTop }, 1000);
    }

    toggleRow(id) {
        $('.expansion-row').remove();
        if (typeof id != 'undefined') {
            let targetRow = $('tr[data-id=' + id + ']');
            // close all others
            $('.expanded[data-id!=' + id + ']').removeClass('expanded');
            targetRow.toggleClass('expanded');
            if (targetRow.hasClass('expanded')) {
                // opening
                let course = this.app.data[id];
                let colspan = (this.app.layout == 'screen') ? 5 : 4;
                let expansion = ViewHelper.createExpansionRow(course, colspan);
                targetRow.after(expansion);     // insert
                return true;    // wheter or not to open popups on map
            }
        } else {
            // close all
            $('.expanded').removeClass('expanded');
        }
        return false;   // no popups
    }

    openRow(id) {
        if (typeof id != 'undefined' && id > 0 && !isNaN(id)) {
            let targetRow = $('tr[data-id=' + id + ']');
            if (!targetRow.hasClass('expanded')) {
                // remove others
                $('.expansion-row').remove();
                $('.expanded').removeClass('expanded');
                // opening
                targetRow.addClass('expanded');
                let course = this.app.data[id];
                let colspan = (this.app.layout == 'mobile') ? 4 : 5;
                let expansion = ViewHelper.createExpansionRow(course, colspan);
                targetRow.after(expansion);
            }
            return true;
        }
        return false;
    }

    createTableRow(course) {
        let tr = $('<tr></tr>').addClass('course-row').attr('data-id', course.id).attr('id', 'course-row-' + course.id);
        let duration = ViewHelper.getTiming(course, ',<br />', ' ', '<br />');
        if (typeof course.course_type == 'undefined') {
            return false;
        }
        let type = course.course_type.name;
        if (course.online) type += ' <span class="online">online</span>';
        let limit = 59;
        let courseName = (course.name.length <= limit + 4)
            ? course.name
            : course.name.substr(0, limit) + ' ...';
        tr.append(
            $('<td class="name">' + courseName + '</td>'),
            $('<td class="university">' + course.institution.name + '</td>'),
            $('<td class="location">' + course.city.name + ',<br />' + course.country.name + '</td>'),
            $('<td class="period">' + duration + '</td>'),
            $('<td class="type">' + type + '</td>')
        );
        return tr[0];
    }

    createView(course) {
        let el = $('<div id="view"></div>');
        let helper = new ViewHelper();
        let timing = ViewHelper.getTiming(course, ', ', ', ', '<br />', true);

        let backActionLabel = (this.app.action == 'view') ? 'Go to Start' : 'Back to List';
        let back = $('<a class="back button small close_view" href="' + BASE_URL + '">' + backActionLabel + '</a>');
        let share = Sharing.createSharingButton('blue small', course);
        el.append($('<div class="buttons"></div>').append(back, share));

        el.append($('<h1>' + course.name + '</h1>'));
        el.append($('<p class="subtitle">' + course.course_type.name + ', ' + timing + '</p>'));
        if (course.description != null && course.description.length > 0)
            el.append($('<div class="text"><p class="strong">Description</p>' + course.description + '</div>'));
        if (course.access_requirements.length > 0)
            el.append($('<div class="text"><p class="strong">Access Requirements</p>' + course.access_requirements + '</div>'));

        el.append($('<hr />'));

        helper.createTermData('Country', course, 'country.name').createGridItem();
        helper.createTermData('City', course, 'city.name').createGridItem();

        helper.createTermData('University', course, 'institution.name').createGridItem();
        helper.createTermData('Department', course, 'department').createGridItem();

        helper.createTermData('Lecturer', course, 'contact_name').createGridItem();
        helper.createTermData('Credits (ECTS)', course, 'ects').createGridItem();

        helper.createTermData('Contact', course, 'contact_mail').createGridItem();
        helper.createTermData('Presence', course, 'online_course').createGridItem();

        helper.createTermData('Language', course, 'language.name').createGridItem();
        helper.createTermData('Last Revised', course, 'updated').createGridItem();

        helper.createTermData('Record Id', course, 'id').createGridItem();

        if (course.info_url.length > 0 && course.info_url != 'null') {
            helper.createTermData('Course URL', course, 'info_url').createGridItem('single-col');
        }

        el.append($(helper.createGridContainer().result));

        // locationMap is a second map only shown on mobile devices
        let location = $('<div id="locationMap"></div>');
        el.append(location);

        el.append($('<hr />'));

        this.clearView();
        $(this.element).append(el);

        this.app.scrollable.top();
        //if(this.app.action == 'index') this.app.hash.push(course.id);

        // init mobile auxiliary map after adding it to document
        let map = new Map({
            htmlIdentifier: 'locationMap',
            apiKey: this.app.mapApiKey,
            scrollWheelZoom: false,
            app: this.app,
            popups: false
        });
        let id = course.id;
        map.setMarkers({ id: course });
        map.map.setView([course.lat, course.lon], 5);
        map.map.on('click', function () { map.map.scrollWheelZoom.enable(); });
        map.map.on('focus', function () { map.map.scrollWheelZoom.enable(); });
        map.map.on('mouseout', function () { map.map.scrollWheelZoom.disable() });
        map.map.on('blur', function () { map.map.scrollWheelZoom.disable() });
    }

    setErrorMessage(msg) {
        msg = msg || 'Something went wrong';
        this.clearView();
        $(this.element).text(msg);
    }

    setLoader() {
        this.clearView();
        $(this.element).addClass('loading').text('loading...');
    }

    clearView() {
        $(this.element).empty().removeClass('loading').removeClass('error');
        this.app.scrollable.disable();
    }

    closeView() {
        if (this.app.action == 'index') {
            this.app.setTable();
        }
        if (this.app.action == 'view') {
            // reload
            window.location = BASE_URL;
        }
    }


}
