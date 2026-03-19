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
M.availability_enrol_dates = M.availability_enrol_dates || {};

M.availability_enrol_dates.form = Y.Object(M.core_availability.plugin);

M.availability_enrol_dates.form.initInner = function(param) {
    // The 'param' variable is the parameter passed through from PHP (you
    // can have more than one if required).

    // Using the PHP code above it'll show 'The param was: frog'.
    // console.log('The param was: ' + param);
};

M.availability_enrol_dates.form.getNode = function(json) {
    // This function does the main work. It gets called after the user
    // chooses to add an availability restriction of this type. You have
    // to return a YUI node representing the HTML for the plugin controls.

    // Example controls contain only one tickbox.
    var html = '<label>'
        + M.get_string('title', 'availability_enrol_dates')
        + ' <input type="checkbox"/></label>';
    var node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values based on the value from the JSON data in Moodle
    // database. This will have values undefined if creating a new one.
    if (json.allow) {
        node.one('input').set('checked', true);
    }

    // Add event handlers (first time only). You can do this any way you
    // like, but this pattern is used by the existing code.
    if (!M.availability_enrol_dates.form.addedEvents) {
        M.availability_enrol_dates.form.addedEvents = true;
        var root = Y.one('#fitem_id_availabilityconditionsjson');
        root.delegate('click', function() {
            // The key point is this update call. This call will update
            // the JSON data in the hidden field in the form, so that it
            // includes the new value of the checkbox.
            M.core_availability.form.update();
        }, '.availability_enrol_dates input');
    }

    return node;
};

M.availability_enrol_dates.form.fillValue = function(value, node) {
    // This function gets passed the node (from above) and a value
    // object. Within that object, it must set up the correct values
    // to use within the JSON data in the form. Should be compatible
    // with the structure used in the __construct and save functions
    // within condition.php.
    var checkbox = node.one('input');
    value.allow = checkbox.get('checked') ? true : false;
};

M.availability_enrol_dates.form.fillErrors = function(errors, node) {
    // If the user has selected something invalid, this optional
    // function can be included to report an error in the form. The
    // error will show immediately as a 'Please set' tag, and if the
    // user saves the form with an error still in place, they'll see
    // the actual error text.

    // In this example an error is not possible...
    // if (false) {
    //     // ...but this is how you would add one if required. This is
    //     // passing your component name (availability_enrol_dates) and the
    //     // name of a string within your lang file (error_message)
    //     // which will be shown if they submit the form.
    //     errors.push('availability_enrol_dates:error_message');
    // }
};