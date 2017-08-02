Feature: Calc operations

  Background: I have a calculator
    Given I have a calculator

  Scenario: Calc sum two numbers
    When I sum 2 and 3
    Then The result should be 5

  @smoke @KWPH-15
  Scenario Outline: Sum operations
    When I sum <x> and <y>
    Then The result should be <z>

    Examples:
    | x  | y  | z |
    | 1  | 3  | 4 |
    | -1 | 10 | 9 |


  @regression
  Scenario: Calculator history is printed correctly
    When I sum 1 and 1
    And I press equals
    Then It should print:
    """
    + 1
    + 1
    %s
    2
    """

  Scenario: Calculator can do mixed operations
    When I do the operations:
    |-|3|
    |*|2|
    Then The result should be -6
