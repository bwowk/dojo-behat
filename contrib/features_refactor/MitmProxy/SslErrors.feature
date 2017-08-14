Feature: Test inalid ssl certificates through mitmproxy

Scenario Outline: Testing invalid SSL certificates
Given I am on "https://<error>.badssl.com/"
Then I should see "<error>" in the "#content" element
Scenarios:
| error       |
| wrong.host  |
| self-signed |
| expired     |
