Feature: Test user interactions

 Background:
    Given a file named "behat.yml" with:
      """
      imports:
          - '/code/contrib/behat.contrib.yml'

      default:
          suites:
              default:
                  contexts:
                      - Ciandt\WebContext: ~
      """

  Scenario: Filling form fields
    Given a file named "features/fillField.feature" with:
      """
      Feature: Filling form fields

        Scenario: Filling form fields
          Given I am on "http://the-internet/login"
          When I fill in "#username" with "tomsmith"
          And I fill in "SuperSecretPassword!" for "#password"
          And I click on "#login button"
          Then I should see "Welcome to the Secure Area."
      """
    When I run "behat features/fillField.feature"
    Then it should pass

# Failing because of timeout on page
#  Scenario: Filling form fields with PyStrings
#    Given a file named "features/fillFieldPystring.feature" with:
#      """
#      Feature: Filling form fields with PyStrings
#
#        Scenario: Filling form fields with PyStrings
#          Given I am on "http://the-internet/tinymce"
#          When I switch to iframe "mce_0_ifr"
#          And I fill in "(?P<selector>[^"]*)" with:
#            '''
#            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean velit dui, scelerisque imperdiet nunc aliquet, ultricies mattis lacus. Duis enim diam, interdum ac posuere eu, facilisis et magna. Pellentesque ultrices, eros in varius vehicula, justo odio fringilla tortor, vitae porta purus risus non odio. Phasellus congue mi ac vestibulum finibus. !@#$%*()_+1234567890-='"´[]{}`~^]]}
#            '''
#          Then I should see:
#            '''
#            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean velit dui, scelerisque imperdiet nunc aliquet, ultricies mattis lacus. Duis enim diam, interdum ac posuere eu, facilisis et magna. Pellentesque ultrices, eros in varius vehicula, justo odio fringilla tortor, vitae porta purus risus non odio. Phasellus congue mi ac vestibulum finibus. !@#$%*()_+1234567890-='"´[]{}`~^]]}
#            '''
#      """
#    When I run "behat features/fillFieldPystring.feature"
#    Then it should pass

  Scenario: Filling multiple form fields
    Given a file named "features/fillMultipleFields.feature" with:
      """
      Feature: Filling multiple form fields

        Scenario: Filling multiple form fields
          Given I am on "http://the-internet/login"
          And I fill in the following:
            | #username | tomsmith             |
            | #password | SuperSecretPassword! |
          And I click on "#login button"
          Then I should see "Welcome to the Secure Area."
      """
    When I run "behat features/fillMultipleFields.feature"
    Then it should pass

  Scenario: Asserting form field contents
    Given a file named "features/AssertFieldContent.feature" with:
      """
      Feature: Asserting form field contents

        Scenario: Asserting form field contents
          Given I am on "http://the-internet/login"
          When I fill in "#username" with "Hakuna Matata"
          Then the "#username" field should contain "Hakuna Matata"
          But the "#username" field should not contain "Lakuna Batata"
      """
    When I run "behat features/AssertFieldContent.feature"
    Then it should pass

   Scenario: Select Options
    Given a file named "features/Select.feature" with:
      """
      Feature: Select Options

        Scenario: Select Options
          Given I am on "http://the-internet/dropdown"
          When I select "Option 2" from "#dropdown"
          Then the "#dropdown" field should contain "2"
      """
    When I run "behat features/Select.feature"
    Then it should pass

  Scenario Outline: Asserting checkbox state failing
    Given a file named "features/AssertCheckbox.feature" with:
      """
      Feature: Asserting checkbox state

        Scenario: Asserting checkbox state
          Given I am on "http://the-internet/checkboxes"
          Then the checkbox "//input[@type='checkbox'][<n>]" should be <desired_state>
      """
    When I run "behat features/AssertCheckbox.feature"
    Then it should <result> with:
      """
      <output>
      """
  Examples:
  | desired_state | n | result | output                                                               |
  | checked       | 1 | fail   | The checkbox //input[@type='checkbox'][1] is not checked (Exception) |
  | unchecked     | 2 | fail   | The checkbox //input[@type='checkbox'][2] is checked (Exception)     |
  | checked       | 2 | pass   | 2 steps (2 passed)                                                   |
  | unchecked     | 1 | pass   | 2 steps (2 passed)                                                   |

  Scenario Outline: Checking and unchecking checkbox
    Given a file named "features/CheckCheckbox.feature" with:
      """
      Feature: Checking and unchecking checkbox

        Scenario: Checking and unchecking checkbox
          Given I am on "http://the-internet/checkboxes"
          When I <action> "//input[@type='checkbox'][<n>]"
          Then the checkbox "//input[@type='checkbox'][<n>]" should be <desired_state>
      """
    When I run "behat features/CheckCheckbox.feature"
    Then it should pass
      """
      <output>
      """
  Examples:
  | action  | desired_state | n | result |
  | check   | checked       | 1 | pass   |
  | uncheck | unchecked     | 2 | pass   |

  Scenario: Hovering a element
    Given a file named "features/Hover.feature" with:
      """
      Feature: Hovering a element

        Scenario: Hovering a element
          Given I am on "http://the-internet/hovers"
          Then I should not see "name: user1"
          When I hover over "//div[@class='figure'][1]"
          Then I should see "name: user1"
      """
    When I run "behat features/Hover.feature"
    Then it should pass


  Scenario Outline: Clicking on a element
    Given a file named "features/Click.feature" with:
      """
      Feature: Clicking on a element

        Scenario: Clicking on a element
          Given I am on "http://the-internet/dynamic_loading/1"
          When I <action> "#start button"
          Then I should see "Loading..."
      """
    When I run "behat features/Click.feature"
    Then it should pass
  Examples:
  | action   |
  | click on |
  | press    |
  | follow   |


  Scenario: Run JavaScript
    Given a file named "features/JavaScript.feature" with:
      """
      Feature: Run JavaScript

        Scenario: Run JavaScript
          When I run js "document.body.innerHTML = 'Hello JavaScript';"
          Then I should see "Hello JavaScript"
      """
    When I run "behat features/JavaScript.feature"
    Then it should pass
