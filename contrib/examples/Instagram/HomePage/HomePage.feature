@instagram @br @us @pt @de
Feature: Check all the language on the HomePage

  Background:
    Given the site url is "${site_url}"
    And I go to "${lang_url}"
    And I wait until "${content_loaded}"

  @regression @smoke
  Scenario: Open Instagram home page and check slogan
    Then I should see "${slogan}"
    Then I set the visual checkpoint "HomePageSlogan${lang_url}" ignoring the elements:
    | ._klk8w img                              |
    | //form[@class='_3bqd5']/*[not(self::h2)] |
    | ._dyp7q                                  |
    | ._m8ogu                                  |
    | footer                                   |

  @smoke
  Scenario: Open Instagram home page and fill in register form
    Then I fill in "${sign_up_e-mail}" with "aboscatto@ciandt.com"
    And I fill in "${sign_up_full_name}" with "Andre Boscatto"
    And I fill in "${sign_up_username}" with "AndreBoscattoCiandt"
    And I fill in "${sign_up_password}" with "${password}"
    Then I set the visual checkpoint "HomePageSignUp${lang_url}" ignoring the elements:
    | ._klk8w img |
    Then I Rotate User Login Twice
    And I set the visual checkpoint "HomePageSignUpRotated${lang_url}" ignoring the elements:
    | ._klk8w img |
