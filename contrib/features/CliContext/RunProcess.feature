Feature: Running command line processes

 Background:
    Given a file named "behat.yml" with:
      """
      default:
          suites:
              default:
                  contexts:
                      - Ciandt\CliContext: ~
      """



  Scenario: Run a single successful process
    Given a file named "features/run_process.feature" with:
      """
      Feature: Run process

      Scenario: Run process
        Given I start the process "P1" with "pwd"
        Then it should exit with success
      """
    When I run "behat features/run_process.feature"
    Then it should pass

  Scenario: Run a single failing process
    Given a file named "features/run_process.feature" with:
      """
      Feature: Run process

      Scenario: Run process
        Given I start the process P1 with "inexistent-command"
        Then it should exit with code 127
      """
    When I run "behat features/run_process.feature"
    Then it should pass