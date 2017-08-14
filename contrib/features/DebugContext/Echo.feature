Feature: Testing DebugContext

 Background:
    Given a file named "behat.yml" with:
      """
      imports:
          - '/code/contrib/behat.contrib.yml'

      default:
          suites:
              default:
                  contexts:
                      - Ciandt\DebugContext: ~
      """

  Scenario: Pause
    Given a file named "features/pause.feature" with:
      """
      Feature: Pause

      Scenario: Print open windows
        Then pause
      """
    When I answer "Enter" when running "behat features/pause.feature"
    Then it should pass with:
      """
      Paused. Press [Enter] to continue.
      """

  Scenario: Echo string
    Given a file named "features/echo.feature" with:
      """
      Feature: Echo string

      Scenario: Echo string
        Then I echo "The brown fox jumps over the lazy dog"
      """
    When I run "behat features/echo.feature"
    Then it should pass with:
      """
      The brown fox jumps over the lazy dog

      1 scenario (1 passed)
      """
