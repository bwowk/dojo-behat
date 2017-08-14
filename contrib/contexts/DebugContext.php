<?php
namespace Ciandt;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

class DebugContext implements Context
{

    const YELLOW = "\e[1;33m";
    const RED = "\e[1;31m";

    /**
     * Pause scenario execution and wait
     * for user input to continue
     * @Then pause
     */
    public function pause()
    {
        $red = self::RED;
        fwrite(STDOUT, "{$red}Paused. Press [Enter] to continue.");
        fread(STDIN, 1024);
    }

    /**
     * Echo a string during a scenario execution
     * Very useful to debug placeholders
     * @Then I echo ":text"
     */
    public function echoString($text)
    {
        #print using ANSI colors
        $yellow = self::YELLOW;
        echo("{$yellow}$text");
    }
}
