<?php

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Ciandt\Gameboy;


class FeatureContext implements Context
{
  private $gameboy = null;


  /**
   * [iHaveAGameboy description]
   * @Given I have a Gameboy
   */
  public function iHaveAGameboy(){
    $this->gameboy = new Gameboy();
  }

  /**
   * [iChargeTheGameboy description]
   * @Given I charge it
   * @Given I charge the Gameboy
   */
  public function iChargeTheGameboy(){
    $this->gameboy->charge();
  }

  /**
   * [iPutTheCartridge description]
   * @param  [type] $cartridge [description]
   * @When I put the :game cartridge
   */
  public function iPutTheCartridge($game) {
      $this->gameboy->insertCartridge(Gameboy::CARTRIDGES[$game]);
  }

  /**
   * [iTurnTheGameboyOn description]
   * @When I turn it on
   * @When I turn the Gameboy on
   */
  public function iTurnTheGameboyOn(){
    $this->gameboy->switch_on();
  }

  /**
   * [itShouldHaveThePokemon description]
   * @Then it should have the pokemon :name
   */
  public function itShouldHaveThePokemon($name) {
    Assert::assertContains($name, $this->gameboy->getPokemons());
  }

  /**
   * @BeforeScenario @charged
   */
  public function charged() {
    $this->iHaveAGameboy();
    $this->gameboy->charge();
  }

  /**
   *@BeforeScenario @pokemonRed
   */
  public function insertRed(){
    $this->iPutTheCartridge('Pokemon_Red');
  }

  /**
   *@BeforeScenario @pokemonBlue
   */
  public function insertBlue(){
    $this->iPutTheCartridge('Pokemon_Blue');
  }

  /**
   *@BeforeScenario @pokemonYellow
   */
  public function insertYellow(){
    $this->iPutTheCartridge('Pokemon_Yellow');
  }


}
