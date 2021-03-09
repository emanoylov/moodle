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
 * Base class for unit tests for profilefield_datetime.
 *
 * @package profilefield_datetime
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_datetime\tests;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/profile/definelib.php');
require_once($CFG->dirroot . '/user/profile/field/datetime/define.class.php');
require_once($CFG->dirroot . '/user/profile/field/datetime/field.class.php');

/**
 * Unit tests for the datetime profile field class.
 *
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @codeCoverageIgnore
 */
class form_test extends \advanced_testcase {
    /** @var string Minimum date error */
    private const MINIMUMDATEERROR = "Minimum test date required";

    /** @var int Category id */
    private $categoryid;

    /** @var stdClass The test user */
    private $user;

    /** @var int The test user count */
    private $usercount;

    public function setUp(): void {
        $this->resetAfterTest();

        $this->usercount = 0;

        // Create test user.
        $this->user = self::getDataGenerator()->create_user();
        self::setUser($this->user);

        // Create category.
        $this->categoryid = $this->getDataGenerator()->create_custom_profile_field_category()->id;
    }

    /**
     * Test the validation for a minimum date by a user signup.
     */
    public function test_minimumdate_validation_by_user_signup() {
        $field = $this->create_minimumdate_field(true);

        $this->run_minimumdate_validation_by_user_signup_scenarios($field);

        // Make field non-required.
        $this->update_profile_field($field, false);

        $this->run_minimumdate_validation_by_user_signup_scenarios($field);
    }

    /**
     * Test the validation for a minimum date by a user signup scenarios.
     *
     * @param object $field
     */
    private function run_minimumdate_validation_by_user_signup_scenarios($field) {
        $this->do_test_minimumdate_validation_by_user_signup($field, 17, true);
        $this->do_test_minimumdate_validation_by_user_signup($field, 18, false);
        $this->do_test_minimumdate_validation_by_user_signup($field, 19, false);
    }

    /**
     * Test no validation for a minimum date by a user signup.
     */
    public function test_minimumdate_novalidation_by_user_signup() {
        $field = $this->create_minimumdate_field(false);

        $this->run_minimumdate_novalidation_by_user_signup_scenarios($field);

        // Make field non-required.
        $this->update_profile_field($field, false);

        $this->run_minimumdate_novalidation_by_user_signup_scenarios($field);
    }

    /**
     * Test no validation for a minimum date by a user signup scenarios.
     *
     * @param object $field
     */
    private function run_minimumdate_novalidation_by_user_signup_scenarios($field) {
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
        $this->usercount++;
        $suffix = "{$this->usercount}_{$years}";

        $username = "testuser$suffix";
        $password = 'Abcdef1*';
        $firstname = 'Test';
        $lastname = "User $suffix";
        $email = "testuser$suffix@example.com";
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
        $result = \auth_email_external::signup_user($username, $password, $firstname, $lastname, $email,
                $city, $country, '', '', $profilefields);
        $result = \core_external\external_api::clean_returnvalue(\auth_email_external::signup_user_returns(), $result);

        $warnings = $result['warnings'];
        if ($errorsexpected) {
            $fieldname = $this->get_field_name($field);

            $this->assertFalse($result['success']);
            $this->assertCount(1, $warnings);
            $this->assertEquals($fieldname, $warnings[0]['item']);
            $this->assertEquals(self::MINIMUMDATEERROR, $warnings[0]['message']);
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

        $this->run_minimumdate_validation_by_field_class_scenarios($field);

        // Make field non-required.
        $this->update_profile_field($field, false);

        $this->run_minimumdate_validation_by_field_class_scenarios($field);
    }

    /**
     * Test the validation for a minimum date by the field class scenarios.
     *
     * @param object $field
     */
    private function run_minimumdate_validation_by_field_class_scenarios($field) {
        $this->do_test_minimumdate_validation_by_field_class($field, 17, true);
        $this->do_test_minimumdate_validation_by_field_class($field, 18, false);
        $this->do_test_minimumdate_validation_by_field_class($field, 19, false);
    }

    /**
     * Test no validation for a minimum date by the field class.
     */
    public function test_minimumdate_novalidation_by_field_class() {
        $field = $this->create_minimumdate_field(false);

        $this->run_minimumdate_novalidation_by_field_class_scenarios($field);

        // Make field non-required.
        $this->update_profile_field($field, false);

        $this->run_minimumdate_novalidation_by_field_class_scenarios($field);
    }

    /**
     * Test no validation for a minimum date by the field class scenarios.
     *
     * @param object $field
     */
    private function run_minimumdate_novalidation_by_field_class_scenarios($field) {
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

        $datetime = new \profile_field_datetime($field->id, $user->id);
        $errors = $datetime->edit_validate_field($user);

        if ($errorsexpected) {
            $this->assertCount(1, $errors);
            $this->assertTrue(array_key_exists($fieldname, $errors));
            $this->assertEquals(self::MINIMUMDATEERROR, $errors[$fieldname]);
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
        $param4 = $addvalidation ? "minimumdate=18 years;minimumdateerror=" . self::MINIMUMDATEERROR : '';
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
        if ($addvalidation) {
            $field->minimumdateenabled = 1;
            $field->minimumdate = 18;
            $field->minimumdateperiod = 'years';
            $field->minimumdateerror = self::MINIMUMDATEERROR;
        }
        $this->save_profile_field($field);
        return $field;
    }

    /**
     * Update a profile field with the specified parameters.
     *
     * @param object $field
     * @param bool $required
     */
    protected function update_profile_field($field, $required) {
        $field->required = $required;
        $this->save_profile_field($field);
    }

    /**
     * Save a profile field.
     *
     * @param object $field The field
     */
    protected function save_profile_field($field) {
        $define = new \profile_define_datetime();
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
