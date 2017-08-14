@tags
Feature: Scenario Outline Placeholders
  In order to avoid redundancy
  As a feature writer
  I need to use placeholders on scenario outlines

  Background:
    Given a file named "features/bootstrap/FeatureContext.php" with:
      """
      <?php
      use Behat\Behat\Context\Context;

      class FeatureContext implements Context
      {
          
        /**
         * @Given I like ":flavor" ice cream
         */
        public function gotIceCream($flavor) {
            $this->placeholders->setPlaceholder("flavor",$flavor);
        }
          
        /**
         * @Given the ice cream truck has ":flavor" ice cream
         */
        public function echoFlavor($flavor) {
            echo "It sells $flavor ice cream";
        }

      }
      """
    And a file named "behat.yml" with:
      """
      default:
          extensions:
              Ciandt\Behat\PlaceholdersExtension: ~
      """

    @test
    Scenario: Using runtime placeholders across scenarios
        Given a file named "features/runtime_placeholder.feature" with:
          """
          Feature: Using runtime placeholders across scenarios
            Scenario: I like a specific ice cream flavor
              Given I like "vanilla" ice cream

            Scenario: The ice cream truck sells that flavor
              Given the ice cream truck has "${flavor}" ice cream
          """

        When I run "behat features/runtime_placeholder.feature"
        Then it should pass with:
          """
            Scenario: The ice cream truck sells that flavor       # features/runtime_placeholder.feature:5
              Given the ice cream truck has "${flavor}" ice cream # FeatureContext::echoFlavor()
                │ It sells vanilla ice cream
          """

