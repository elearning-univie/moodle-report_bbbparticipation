@report @report_bbbparticipation

Feature: As a teacher I want to view the BBB participation report

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | John   | Doe  | teacher@example.com |
      | student1  | Gerlinde   | Hasebutz  | student1@example.com |
      | student2  | Karla      | Wurmkistl | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student1 | C1    | student        |
      | student2 | C1    | student        |
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "BigBlueButtonBN" to section "1" and I fill the form with:
      | Instance type | Room/Activity with recordings |
      | Virtual classroom name | BBB1 |

  @javascript
  Scenario: I can view the BBB participation report
    When I click on "Actions menu" "link"
    And I click on "More..." "link"
    And I click on "BBB participation" "link"
    Then I should see "BBB participation"

  @javascript
  Scenario: I can view the BBB participation report with ID number
    When I click on "Actions menu" "link"
    And I click on "More..." "link"
    And I click on "BBB participation" "link"
    Then I should see "ID number"
