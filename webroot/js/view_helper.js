

'use strict';

class ViewHelper {

    static months(i) {
        let months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];
        if (typeof i != "undefined" && i >= 0 && i <= 11) return months[i];
        return months;
    }

    constructor(result = '', collection = []) {
        this._result = result;  // the resulting html string
        this._collection = collection;  // collection of html string items to wrap in a container
    }

    reset() {
        this._result = '';
        this._collection = [];
    }

    copy() {
        return new ViewHelper(this._result, this._collection);
    }

    get result() {
        let result = this._result;
        this.reset();
        return result;
    }


    static createLink(href, text, external = true) {
        text = text || href;
        if (text == href && text.length > 60)
            text = text.substr(0, 60) + '...';
        let result = $('<a></a>').attr('href', href).html(text);
        if (external) result.attr('target', '_blank');
        return result[0].outerHTML;
    }

    createLink(href, text, external) {
        this._result = ViewHelper.createLink(href, text, external);
        return this;
    }


    static createTermData(term, data, key) {
        let empty = '-';
        let value = ViewHelper.getValue(data, key);
        if (value === false) value = empty;
        if (key == 'updated') {
            let date = new Date(data.updated);
            value = date.toISOString();
            let timePos = value.indexOf("T");
            value = value.slice(0, timePos);
        }
        if (key == 'contact_mail') {
            value = value.replace("@", " /at/ ");
        }
        if (key == 'info_url') {
            value = ViewHelper.createLink(value);
        }
        if (key == 'online_course')
            value = (data.online_course) ? 'online' : 'campus';
        let result = '<p class="term">' + term + '</p>';
        result += '<p class="data">' + value + '</p>';
        return result;
    }

    static getValue(data, key) {
        let empty = false;
        let split = key.split('.');
        let value = data[key] || empty;
        if (split.length == 2) value = data[split[0]][split[1]] || empty;
        if (typeof value == 'string' && value.match(/^null$|^nan$/i)) value = empty;
        return value;
    }

    createTermData(term, data, key) {
        this._result = ViewHelper.createTermData(term, data, key);
        return this;
    }


    static createGridItem(content, classes = '') {
        classes = (classes == '') ? 'item' : 'item ' + classes;
        let result = $('<div></div>').addClass(classes).html(content);
        return result[0].outerHTML;
    }

    createGridItem(classes) {
        this._collection.push(ViewHelper.createGridItem(this._result, classes));
        this._result = '';
        return this;
    }


    static createGridContainer(items = [], classes = '') {
        classes = (classes == '') ? 'grid-container' : 'grid-container ' + classes;
        let result = '<div class="' + classes + '">';
        for (let i = 0; i < items.length; i++) result += items[i];
        result += '</div>';
        return result;
    }

    createGridContainer(classes) {
        this._result = ViewHelper.createGridContainer(this._collection, classes);
        this._collection = [];
        return this;
    }


    static getTiming(course, dateSeparator = ', ', recurringSeparator = ', ', durationSeparator = '<br />', verbose = false) {
        let result = '';
        if (course.start_date) {
            let split = course.start_date.split(/[;,]/);
            let lastMonth;
            let start = '';
            for (let i = 0; split.length > i; i++) {
                let date = new Date(split[i].trim())
                if ((!course.recurring && date < new Date()) || (course.recurring && lastMonth == date.getMonth())) continue;     // omit past dates except for recurring starts
                lastMonth = date.getMonth()
                if (start != '') start += dateSeparator;
                // Do not use JS Date object since this uses the timezone of the browser.
                // This might cause showing incorrect dates, see issue #113
                let dateAsText = split[i].trim();
                let dateAsTextParts = dateAsText.split("-");
                let day = parseInt(dateAsTextParts[2]);
                let month = parseInt(dateAsTextParts[1]);
                start += day + ' ' + ViewHelper.months()[month-1];
                if (!course.recurring) start += ' ' + date.getFullYear();
            }
            if (verbose && start.length > 0 && course.recurring) start = 'each ' + start;
            result = start;
        }
        if (course.recurring) {
            if (result != '') result += recurringSeparator;
            result += '<span class="recurring">recurring</span>';
        }
        if (course.duration) {
            if (result != '') result += durationSeparator;
            result += course.duration + ' ' + course.course_duration_unit.name;
        }
        return result;
    }

    createPopup(course) {
        let details = $('<a></a>').text('Details')
            .attr('data-id', course.id)
            .attr('href', BASE_URL + 'courses/view/' + course.id)
            .addClass('show_view button x-small');
        let buttons = $('<div></div>').addClass('buttons');
        this._collection = [];
        this._result = '';
        this._collection.push($('<h1></h1>').text(course.name));
        this._collection.push($('<p></p>').html(course.institution.name + ',<br />' + course.department + '.'));
        this._collection.push($('<p></p>').text('Type: ' + course.course_type.name));
        buttons.append(details);
        buttons.append(Sharing.createSharingButton('x-small', course));
        buttons.append($('<button></button>').text('Table').addClass('show_table x-small back'));
        this._collection.push(buttons);
        return this.concat();
    }

    static createExpansionRow(course, colspan) {
        let content = $('<td></td>').attr('colspan', colspan);
        let share = Sharing.createSharingButton('blue x-small', course);
        let onMap = $('<button></button>').text('Map')
            .addClass('show_map x-small blue')
            .attr('data-id', course.id);
        let details = $('<a></a>').text('Details')
            .addClass('show_view button x-small blue')
            .attr('data-id', course.id)
            .attr('href', BASE_URL + 'courses/view/' + course.id);
        content.append(details, share, onMap);
        let expansionRow = $('<tr></tr>').addClass('expansion-row').append(content);
        return expansionRow;
    }

    static concat(collection) {
        let retval = '';
        collection.forEach(function (node) {
            if (node instanceof jQuery) retval += node.prop('outerHTML');
            if (typeof node == 'string') retval += node;
            if (typeof node.outerHTML === 'string') retval += node.outerHTML;
        });
        return retval;
    }

    concat() {
        this._result = ViewHelper.concat(this._collection);
        return this._result;
    }
}
