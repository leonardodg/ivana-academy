# TODAY


  1. Create availability/condition/mycondition/version.php:


```
  <?php
  defined('MOODLE_INTERNAL') || die();

  $plugin->component = 'availability_condition_mycondition';
  $plugin->version   = 2026032400;
  $plugin->requires  = 2023051400;
  $plugin->maturity  = MATURITY_ALPHA;
  $plugin->release   = '2026-03-24';

```

 2. Create availability/condition/mycondition/lib.php:

 ```
  <?php
  namespace availability_mycondition;

  use core\availability\condition;
  use core\condition_base;
  use renderable;
  use renderer_base;
  use html_table;
  use html_table_row;
  use html_table_cell;

  class condition extends condition_base implements renderable {

      /** @var mixed|null Value to check */
      private $value;

      /**
       * Constructor.
       *
       * @param stdClass|null $structure Structure data from database
       */
      public function __construct($structure) {
          parent::__construct($structure);
          $this->value = isset($structure->value) ? $structure->value : null;
      }

      /**
       * Validates whether this condition works correctly.
       * @return bool True if condition is valid
       */
      public function is_valid() {
          // Validate your condition logic here
          return true;
      }

      /**
       * Adds JSON form element to the form.
       *
       * @param \MoodleQuickForm $mform The form to add elements to
       * @param string $elname The name of the element to add
       * @param array $values Elements values
       * @return bool Success
       */
      public function get_config_edit(\MoodleQuickForm $mform, $elname, array $values) {
          // Add your form elements here
          // Example: text input for a value
          $mform->addElement('text', $elname, get_string('label', 'availability_condition_mycondition'));
          $mform->setType($elname, PARAM_TEXT);
          $mform->addHelpButton($elname, 'label', 'availability_condition_mycondition');

          return true;
      }

      /**
       * Returns the JSON structure which describes the condition parameters.
       *
       * @return array
       */
      public function json_encode_config() {
          $config = array('value' => $this->value);
          return $config;
      }

      /**
       * Loads an instance of this condition from JSON.
       *
       * @param stdClass $structure Structure data from database
       * @return condition Instance
       */
      public static function load_from_json($structure) {
          return new self($structure);
      }

   /**
       * Returns true if this condition is currently true.
       *
       * @param stdClass $userdata Information about the user
       * @return bool True if condition is true
       */
      public function is_available($userdata) {
          // Implement your condition logic here
          // Return true if the user meets the condition

          if (is_null($this->value)) {
              return false;
          }

          // Example logic:
          return true; // Replace with your actual logic
      }

      /**
       * Returns human readable text describing this condition.
       *
       * @param string $fulltext The text to be included in the description
       * @param bool $not If true then the 'not' condition applies
       * @return string Text describing this condition
       */
      public function get_description($fulltext, $not = false) {
          $value = $this->value;
          $label = get_string('label', 'availability_condition_mycondition');

          if ($not) {
              return get_string('notmet', 'availability_condition_mycondition',
                  array('description' => $label . ': ' . $value, 'text' => $fulltext));
          }

          return get_string('met', 'availability_condition_mycondition',
              array('description' => $label . ': ' . $value, 'text' => $fulltext));
      }
    /**
       * Returns true if the condition is satisfied for a user.
       *
       * @param stdClass $user The user to check
       * @return bool
       */
      public function check_user($user) {
          // Your condition checking logic
          return $this->is_available($user);
      }

      /**
       * Returns the JS required to add this condition to the page.
       *
       * @return array An array of JavaScript strings
       */
      public function get_js() {
          return array();
      }

      /**
       * Export for template.
       *
       * @param renderer_base $output
       * @return stdClass
       */
      public function export_for_template(renderer_base $output) {
          $data = new stdClass();
          $data->label = get_string('label', 'availability_condition_mycondition');
          $data->value = $this->value;
          return $data;
      }
  }

```


 3. Create availability/condition/mycondition/settings.php:

 ```
  <?php
  defined('MOODLE_INTERNAL') || die;

  if ($hassiteconfig) {
      // Add your settings here
      // Example:
      // $settings->add(new admin_setting_configtext(
      //     'availability_condition_mycondition/defaultvalue',
      //     get_string('setting', 'availability_condition_mycondition'),
      //     get_string('settingdesc', 'availability_condition_mycondition'),
      //     '',
      //     PARAM_TEXT
      // ));
  }
```

  4. Create availability/condition/mycondition/lang/en/availability_condition_mycondition.php:

