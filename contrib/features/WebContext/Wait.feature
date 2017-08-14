Feature: Test waiting for specific conditions

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

  Scenario: Waiting for hidden element to appear
    Given a file named "features/WaitHiddenElement.feature" with:
      """
      Feature: Waiting for an hidden element to appear

        Scenario: Waiting for an hidden element to appear
          Given I am on "http://the-internet/dynamic_loading/1"
          When I click on "//button[text()='Start']"
          Then I should not see "Hello World!"
          When I wait until "//div[@id='finish']" appears
          Then I should see "Hello World!"
      """
    When I run "behat features/WaitHiddenElement.feature"
    Then it should pass


  Scenario: Waiting for an nonexistent element to be created
    Given a file named "features/WaitElementCreation.feature" with:
      """
      Feature: Waiting for an nonexistent element to be created

        Scenario: Waiting for an nonexistent element to be created
          Given I am on "http://the-internet/dynamic_loading/2"
          When I click on "//button[text()='Start']"
          Then I should not see "Hello World!"
          When I wait until "//div[@id='finish']" appears
          Then I should see "Hello World!"
      """
    When I run "behat features/WaitElementCreation.feature"
    Then it should pass


  Scenario: Waiting for an element to disappear
    Given a file named "features/WaitElementDisappear.feature" with:
      """
      Feature: Waiting for an element to disappear

        Scenario: Waiting for an element to disappear
          Given I am on "http://the-internet/dynamic_loading/2"
          When I click on "//button[text()='Start']"
          Then I should not see "Hello World!"
          When I wait until "//div[@id='loading']" disappears
          Then I should see "Hello World!"
      """
    When I run "behat features/WaitElementDisappear.feature"
    Then it should pass

  Scenario: Timeout while waiting for an element to disappear
    Given a file named "features/WaitElementDisappearTimeout.feature" with:
      """
      Feature: Timeout while waiting for an element to disappear

        Scenario: Timeout while waiting for an element to disappear
          Given I am on "http://the-internet/dynamic_loading/2"
          When I click on "//button[text()='Start']"
          Then I should not see "Hello World!"
          When I wait at most 1 second until "//div[@id='loading']" disappears
          Then I should see "Hello World!"
      """
    When I run "behat features/WaitElementDisappearTimeout.feature"
    Then it should fail with:
      """
      Timeout while waiting for "//div[@id='loading']" to disappear. (Exception)
      """

  Scenario: Timeout while waiting for an element to appear
    Given a file named "features/WaitElementAppearTimeout.feature" with:
      """
      Feature: Timeout while waiting for an element to appear

        Scenario: Timeout while waiting for an element to appear
          Given I am on "http://the-internet/dynamic_loading/2"
          When I click on "//button[text()='Start']"
          Then I should not see "Hello World!"
          When I wait at most 1 second until "//div[@id='finish']" appears
          Then I should see "Hello World!"
      """
    When I run "behat features/WaitElementAppearTimeout.feature"
    Then it should fail with:
      """
      Timeout while waiting for "//div[@id='finish']" to appear. (Exception)
      """

  Scenario: Waiting for js condition
    Given a file named "features/WaitJs.feature" with:
      """
      Feature: Waiting for js condition

        Scenario: Waiting for js condition
          Given I am on "http://the-internet/dynamic_loading/2"
          When I click on "//button[text()='Start']"
          Then I should not see "Hello World!"
          When I wait until "document.getElementById('finish') != null"
          Then I should see "Hello World!"
      """
    When I run "behat features/WaitJs.feature"
    Then it should pass



  Scenario: Timeout while waiting for js condition
    Given a file named "features/WaitJsTimeout.feature" with:
      """
      Feature: Timeout while waiting for js condition
        Scenario: Timeout while waiting for js condition
          When I wait at most 1 second until "false"
      """
    When I run "behat features/WaitJsTimeout.feature"
    Then it should fail with:
      """
      Timeout while waiting for "false" (Exception)
      """
