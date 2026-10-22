# MAKE TEST


- Create availability/condition/enrol_dates/tests/behat/features/enrol_dates.feature (optional):
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

- Create availability/condition/mycondition/tests/behat/step_definitions/mycondition_steps.php (optional):
  <?php
  namespace Behat\availability_condition_mycondition	est\step_definitions;

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

