<?php

namespace Ciandt;


class Gameboy
{

    private $state = self::STATE_OFF;
    private $charge = self::CHARGE_EMPTY_BATTERY;
    private $cartridge = null;

    const STATE_OFF = 0;
    const STATE_ON = 1;
    const CHARGE_EMPTY_BATTERY = 0;
    const CHARGE_LOW_BATTERY = 1;
    const CHARGE_MED_BATTERY = 2;
    const CHARGE_FULL_BATTERY = 3;

    const CARTRIDGES = array(
      "Pokemon_Yellow" => array(
        'extra_pokemons' => array(
          'Sandshrew','Sandslash','Vulpix','Ninetales','Oddish','Gloom',
          'Vileplume','Mankey','Primeape','Growlithe','Arcanine','Bellsprout',
          'Weepinbell','Victreebel','Scyther'
        )
      ),
      "Pokemon_Red" => array(
        'extra_pokemons' => array(
          'Weedle','Kakuna','Beedrill','Ekans','Arbok','Raichu','Oddish',
          'Gloom','Vileplume','Mankey','Primeape','Growlithe','Arcanine',
          'Koffing','Weezing','Scyther','Jynx','Electabuzz','Pinsir'
        )
      ),
      "Pokemon_Blue" => array(
        'extra_pokemons' => array(
          'Weedle','Kakuna','Beedrill','Raichu','Sandshrew','Sandslash',
          'Oddish','Gloom','Vileplume','Meowth','Persian','Growlithe',
          'Arcanine','Koffing','Weezing','Scyther','Jynx','Pinsir'
        )
      ),
    );

    public function switch_on() {
      if ($this->charge === self::CHARGE_EMPTY_BATTERY){
        throw new \Exception("Dead batteries. Cannot turn on.");
      }
      $this->state = self::STATE_ON;
    }

    public function insertCartridge($cartridge) {
      $this->cartridge = $cartridge;
    }

    public function charge() {
      if ($this->state != self::CHARGE_FULL_BATTERY) {
        $this->charge = self::CHARGE_FULL_BATTERY;
      }
    }

    public function getPokemons() {
      if ($this->cartridge === null) {
        throw new \Exception("Insert a cartridge.");
      }
      return $this->cartridge-['extra_pokemons'];
    }



}
