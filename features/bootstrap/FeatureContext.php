<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Ciandt\Calc;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }
    private $calculator;

    /**
     * @Given I have a calculator
     */
    public function iHaveCalculator()
    {
        $this->calculator = new Calc();
    }

    /**
     * @When I sum :arg1 and :arg2
     * @When I sum :arg1 and :arg2 and :$arg3
     */
    public function iSumAnd($arg1, $arg2, $arg3 = null)
    {
        $this->calculator->doOp('+',$arg1);
        $this->calculator->doOp('+',$arg2);
    }

    /**
     * @Then The result should be :arg1
     */
    public function theResultShouldBe($arg1)
    {
        $result = $this->calculator->equals();
        if($result != $arg1){
          throw new Exception("Deu ruim");
        }
    }

}
