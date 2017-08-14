Feature: Test basic authentication through mitmproxy

Scenario: Basic authentication through mitmproxy
Given I go to "http://the-internet/basic_auth"
Then I should see "Congratulations! You must have the proper credentials."