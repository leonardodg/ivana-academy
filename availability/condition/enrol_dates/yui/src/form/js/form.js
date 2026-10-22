/**
 * JavaScript for form editing enrol dates conditions.
 *
 * @module moodle-availability_enrol_dates-form
 */
M.availability_enrol_dates = M.availability_enrol_dates || {};

/**
 * @class M.availability_enrol_dates.form
 * @extends M.core_availability.plugin
 */
M.availability_enrol_dates.form = Y.Object(M.core_availability.plugin);

M.availability_enrol_dates.form.initInner = function() {
};

/**
 * Updates the direction select options based on the selected timevaluecheck value.
 * start/end dates only allow 'after', so 'before' is disabled accordingly.
 */
M.availability_enrol_dates.form.updateDirectionOptions = function(node) {
    var timevaluecheck = node.one('select[name=timevaluecheck]').get('value');
    var directionSelect = node.one('select[name=direction]');
    var beforeOption = directionSelect.one('option[value=before]');
    var afterOption = directionSelect.one('option[value=after]');

    if (timevaluecheck === 'coursetimestart' || timevaluecheck === 'enroltimestart') {
        beforeOption.set('disabled', true);
        afterOption.set('disabled', false);
        directionSelect.set('value', 'after');
    } else if (timevaluecheck === 'coursetimeend' || timevaluecheck === 'enroltimeend') {
        beforeOption.set('disabled', false);
        afterOption.set('disabled', true);
        directionSelect.set('value', 'before');
    } else {
         beforeOption.set('disabled', false);
        afterOption.set('disabled', false);
        directionSelect.set('value', '');
    }
};

M.availability_enrol_dates.form.getNode = function(json) {
    var html = '<label><span class="pe-3">' + M.util.get_string('title', 'availability_enrol_dates') + '</span> ' +
            '<span class="availability_enrol_dates">' +
            '<select name="direction" class="custom-select">' +
            '<option value="">' + M.util.get_string('select_direction', 'availability_enrol_dates') + '</option>' +
            '<option value="before">' + M.util.get_string('before', 'availability_enrol_dates') + '</option>' +
            '<option value="after">' + M.util.get_string('after', 'availability_enrol_dates') + '</option>' +
            '</select> ' +
            '<select name="timevaluecheck" class="custom-select">' +
            '<option value="">' + M.util.get_string('select_timevaluecheck', 'availability_enrol_dates') + '</option>' +
            '<option value="coursetimestart">' + M.util.get_string('coursetimestart', 'availability_enrol_dates') + '</option>' +
            '<option value="coursetimeend">' + M.util.get_string('coursetimeend', 'availability_enrol_dates') + '</option>' +
            '<option value="enroltimestart">' + M.util.get_string('enroltimestart', 'availability_enrol_dates') + '</option>' +
            '<option value="enroltimeend">' + M.util.get_string('enroltimeend', 'availability_enrol_dates') + '</option>' +
            '</select> ' +
            '<input type="number" name="timenumber" min="0" max="99999" maxlength="5" value="0" /> ' +
            '<select name="timeperiod" class="custom-select">' +
            '<option value="">' + M.util.get_string('select_timeperiod', 'availability_enrol_dates') + '</option>' +
            '<option value="hours">' + M.util.get_string('hours', 'availability_enrol_dates') + '</option>' +
            '<option value="days">' + M.util.get_string('days', 'availability_enrol_dates') + '</option>' +
            '<option value="months">' + M.util.get_string('months', 'availability_enrol_dates') + '</option>' +
            '</select>' +
            '</span></label>';

    var node = Y.Node.create('<span class="d-flex flex-wrap align-items-center">' + html + '</span>');

    if (json.direction !== undefined) {
        node.one('select[name=direction]').set('value', json.direction);
    }
    if (json.timevaluecheck !== undefined) {
        node.one('select[name=timevaluecheck]').set('value', json.timevaluecheck);
    }
    if (json.timenumber !== undefined) {
        node.one('input[name=timenumber]').set('value', json.timenumber);
    }
    if (json.timeperiod !== undefined) {
        node.one('select[name=timeperiod]').set('value', json.timeperiod);
    }

    // Apply direction restrictions for the initial timevaluecheck value.
    M.availability_enrol_dates.form.updateDirectionOptions(node);

    // Attach change events directly on the node.
    node.delegate('change', function(e) {
        if (e.target.get('name') === 'timevaluecheck') {
            M.availability_enrol_dates.form.updateDirectionOptions(node);
        }
        M.core_availability.form.update();
    }, 'select, input');

    return node;
};

M.availability_enrol_dates.form.fillValue = function(value, node) {
    value.direction = node.one('select[name=direction]').get('value');
    value.timevaluecheck = node.one('select[name=timevaluecheck]').get('value');
    value.timenumber = parseInt(node.one('input[name=timenumber]').get('value'), 10);
    value.timeperiod = node.one('select[name=timeperiod]').get('value');
};

M.availability_enrol_dates.form.fillErrors = function(errors, node) {
    if (!node.one('select[name=direction]').get('value')) {
        errors.push('availability_enrol_dates:error_missing_direction');
    }
    if (!node.one('select[name=timevaluecheck]').get('value')) {
        errors.push('availability_enrol_dates:error_missing_timevaluecheck');
    }
    if (!node.one('select[name=timeperiod]').get('value')) {
        errors.push('availability_enrol_dates:error_missing_timeperiod');
    }
    var timenumber = parseInt(node.one('input[name=timenumber]').get('value'), 10);
    if (isNaN(timenumber) || timenumber < 0) {
        errors.push('availability_enrol_dates:error_invalid_timenumber');
    }
};
