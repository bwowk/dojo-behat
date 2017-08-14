<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ciandt\Behat\PlaceholdersExtension\Subscriber;

use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Description of BeforeScenarioSubscriber
 *
 * @author bwowk
 */
class BeforeScenarioSubscriber implements EventSubscriberInterface
{
    
    private $scenarioTags;
    
    public static function getSubscribedEvents()
    {
        return array(
            ScenarioTested::BEFORE => 'onBeforeScenarioTested',
            OutlineTested::BEFORE => 'onBeforeScenarioTested'
            );
    }
    
    public function onBeforeScenarioTested($beforeScenario){
        $featureTags = $beforeScenario->getFeature()->getTags();
        $scenarioTags = $beforeScenario->getNode()->getTags();
        $this->scenarioTags = array_merge($featureTags, $scenarioTags);
    }
    
    public function getScenarioTags()
    {
        return $this->scenarioTags;
    }


}
