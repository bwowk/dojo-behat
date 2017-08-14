<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Ciandt\Behat\PlaceholdersExtension\Tester;

use Behat\Behat\Tester\Result\StepResult;
use Behat\Behat\Tester\StepTester;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Tester\Setup\SuccessfulSetup;
use Behat\Testwork\Tester\Setup\SuccessfulTeardown;

/**
 * Description of ResultPlaceholderReplacerStepTester
 *
 * @author bwowk
 */
class ResultPlaceholderReplacerStepTester implements StepTester
{
    
    private $baseTester;
    
    function __construct(StepTester $baseTester)
    {
        $this->baseTester = $baseTester;
    }

    //put your code here
    public function setUp(Environment $env, FeatureNode $feature, StepNode $step, $skip)
    {
        return new SuccessfulSetup();
    }

    public function tearDown(Environment $env, FeatureNode $feature, StepNode $step, $skip, StepResult $result)
    {
        return new SuccessfulTeardown();
    }

    public function test(Environment $env, FeatureNode $feature, StepNode $step, $skip)
    {
        $result = $this->baseTester->test($env, $feature, $step, $skip);
    }
}
