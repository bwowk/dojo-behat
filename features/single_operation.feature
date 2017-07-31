Feature: Calc operations

  Background: I have a calculator
    Given I have a calculator

  Scenario: Calc sum two numbers
    When I sum 2 and 3
    Then The result should be 5

  Scenario Outline: Sum operations
    When I sum <x> and <y>
    Then The result should be <z>

    Examples:
    | x  | y  | z |
    | 1  | 3  | 4 |
    | -1 | 10 | 9 |
    | -1 | 10 | 4 |
    | -1 | 10 | 2 |
