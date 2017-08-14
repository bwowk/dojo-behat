Feature: Running tests which open new browser windows

Scenario: I should not be able to interact with a new window without changing to it
Given I am on "http://the-internet/windows"
When I click on "//a[text() = 'Click Here']"
Then I should see "Powered by Elemental Selenium"
Then I close the current window

Scenario: I should be able to interact with a new window after changing to it
Given I am on "http://the-internet/windows"
When I click on "//a[text() = 'Click Here']"
When I switch to the window "New Window"
Then I should see "New Window"
But I should not see "Powered by Elemental Selenium"
When I close the current window

Scenario: I should be able to close a window
Given I am on "http://the-internet/windows"
When I click on "//a[text() = 'Click Here']"
Then I should see a window with title "New Window"
When I switch to the window "New Window"
And I close the current window
Then I should not see a window with title "New Window"