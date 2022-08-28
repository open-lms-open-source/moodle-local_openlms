@local @local_openlms @openlms
Feature: Test local_openlms behat steps

  Scenario: local_openlms behat step: unnecessary Admin bookmarks block gets deleted
    When unnecessary Admin bookmarks block gets deleted
    And I log in as "admin"
    And I navigate to "Users > Accounts > Bulk user actions" in site administration
    Then I should not see "Admin bookmarks"

    
