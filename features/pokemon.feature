@pokemon
Feature: Pokemon games on Gameboy Color

  #Background:
    #Given I have a Gameboy

  @pokemonRed @pokemonBlue @pokemonYellow @charged
  Scenario: The Pokemon edition should have a exclusive pokemon_edition
  #  When I put the "${pokemon_edition}" cartridge
    And I turn the Gameboy on
    Then it should have the pokemon "${pokemon_Name}"

  #Scenario: Should warn about dead batteries
  #  And I turn the Gameboy on
  #  Then it should warn me about dead batteries
