Feature: Test access to local domains

Scenario: Test access to local domains
Given I am on "http://selenium-hub:4444/grid/console"
Then I should see "Grid Console"