```
  <?php
  $string['pluginname'] = 'My Condition';
  $string['label'] = 'My Condition Value';
  $string['met'] = '{$a->description} - {$a->text}';
  $string['notmet'] = 'Not {$a->description} - {$a->text}';
  $string['setting'] = 'Default Value';
  $string['settingdesc'] = 'Default value for the condition';
  $string['edithelp'] = 'Edit my condition';
```

 5. Create availability/condition/mycondition/tests/behat/features/mycondition.feature (optional):
  Feature: Availability condition - My Condition
    In order to test my availability condition
    As a user
    I need to have content available only when my condition is met

    Scenario: Condition should be met when value matches
      Given the following "course" exists:
        | fullname | Course 1 |
        | shortname | C1 |
      And the following "activity" exists:
        | activity | assignment |
        | course   | Course 1 |
        | name     | Assignment 1 |
      When I am on the "Edit settings" page for "Course 1"
      And I click on "Add restriction" for "Assignment 1"
      And I click on "My Condition"
      And I set the following fields to these values:
        | My Condition | testvalue |
      And I press "Save and display"
      Then the "Save and display" button should be visible

6. Create availability/condition/mycondition/tests/behat/step_definitions/mycondition_steps.php (optional):
```
  <?php
  namespace Behat\availability_condition_mycondition\test\step_definitions;

  use Behat\Gherkin\Node\TableNode;
  use Behat\Mink\Exception\ElementNotFoundException;
  use Behat\Mink\Exception\UnsupportedException;
  use Behat\Mink\Exception\TimeoutException;

  require_once(__DIR__ . '/../../../../../../../../../behat/classes/utilities.php');

  /**
   * Custom step definitions for testing availability conditions.
   */
  class availability_condition_mycondition_step_definitions extends behat_utilities {

      /**
       * Sets form field value using the available steps.
       *
       * @param string $field The field name
       * @param string $value The value to set
       */
      public function set_field($field, $value) {
          $this->press_button('Continue'); // Sometimes needed
          $this->fill_field($field, $value);
      }

      /**
       * Checks if the availability condition is present in the form.
       *
       * @param string $conditionname The condition name to check for
       * @throws ElementNotFoundException
       */
      public function i_click_on_availability_condition($conditionname) {
          $this->click_link($conditionname);
      }
  }
```


---------------------------------------------------



  File Structure

  availability/
  └── condition/
      └── enrol_dates/                 <-- This is your plugin folder
          ├── version.php             <-- Required (Defines version, requires, etc.)
          ├── settings.php            <-- Optional (If you need admin settings)
          ├── db/
          │   └── access.php          <-- Optional (If you need permissions logic)
          ├── lang/
          │   └── en/
          │       └── enrol_dates.php <-- Required (Language strings)
          └── classes/
              └── condition.php       <-- Required (The main logic class)

  ---
  Essential Code Examples

  Here is how the main files should look to follow the standard Moodle pattern.

  1. version.php

  Standard version file.

  <?php
  defined('MOODLE_INTERNAL') || die();

  $plugin->component = 'availability_enrol_dates';
  $plugin->version   = 2023100100; // YYYYMMDDXX
  $plugin->requires  = 2022112900; // Min Moodle version
  $plugin->maturity  = MATURITY_ALPHA;
  $plugin->release   = 'v0.1-dev';


  2. classes/condition.php                                                                                                                                                   15:53:52 [16/328]

  This is the core class. It must extend \core\availability\condition\condition and implement is_available, get_description, etc.

  <?php
  namespace core\availability\condition;

  defined('MOODLE_INTERNAL') || die();

  require_once($CFG->libdir . '/availability/condition.php');

  class condition extends \core\availability\condition {

      // Constructor, validation, export and import methods go here
      public function is_available($notused, \core\condition_info $conditioninfo, $notused2) {
          // Return true if the user can access based on the enrolment dates
      }

      public function get_description($notused) {
          // Return the string to display in the condition builder
      }
  }

  3. lang/en/enrol_dates.php

  This is where you define the strings used in the UI.

  $string['pluginname'] = 'Enrolment Date Availability';
  $string['description'] = 'Restrict access based on the enrolment start or end date.';
  $string['notstarted'] = 'Has not started yet';
  $string['ended'] = 'Has already ended';

  4. db/access.php (Optional)

  Used to define capabilities if needed, though most availability conditions rely on core capabilities.

  $capabilities = array(
      'moodle/availability/condition:enrol_dates:view' => array(
          'captype' => 'read',
          'contextlevel' => CONTEXT_MODULE,
          'legacy' => array(
              'guest' => CAP_PREVENT
          )
      ),
      // ...
  );



