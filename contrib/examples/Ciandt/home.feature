Feature: Test if the correct yml is being loaded

  Background:
    Given the site url is "${site_url}"
    And I go to "${lang_url}"

  @ciandt @br
  Scenario: Open Ciandt home page
    Given I am on "/home"
    And I wait until "${content_loaded}"
    When I switch to iframe "home-frame"
    Then I should see "${slogan}"

  @ciandt @us
  Scenario: Open Ciandt home page
    Given I am on "/home"
    And I wait until "${content_loaded}"
    When I switch to iframe "home-frame"
    Then I should see "${slogan}"
