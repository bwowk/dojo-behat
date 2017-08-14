Feature: Test web pages navigations

Scenario Outline: Navigating to pages
  Given the site url is "http://the-internet"
  When I go to "<destination_url>"
  Then I should be on "<actual_url>"
  Scenarios:
  | destination_url                              | actual_url                                   |
  | /                                            | /                                            |
  | http://the-internet/           | /                                            |
  | /                                            | http://the-internet/           |
  | http://the-internet/redirector | http://the-internet/redirector |
  | http://the-internet/redirector | /redirector                                  |
  | /redirector                                  | /redirector                                  |
  | /redirector                                  | http://the-internet/redirector |

Scenario: Navigating to pages alternative steps
  Given I set the site url to "http://the-internet"
  And I am on "/"
  Then I should be on "http://the-internet/"

Scenario: Testing history navigation
  Given I am on "http://the-internet/"
  When I click on "//a[text()='Status Codes']"
  Then I should be on "/status_codes"
  When I move backward one page
  Then I should be on the homepage
  When I move forward one page
  Then I should be on "/status_codes"

Scenario: Reloading the page
  Given I am on "http://the-internet/dynamic_loading/1"
  Then I should see "Start"
  When I click on "#start button"
  Then I should see "Loading..."
  And I should not see "Start"
  When I reload the page
  Then I should see "Start"

Scenario Outline: Testing url against regex
  Given I am on "https://www.drupal.org/project/issues/drupal"
  When I click on "(//table[contains(@class,'project-issue')]/tbody/tr/td[contains(@class,'views-field-title')]/a)[<n>]"
  Then the url should match "\/node\/\d+"
  Examples:
  | n      |
  | 1      |
  | 10     |
  | 25     |
  | 50     |
  | last() |

Scenario: Checking page html content
  Given I go to "http://the-internet/"
  Then the response should contain "<title>The Internet</title>"
  Then the page source should contain "<title>The Internet</title>"
  Then the page html should contain "<title>The Internet</title>"
  But the response should not contain "<title>Hakuna Matata</title>"
  And the page source should not contain "<title>Hakuna Matata</title>"
  And the page html should not contain "<title>Hakuna Matata</title>"

#Scenario: Changing focus to iframe
#  Given I am on "http://the-internet/iframe"
#  Then I should not see "Your content goes here."
#  When I switch to iframe "mce_0_ifr"
#  Then I should see "Your content goes here."
#  When I go back to the main frame
#  Then I should not see "Your content goes here."
