@local @local_openlms @openlms
Feature: Test local_openlms behat steps

  Scenario: local_openlms behat step: unnecessary Admin bookmarks block gets deleted
    When unnecessary Admin bookmarks block gets deleted
    And I log in as "admin"
    And I navigate to "Users > Accounts > Bulk user actions" in site administration
    Then I should not see "Admin bookmarks"

  Scenario: local_openlms behat step: I skip tests if plugin is not installed
    Given I skip tests if "local_openlms" is not installed
    When I skip tests if "local_grgrgrgrgrgr" is not installed
    Then I should see "never reached"

  Scenario: local_openlms behat step: List term definition assertion works
    Given I skip tests if "enrol_programs" is not installed
    And the following "enrol_programs > programs" exist:
      | fullname    | idnumber | category | public | archived |
      | Program 000 | PR0      |          | 0      | 0        |
    And I log in as "admin"
    And I navigate to "Programs > Program management" in site administration
    When I follow "Program 000"
    Then I should see "Program 000" in the "Full name:" definition list item
    And I should see "Program" in the "Full name:" definition list item
    And I should see "P" in the "Full name:" definition list item
    And I should see "rog" in the "Full name:" definition list item
  # Uncomment following to test a failure.
    #And I should see "program 000" in the "Full name:" definition list item

  Scenario: local_openlms behat step: List term definition negative assertion works
    Given I skip tests if "enrol_programs" is not installed
    And the following "enrol_programs > programs" exist:
      | fullname    | idnumber | category | public | archived |
      | Program 000 | PR0      |          | 0      | 0        |
    And I log in as "admin"
    And I navigate to "Programs > Program management" in site administration
    When I follow "Program 000"
    Then I should not see "program" in the "Full name:" definition list item
  # Uncomment following to test a failure.
    #And I should not see "Program" in the "Full name:" definition list item

  Scenario: local_openlms behat step: I am on the profile page of user
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | First     | Student  | student1@example.com |
      | student2 | Second    | Student  | student2@example.com |
      | student3 | Third     | Student  | student3@example.com |
    And I log in as "admin"
    When I am on the profile page of user "student1"
    Then I should see "First Student"
    And I should see "User details"
    And I should see "Today's logs"
