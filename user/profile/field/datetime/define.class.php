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
 * This file contains the datetime profile field definition class.
 *
 * @package profilefield_datetime
 * @copyright 2010 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/**
 * Define datetime fields.
 *
 * @copyright 2010 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class profile_define_datetime extends profile_define_base {

    /**
     * Define the setting for a datetime custom field.
     *
     * @param moodleform $form the user form
     */
    public function define_form_specific($form) {
        // Get the current calendar in use - see MDL-18375.
        $calendartype = \core_calendar\type_factory::get_calendar_instance();

        // Create variables to store start and end.
        list($year, $month, $day) = explode('_', date('Y_m_d'));
        $currentdate = $calendartype->convert_from_gregorian($year, $month, $day);
        $currentyear = $currentdate['year'];

        $arryears = $calendartype->get_years();

        // Add elements.
        $form->addElement('select', 'param1', get_string('startyear', 'profilefield_datetime'), $arryears);
        $form->setType('param1', PARAM_INT);
        $form->setDefault('param1', $currentyear);

        $form->addElement('select', 'param2', get_string('endyear', 'profilefield_datetime'), $arryears);
        $form->setType('param2', PARAM_INT);
        $form->setDefault('param2', $currentyear);

        $form->addElement('checkbox', 'param3', get_string('wanttime', 'profilefield_datetime'));
        $form->setType('param3', PARAM_INT);

        // Add elements for data validation (param4).
        $elements = array();
        $elements[] =& $form->createElement('text', 'minimumdate', '', 'maxlength="5" size="5"');

        $options = array();
        $options[PROFILE_VALIDATION_MINUTES] = get_string('minimumdateminutes', 'profilefield_datetime');
        $options[PROFILE_VALIDATION_HOURS] = get_string('minimumdatehours', 'profilefield_datetime');
        $options[PROFILE_VALIDATION_DAYS] = get_string('minimumdatedays', 'profilefield_datetime');
        $options[PROFILE_VALIDATION_MONTHS] = get_string('minimumdatemonths', 'profilefield_datetime');
        $options[PROFILE_VALIDATION_YEARS] = get_string('minimumdateyears', 'profilefield_datetime');

        $elements[] =& $form->createElement('select', 'minimumdateperiod', '', $options);

        $elements[] =& $form->createElement('advcheckbox', 'minimumdateenabled', '',
                get_string('minimumdateenabled', 'profilefield_datetime'));

        $form->addGroup($elements, 'minimumdategroup',
                get_string('minimumdate', 'profilefield_datetime'), '&nbsp;&nbsp;', false);
        $form->addHelpButton('minimumdategroup', 'minimumdate', 'profilefield_datetime');

        $minimumdatecallback = function($value) {
            return !empty($value) && is_numeric($value) && $value > 0;
        };

        $form->addGroupRule('minimumdategroup',
                ['minimumdate' => [
                        [get_string('minimumdaterequired', 'profilefield_datetime'),
                        'callback', $minimumdatecallback, 'server']
                ]]);

        $form->setType('minimumdateenabled', PARAM_INT);
        $form->setType('minimumdate', PARAM_INT);
        $form->setDefault('minimumdateperiod', PROFILE_VALIDATION_YEARS);

        $form->addElement('text', 'minimumdateerror',
                get_string('minimumdateerror', 'profilefield_datetime'), 'maxlength="40" size="40"');
        $form->setType('minimumdateerror', PARAM_TEXT);

        $form->disabledIf('minimumdate', 'minimumdateenabled', 'notchecked');
        $form->disabledIf('minimumdateperiod', 'minimumdateenabled', 'notchecked');
        $form->disabledIf('minimumdateerror', 'minimumdateenabled', 'notchecked');

        $form->addElement('hidden', 'startday', '1');
        $form->setType('startday', PARAM_INT);
        $form->addElement('hidden', 'startmonth', '1');
        $form->setType('startmonth', PARAM_INT);
        $form->addElement('hidden', 'startyear', '1');
        $form->setType('startyear', PARAM_INT);
        $form->addElement('hidden', 'endday', '1');
        $form->setType('endday', PARAM_INT);
        $form->addElement('hidden', 'endmonth', '1');
        $form->setType('endmonth', PARAM_INT);
        $form->addElement('hidden', 'endyear', '1');
        $form->setType('endyear', PARAM_INT);
        $form->addElement('hidden', 'defaultdata', '0');
        $form->setType('defaultdata', PARAM_INT);
    }

    /**
     * Validate the data from the profile field form.
     *
     * @param stdClass $data from the add/edit profile field form
     * @param array $files
     * @return array associative array of error messages
     */
    public function define_validate_specific($data, $files) {
        $errors = array();

        // Make sure the start year is not greater than the end year.
        if ($data->param1 > $data->param2) {
            $errors['param1'] = get_string('startyearafterend', 'profilefield_datetime');
        }

        return $errors;
    }

    /**
     * Alter form based on submitted or existing data.
     *
     * @param moodleform $mform
     */
    public function define_after_data(&$mform) {
        global $DB;

        $this->define_after_data_param4($mform);

        // If we are adding a new profile field then the dates have already been set
        // by setDefault to the correct dates in the used calendar system. We only want
        // to execute the rest of the code when we have the years in the DB saved in
        // Gregorian that need converting to the date for this user.
        $id = optional_param('id', 0, PARAM_INT);
        if ($id === 0) {
            return;
        }

        // Get the field data from the DB.
        $field = $DB->get_record('user_info_field', array('id' => $id), 'param1, param2', MUST_EXIST);

        // Get the current calendar in use - see MDL-18375.
        $calendartype = \core_calendar\type_factory::get_calendar_instance();

        // An array to store form values.
        $values = array();

        // The start and end year will be set as a Gregorian year in the DB. We want
        // convert these to the equivalent year in the current calendar type being used.
        $startdate = $calendartype->convert_from_gregorian($field->param1, 1, 1);
        $values['startday'] = $startdate['day'];
        $values['startmonth'] = $startdate['month'];
        $values['startyear'] = $startdate['year'];
        $values['param1'] = $startdate['year'];

        $stopdate = $calendartype->convert_from_gregorian($field->param2, 1, 1);
        $values['endday'] = $stopdate['day'];
        $values['endmonth'] = $stopdate['month'];
        $values['endyear'] = $stopdate['year'];
        $values['param2'] = $stopdate['year'];

        // Set the values.
        foreach ($values as $key => $value) {
            $param = $mform->getElement($key);
            $param->setValue($value);
        }
    }

    /**
     * Alter form based on submitted or existing data for the param4 field.
     *
     * @param moodleform $mform
     */
    public function define_after_data_param4(&$mform) {
        // Get the parameters from the param4 field.
        // The expected parameter format is:
        // param1=value1;param2=value2 ...
        if (!isset($mform->_defaultValues['param4']) ||
                empty($params = profile_get_parameters($mform->_defaultValues['param4']))) {
            return;
        }

        // Load minimumdate to the form if present.
        if (isset($params['minimumdate'])) {
            $values = explode(' ', $params['minimumdate']);
            $applicable = false;
            $applicableelem = null;
            $group = $mform->getElement('minimumdategroup');
            $elements =& $group->getElements();
            foreach ($elements as $elem) {
                $name = $elem->getName();
                if ($name == 'minimumdate') {
                    if (isset($values[0])) {
                        $applicable = true;
                        $elem->setValue($values[0]);
                    }
                } else if ($name == 'minimumdateperiod') {
                    if (isset($values[1])) {
                        $applicable = true;
                        $elem->setValue($values[1]);
                    }
                } else if ($name == 'minimumdateenabled') {
                    $applicableelem = $elem;
                }
            }

            if ($applicable && $applicableelem) {
                $applicableelem->setValue(1);
            }
        }

        // Load minimumdateerror to the form if present.
        if (isset($params['minimumdateerror'])) {
            $param = $mform->getElement('minimumdateerror');
            $param->setValue($params['minimumdateerror']);
        }
    }

    /**
     * Preprocess data from the profile field form before
     * it is saved.
     *
     * @param stdClass $data from the add/edit profile field form
     * @return stdClass processed data object
     */
    public function define_save_preprocess($data) {
        // Get the current calendar in use - see MDL-18375.
        $calendartype = \core_calendar\type_factory::get_calendar_instance();

        // Check if the start year was changed, if it was then convert from the start of that year.
        if ($data->param1 != $data->startyear) {
            $startdate = $calendartype->convert_to_gregorian($data->param1, 1, 1);
        } else {
            $startdate = $calendartype->convert_to_gregorian($data->param1, $data->startmonth, $data->startday);
        }

        // Check if the end year was changed, if it was then convert from the start of that year.
        if ($data->param2 != $data->endyear) {
            $stopdate = $calendartype->convert_to_gregorian($data->param2, 1, 1);
        } else {
            $stopdate = $calendartype->convert_to_gregorian($data->param2, $data->endmonth, $data->endday);
        }

        $data->param1 = $startdate['year'];
        $data->param2 = $stopdate['year'];

        if (empty($data->param3)) {
            $data->param3 = null;
        }

        // Data validation (param4).
        $param4 = null;
        if (isset($data->minimumdateenabled) && $data->minimumdateenabled) {
            $data->minimumdateerror = trim($data->minimumdateerror);
            $param4 = "minimumdate={$data->minimumdate} {$data->minimumdateperiod};" .
                    "minimumdateerror={$data->minimumdateerror}";
        }
        $data->param4 = $param4;

        // No valid value in the default data column needed.
        $data->defaultdata = '0';

        return $data;
    }
}
