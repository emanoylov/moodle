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
 * This file contains the datetime profile field class.
 *
 * @package profilefield_datetime
 * @copyright 2010 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/**
 * Handles displaying and editing the datetime field.
 *
 * @copyright 2010 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class profile_field_datetime extends profile_field_base {

    /**
     * Handles editing datetime fields.
     *
     * @param moodleform $mform
     */
    public function edit_field_add($mform) {
        // Get the current calendar in use - see MDL-18375.
        $calendartype = \core_calendar\type_factory::get_calendar_instance();

        // Check if the field is required.
        if ($this->field->required) {
            $optional = false;
        } else {
            $optional = true;
        }

        // Convert the year stored in the DB as gregorian to that used by the calendar type.
        $startdate = $calendartype->convert_from_gregorian($this->field->param1, 1, 1);
        $stopdate = $calendartype->convert_from_gregorian($this->field->param2, 1, 1);

        $attributes = array(
            'startyear' => $startdate['year'],
            'stopyear'  => $stopdate['year'],
            'optional'  => $optional
        );

        // Check if they wanted to include time as well.
        if (!empty($this->field->param3)) {
            $mform->addElement('date_time_selector', $this->inputname, format_string($this->field->name), $attributes);
        } else {
            $mform->addElement('date_selector', $this->inputname, format_string($this->field->name), $attributes);
        }

        $mform->setType($this->inputname, PARAM_INT);
        $mform->setDefault($this->inputname, time());
    }

    /**
     * If timestamp is in YYYY-MM-DD or YYYY-MM-DD-HH-MM-SS format, then convert it to timestamp.
     *
     * @param string|int $datetime datetime to be converted.
     * @param stdClass $datarecord The object that will be used to save the record
     * @return int timestamp
     * @since Moodle 2.5
     */
    public function edit_save_data_preprocess($datetime, $datarecord) {
        if (!$datetime) {
            return 0;
        }

        if (is_numeric($datetime)) {
            $gregoriancalendar = \core_calendar\type_factory::get_calendar_instance('gregorian');
            $datetime = $gregoriancalendar->timestamp_to_date_string($datetime, '%Y-%m-%d-%H-%M-%S', 99, true, true);
        }

        $datetime = explode('-', $datetime);
        // Bound year with start and end year.
        $datetime[0] = min(max($datetime[0], $this->field->param1), $this->field->param2);

        if (!empty($this->field->param3) && count($datetime) == 6) {
            return make_timestamp($datetime[0], $datetime[1], $datetime[2], $datetime[3], $datetime[4], $datetime[5]);
        } else {
            return make_timestamp($datetime[0], $datetime[1], $datetime[2]);
        }
    }

    /**
     * Display the data for this field.
     */
    public function display_data() {
        // Check if time was specified.
        if (!empty($this->field->param3)) {
            $format = get_string('strftimedaydatetime', 'langconfig');
        } else {
            $format = get_string('strftimedate', 'langconfig');
        }

        // Check if a date has been specified.
        if (empty($this->data)) {
            return get_string('notset', 'profilefield_datetime');
        } else {
            return userdate($this->data, $format);
        }
    }

    /**
     * Check if the field data is considered empty
     *
     * @return boolean
     */
    public function is_empty() {
        return empty($this->data);
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $usernew
     * @return  array  contains error message otherwise null
     */
    public function edit_validate_field($usernew) {
        $errors = parent::edit_validate_field($usernew);
        $errors += $this->edit_validate_field_param4($usernew);
        return $errors;
    }

    /**
     * Validate the form field from profile page for the param4 field.
     *
     * @param stdClass $usernew
     * @return array contains error messages
     */
    protected function edit_validate_field_param4($usernew) : array {
        // Get the parameters from the param4 field.
        // The expected parameter format is:
        // param1=value1;param2=value2 ...
        $params = profile_get_parameters($this->field->param4);
        if (empty($params)) {
            return [];
        }

        $errors = [];
        $value = $this->get_field_value($usernew);

        // Validate minimumdate.
        // Expected parameters:
        // minimumdate - the minimum date to enforce e.g. 18 years
        // minimumdateerror - the error to show if minimumdate is not valid.
        if (isset($params['minimumdate']) && !empty($params['minimumdate'])) {
            $today = strtotime('today');
            $minvalue = strtotime($params['minimumdate'], $value);

            if ($minvalue > $today) {
                if (isset($params['minimumdateerror']) && !empty($params['minimumdateerror'])) {
                    $error = $params['minimumdateerror'];
                } else {
                    $error = get_string('minimumdateinvalid', 'profilefield_datetime');
                }
                $errors[$this->inputname] = $error;
            }
        }

        return $errors;
    }

    /**
     * Return the field type and null properties.
     * This will be used for validating the data submitted by a user.
     *
     * @return array the param type and null property
     * @since Moodle 3.2
     */
    public function get_field_properties() {
        return array(PARAM_INT, NULL_NOT_ALLOWED);
    }

    /**
     * Check if the field should convert the raw data into user-friendly data when exporting
     *
     * @return bool
     */
    public function is_transform_supported(): bool {
        return true;
    }
}
