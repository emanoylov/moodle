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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/profile/definelib.php');
require_once($CFG->dirroot . '/user/profile/index_field_form.php');
require_once($CFG->dirroot . '/user/profile/field/datetime/define.class.php');
require_once($CFG->dirroot . '/user/profile/field/datetime/field.class.php');

/**
 * Profile field datetime form tests.
 *
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_datetime_form_testcase extends \advanced_testcase {
    private const MINIMUMDATETIMEERROR = "Minimum test date required";

    /** @var int Category id */
    private $categoryid;

    /** @var stdClass The test user */
    private $user;

    public function setUp() {
        $this->resetAfterTest();

        // Create test user.
        $this->user = self::getDataGenerator()->create_user();
        self::setUser($this->user);

        // Create category.
        $this->categoryid = $this->getDataGenerator()->create_profile_field_category();
    }

    /**
     * Test the validation for a minimum date by a user signup.
     */
    public function test_minimumdate_validation_by_user_signup() {
        $field = $this->create_minimumdate_field(true);

        $this->do_test_minimumdate_validation_by_user_signup($field, 17, true);
        $this->do_test_minimumdate_validation_by_user_signup($field, 18, false);
        $this->do_test_minimumdate_validation_by_user_signup($field, 19, false);
    }

    /**
     * Test no validation for a minimum date by a user signup.
     */
    public function test_minimumdate_novalidation_by_user_signup() {
        $field = $this->create_minimumdate_field(false);

        $this->do_test_minimumdate_validation_by_user_signup($field, 17, false);
        $this->do_test_minimumdate_validation_by_user_signup($field, 18, false);
        $this->do_test_minimumdate_validation_by_user_signup($field, 19, false);
    }

    /**
     * Test the validation for a minimum date by a user signup.
     *
     * @param object $field
     * @param int $years
     * @param bool $errorsexpected
     */
    private function do_test_minimumdate_validation_by_user_signup(object $field, int $years, bool $errorsexpected) {
        global $CFG;

        $CFG->registerauth = 'email';

        $username = "testuser$years";
        $password = 'Abcdef1*';
        $firstname = 'Test';
        $lastname = "User $years";
        $email = "testuser$years@example.com";
        $city = 'London';
        $country = '';

        $profilefields = [
                [
                        'type' => 'datetime',
                        'name' => 'profile_field_minimumdate',
                        'value' => $this->get_test_date($years),
                ]
        ];

        // Create new user.
        $result = auth_email_external::signup_user($username, $password, $firstname, $lastname, $email,
                $city, $country, '', '', $profilefields);
        $result = external_api::clean_returnvalue(auth_email_external::signup_user_returns(), $result);

        $warnings = $result['warnings'];
        if ($errorsexpected) {
            $fieldname = $this->get_field_name($field);

            $this->assertFalse($result['success']);
            $this->assertCount(1, $warnings);
            $this->assertEquals($fieldname, $warnings[0]['item']);
            $this->assertEquals(self::MINIMUMDATETIMEERROR, $warnings[0]['message']);
        } else {
            $this->assertTrue($result['success']);
            $this->assertCount(0, $warnings);
        }
    }

    /**
     * Test the validation for a minimum date by the field class.
     */
    public function test_minimumdate_validation_by_field_class() {
        $field = $this->create_minimumdate_field(true);

        $this->do_test_minimumdate_validation_by_field_class($field, 17, true);
        $this->do_test_minimumdate_validation_by_field_class($field, 18, false);
        $this->do_test_minimumdate_validation_by_field_class($field, 19, false);
    }

    /**
     * Test no validation for a minimum date by the field class.
     */
    public function test_minimumdate_novalidation_by_field_class() {
        $field = $this->create_minimumdate_field(false);

        $this->do_test_minimumdate_validation_by_field_class($field, 17, false);
        $this->do_test_minimumdate_validation_by_field_class($field, 18, false);
        $this->do_test_minimumdate_validation_by_field_class($field, 19, false);
    }

    /**
     * Test the validation for a minimum date by the field class.
     *
     * @param object $field
     * @param int $years
     * @param bool $errorsexpected
     */
    private function do_test_minimumdate_validation_by_field_class(object $field, int $years, bool $errorsexpected): void {
        $user = $this->get_user($field, $this->get_test_date($years));
        $fieldname = $this->get_field_name($field);

        $datetime = new profile_field_datetime($field->id, $user->id);
        $errors = $datetime->edit_validate_field($user);

        if ($errorsexpected) {
            $this->assertCount(1, $errors);
            $this->assertTrue(array_key_exists($fieldname, $errors));
            $this->assertEquals(self::MINIMUMDATETIMEERROR, $errors[$fieldname]);
        } else {
            $this->assertCount(0, $errors);
        }
    }

    /**
     * Create a minimum date profile field.
     *
     * @param bool $addvalidation
     * @return object
     */
    private function create_minimumdate_field(bool $addvalidation) {
        $param4 = $addvalidation ? "minimumdatetime=18 years;minimumdatetimeerror=" . self::MINIMUMDATETIMEERROR : '';
        $field = (object) [
                'shortname' => 'minimumdate',
                'name' => 'Date 1',
                'datatype' => 'datetime',
                'descriptionformat' => FORMAT_HTML,
                'categoryid' => $this->categoryid,
                'required' => '1',
                'locked' => '0',
                'visible' => '1',
                'forceunique' => '0',
                'signup' => '1',
                'defaultdata' => 0,
                'defaultdataformat' => FORMAT_MOODLE,
                'startyear' => '',
                'endyear' => '',
                'param1' => '1900',
                'param2' => '2020',
                'param3' => '',
                'param4' => $param4,
                'param5' => '',
        ];
        $this->create_profile_field($field);
        return $field;
    }

    /**
     * Create a profile field.
     *
     * @param object $field The field
     */
    private function create_profile_field($field) {
        $define = new profile_define_datetime();
        $define->define_save($field);
    }

    /**
     * Get a test date.
     *
     * @param int $subtractyears
     * @return int
     */
    private function get_test_date(int $subtractyears): int {
        return mktime(0, 0, 0, date('m'), date('d'), date('Y') - $subtractyears);
    }

    /**
     * Get the user and set the specified profile field.
     *
     * @param object $field
     * @param int $date
     * @return stdClass
     */
    private function get_user(object $field, int $date) {
        $fieldname = $this->get_field_name($field);

        $user = $this->user;
        $user->$fieldname = $date;
        return $user;
    }

    /**
     * Get field name.
     *
     * @param object $field
     * @return string
     */
    private function get_field_name(object $field): string {
        return "profile_field_{$field->shortname}";
    }
}