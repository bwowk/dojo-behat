Feature: Testing debug steps

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



  Scenario: Print current url
    Given a file named "features/printUrl.feature" with:
      """
      Feature: Print current url

      Scenario: Print current url
        Given I am on "http://the-internet/"
        Then print current URL
      """
    When I run "behat features/printUrl.feature"
    Then it should pass with:
      """
      │ http://the-internet/
      """

  Scenario: Print page HTML
    Given a file named "features/printHtml.feature" with:
      """
      Feature: Print page HTML

      Scenario: Print page HTML
        Given I am on "http://the-internet/"
        Then print last response
      """
    When I run "behat features/printHtml.feature"
    Then it should pass with:
      """
            │ <h2>Available Examples</h2>
            │ <ul>
            │   <li><a href="/abtest">A/B Testing</a></li>
            │   <li><a href="/basic_auth">Basic Auth</a> (user and pass: admin)</li>
            │   <li><a href="/broken_images">Broken Images</a></li>
            │   <li><a href="/challenging_dom">Challenging DOM</a></li>
            │   <li><a href="/checkboxes">Checkboxes</a></li>
      """

  Scenario: Print page text
    Given a file named "features/printText.feature" with:
      """
      Feature: Print page text

      Scenario: Print page text
        Given I am on "http://the-internet/"
        And I echo the page's text
      """
    When I run "behat features/printText.feature"
    Then it should pass with:
      """
      Welcome to the Internet Available Examples A/B Testing Basic Auth (user and pass: admin) Broken Images Challenging DOM Checkboxes Context Menu Disappearing Elements Drag and Drop Dropdown Dynamic Content Dynamic Controls Dynamic Loading Exit Intent File Download File Upload Floating Menu Forgot Password Form Authentication Frames Geolocation Horizontal Slider Hovers Infinite Scroll JQuery UI Menus JavaScript Alerts JavaScript onload event error Key Presses Large & Deep DOM Multiple Windows Nested
      """

  Scenario: Print open windows list
    Given a file named "features/printWindows.feature" with:
      """
      Feature: Print open windows

      Scenario: Print open windows
        Given I am on "http://the-internet/windows"
        And I click on "//a[text()='Click Here']"
        And I list all open windows
      """
    When I run "behat features/printWindows.feature"
    Then it should pass with:
      """
      The Internet | New Window
      """
