@placeholders
Feature: Calc operations

  Background: I have a calculator
    Given I have a calculator

  @smoke @modelA @modelB @modelC
  Scenario: Calc sum two numbers
    When I sum "${two}" and 3
    Then The result should be 5

  @smoke @KWPH-15
  Scenario Outline: Sum operations
    When I sum <x> and <y>
    Then The result should be <z>

    Examples:
    | x  | y  | z |
    | 1  | 3  | 4 |
    | -1 | 10 | 9 |


  @KWPH-15
  Scenario: Calculator history is printed correctly
    When I sum 1 and 1
    Then the history should show result 2


  Scenario: Calculator can do mixed operations
    When I do the operations:
    |-|3|
    |*|2|
    Then The result should be -6