# YESTARDAY

```

 <?php
  namespace moodleplugin\availability_enrol_dates;

  use core_availability\condition;
  use core_lib\system_date;
  use core\ Então // Include Moodle's core classes if needed

  /**
   * Custom condition class for enrollment date rules
   */
  class Condition extends condition
  {
      /**
       * Constants for direction rules
       */
      const DIRECTION_BEFORE = 'before';
      const DIRECTION_AFTER = 'after';

      /**
       * Constants for time check options
       */
      const TIME_COURSE_START = 'Course TimeStart';
      const TIME_COURSE_END = 'Course TimeEnd';
      const TIME_ENROL_START = 'Enrol TimeStart';
      const TIME_ENROL_END = 'Enrol TimeEnd';

      /**
       * Constants for period options
       */
      const PERIOD_HOURS = 'Hours';
      const PERIOD_DAYS = 'Days';
      const PERIOD_MONTHS = 'Months';

    /**
       * Check if enrollment is allowed based on stored conditions
       *
       * @param \context $context
       * @param \user $user
       * @return bool
       */
      public function check(\context $context, \user $user): bool
      {
          // Fetch condition settings from plugin database (replace with your storage)
          $settings = $this->getConditionSettings($user->id);

          // Default to blocking if no settings found
          if (empty($settings)) {
              return false;
          }

          // Validate settings
          $this->validateSettings($settings);

          // Get relevant dates from database
          $courseDates = $this->getCourseDates($context->course->id);
          $enrolDates = $this->getEnrolDates($user->id);

          // Combine dates into a unified array
          $dbDates = array_merge($courseDates, $enrolDates);

          // Evaluate condition
          $result = $this->evaluateCondition(
              $settings['direction'],
              $settings['timeCheck'],
              $settings['period'],
              $settings['periodValue'],
              $dbDates
          );

          return $result['isAvailable'];
      }



    /**
       * Retrieve condition settings for a user/course
       */
      private function getConditionSettings(int $userId): array
      {
          // Example: Fetch from a custom settings table or moodle_enrolment
          // Replace with your actual storage mechanism
          $db = \DB::get_instance();
          $record = $db->get_record('moodleplugin_availability_enrol_dates_settings',
              ['userid' => $userId],
              ['direction', 'timeCheck', 'period', 'periodValue']);

          // Default settings if none found
          return [
              'direction' => self::DIRECTION_BEFORE,
              'timeCheck' => self::TIME_ENROL_END,
              'period' => self::PERIOD_DAYS,
              'periodValue' => 14
          ];
      }

    /**
       * Validate condition settings
       */
      private function validateSettings(array &$settings): void
      {
          $validDirections = [self::DIRECTION_BEFORE, self::DIRECTION_AFTER];
          $validTimeChecks = [self::TIME_COURSE_START, self::TIME_COURSE_END,
                            self::TIME_ENROL_START, self::TIME_ENROL_END];
          $validPeriods = [self::PERIOD_HOURS, self::PERIOD_DAYS, self::PERIOD_MONTHS];

          if (!in_array($settings['direction'], $validDirections)) {
              throw new \InvalidArgumentException("Invalid direction");
          }
          if (!in_array($settings['timeCheck'], $validTimeChecks)) {
              throw new \InvalidArgumentException("Invalid time check");
          }
          if (!in_array($settings['period'], $validPeriods)) {
              throw new \InvalidArgumentException("Invalid period");
          }
          if ($settings['periodValue'] <= 0) {
              throw new \InvalidArgumentException("Period value must be positive");
          }
      }

      /**
       * Get course dates from Moodle database
       */
      private function getCourseDates(int $courseId): array
      {
          $db = \DB::get_instance();
          $course = $db->get_record('mdl_course', ['id' => $courseId]);
          return [
              'CourseTimeStart' => $course->timestart ?? '',
              'CourseTimeEnd' => $course->timeend ?? '',
          ];
      }

    /**
       * Get enrollment dates for a user
       */
      private function getEnrolDates(int $userId): array
      {
          $db = \DB::get_instance();
          $record = $db->get_record('mdl_enrolment', ['userid' => $userId]);
          return [
              'EnrolTimeStart' => $record->timestart ?? '',
              'EnrolTimeEnd' => $record->timeend ?? '',
          ];
      }

      /**
       * Evaluate condition based on settings and dates
       */
      private function evaluateCondition(
          string $direction,
          string $timeCheck,
          string $period,
          int $periodValue,
          array $dbDates
      ): array {
          $baseDate = $this->getBaseDate($timeCheck, $dbDates);
          if (!$baseDate) {
              return ['isAvailable' => false];
          }

          $threshold = $this->calculateThreshold($baseDate, $direction, $period, $periodValue);
          $currentDate = system_date::instance()->format(\format::default_format);

          if ($direction === self::DIRECTION_BEFORE) {
              return ['isAvailable' => $currentDate < $threshold];
          } else {
              return ['isAvailable' => $currentDate > $threshold];
          }
      }
   /**
       * Get base date from database
       */
      private function getBaseDate(string $timeCheck, array $dbDates): ?\DateTime
      {
          switch ($timeCheck) {
              case self::TIME_COURSE_START: return $this->parseDate($dbDates['CourseTimeStart']);
              case self::TIME_COURSE_END:   return $this->parseDate($dbDates['CourseTimeEnd']);
              case self::TIME_ENROL_START:  return $this->parseDate($dbDates['EnrolTimeStart']);
              case self::TIME_ENROL_END:    return $this->parseDate($dbDates['EnrolTimeEnd']);
              default: return null;
          }
      }

      /**
       * Parse date string (e.g., "2026-03-23 14:30:00")
       */
      private function parseDate(string $dateString): ?\DateTime
      {
          if (empty($dateString)) return null;
          try {
              return new \DateTime($dateString);
          } catch (\Exception $e) {
              return null;
          }
      }
    /**
       * Calculate threshold date
       */
      private function calculateThreshold(
          \DateTime $baseDate,
          string $direction,
          string $period,
          int $periodValue
      ): \DateTime {
          $interval = $this->createDateInterval($period, $periodValue);
          return $direction === self::DIRECTION_BEFORE
              ? $baseDate->sub($interval)
              : $baseDate->add($interval);
      }

      /**
       * Create date interval
       */
      private function createDateInterval(string $period, int $periodValue): \DateInterval
      {
          switch ($period) {
              case self::PERIOD_HOURS:  return new \DateInterval("PT{$periodValue}H");
              case self::PERIOD_DAYS:   return new \DateInterval("P{$periodValue}D");
              case self::PERIOD_MONTHS: return new \DateInterval("P{$periodValue}M");
              default: throw new \InvalidArgumentException("Invalid period type");
          }
      }
  }
```