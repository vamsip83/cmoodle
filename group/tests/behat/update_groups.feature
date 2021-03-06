@core @core_group
Feature: Automatic updating of groups and groupings
  In order to check the expected results occur when updating groups and groupings in different scenarios
  As a teacher
  I need to create groups and groupings under different scenarios and check that the expected result occurs when attempting to update them.

  Background:
    Given the following "courses" exists:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I expand "Users" node
    And I follow "Groups"
    And I press "Create group"
    And I fill the moodle form with:
      | Group name | Group (without ID) |
    And I press "Save changes"
    And I press "Create group"
    And I fill the moodle form with:
      | Group name | Group (with ID) |
      | Group ID number | An ID |
    And I press "Save changes"
    And I follow "Groupings"
    And I press "Create grouping"
    And I fill the moodle form with:
      | Grouping name | Grouping (without ID) |
    And I press "Save changes"
    And I press "Create grouping"
    And I fill the moodle form with:
      | Grouping name | Grouping (with ID) |
      | Grouping ID number | An ID |
    And I press "Save changes"
    And I follow "Groups"

  @javascript
  Scenario: Update groups and groupings with ID numbers
    Given I select "Group (with ID)" from "groups"
    And I press "Edit group settings"
    And the "idnumber" field should match "An ID" value
    And I fill the moodle form with:
      | Group name | Group (with ID) (updated) |
      | Group ID number | An ID (updated) |
    When I press "Save changes"
    Then I should see "Group (with ID) (updated)"
    And I select "Group (with ID) (updated)" from "groups"
    And I press "Edit group settings"
    And the "idnumber" field should match "An ID (updated)" value
    And I press "Save changes"
    And I follow "Groupings"
    And I click on "Edit" "link" in the "Grouping (with ID)" "table_row"
    And the "idnumber" field should match "An ID" value
    And I fill the moodle form with:
      | Grouping name | Grouping (with ID) (updated) |
      | Grouping ID number | An ID (updated) |
    And I press "Save changes"
    And I should see "Grouping (with ID) (updated)"
    And I click on "Edit" "link" in the "Grouping (with ID) (updated)" "table_row"
    And the "idnumber" field should match "An ID (updated)" value

  @javascript
  Scenario: Update groups and groupings with ID numbers without the 'moodle/course:changeidnumber' capability
    Given I log out
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | moodle/course:changeidnumber | Prevent |
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I expand "Users" node
    And I follow "Groups"
    And I select "Group (with ID)" from "groups"
    When I press "Edit group settings"
    Then the "idnumber" "field" should be readonly
    And the "idnumber" field should match "An ID" value
    And I fill the moodle form with:
      | Group name | Group (with ID) (updated) |
    And I press "Save changes"
    And I should see "Group (with ID) (updated)"
    And I select "Group (with ID) (updated)" from "groups"
    And I press "Edit group settings"
    And the "idnumber" "field" should be readonly
    And the "idnumber" field should match "An ID" value
    And I press "Save changes"
    And I follow "Groupings"
    And I click on "Edit" "link" in the "Grouping (with ID)" "table_row"
    And the "idnumber" "field" should be readonly
    And the "idnumber" field should match "An ID" value
    And I fill the moodle form with:
      | Grouping name | Grouping (with ID) (updated) |
    And I press "Save changes"
    And I should see "Grouping (with ID) (updated)"
    And I click on "Edit" "link" in the "Grouping (with ID) (updated)" "table_row"
    And the "idnumber" "field" should be readonly
    And the "idnumber" field should match "An ID" value

