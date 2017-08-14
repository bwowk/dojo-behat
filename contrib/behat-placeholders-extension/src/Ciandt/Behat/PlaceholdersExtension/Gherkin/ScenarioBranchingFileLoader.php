<?php

namespace Ciandt\Behat\PlaceholdersExtension\Gherkin;

use Behat\Gherkin\Loader\GherkinFileLoader;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Ciandt\Behat\PlaceholdersExtension\Utils\PlaceholderUtils;

/**
 * Gherkin *.feature files loader.
 * Modified to branch Scenarios per variant.
 *
 * @author Bruno Wowk <bruno.wowk@gmail.com>
 */
class ScenarioBranchingFileLoader extends GherkinFileLoader
{
   
    /**
     * Parses feature at provided absolute path.
     *
     * @param string $path Feature path
     *
     * @return FeatureNode
     */
    protected function parseFeature($path)
    {

        $filename = $this->findRelativePath($path);
        $content = file_get_contents($path);
        $feature = $this->parser->parse($content, $filename);
        if (!($feature instanceof FeatureNode)) {
            return $feature;
        }
        $feature = $this->branchFeatureVariants($feature);
        return $feature;
    }
    
    /**
     * Branches Scenarios/Outlines with variant tags for each variant.
     *
     * @param FeatureNode $feature Parsed feature
     *
     * @return FeatureNode
     */
    private function branchFeatureVariants(FeatureNode $feature)
    {
        $scenarios = array();
        
        foreach ($feature->getScenarios() as $scenario) {
            $scenarios = array_merge($scenarios, $this->getBranchedScenario($scenario, $feature));
        }

        return new FeatureNode(
            $feature->getTitle(),
            $feature->getDescription(),
            $feature->getTags(),
            $feature->getBackground(),
            $scenarios,
            $feature->getKeyword(),
            $feature->getLanguage(),
            $feature->getFile(),
            $feature->getLine()
        );
    }
    
    private function getBranchedScenario(ScenarioInterface $scenarioLike, FeatureNode $feature)
    {
        $scenarioTags = $scenarioLike->getTags();
        $featureTags = $feature->getTags();
        $tags = array_merge($scenarioTags, $featureTags);
        
        $variants = PlaceholderUtils::filterVariantTags($tags, false);
        
        if (count($variants) <= 1) {
            return array($scenarioLike);
        } else {
            return $this->branchScenarioVariants($scenarioLike, $variants);
        }
        
    }
    
    private function branchScenarioVariants(ScenarioInterface $scenarioLike, $variants)
    {
        $scenarios = array();
        $nonVariantTags = PlaceholderUtils::filterVariantTags($scenarioLike->getTags(), true);
        foreach ($variants as $variant) {
            $tags = array_merge($nonVariantTags, array($variant));
            if ($scenarioLike instanceof ScenarioNode) {
                $scenarios[] = new ScenarioNode(
                    $scenarioLike->getTitle(),
                    $tags,
                    $scenarioLike->getSteps(),
                    $scenarioLike->getKeyword(),
                    $scenarioLike->getLine()
                );
            } elseif ($scenarioLike instanceof OutlineNode) {
                $scenarios[] = new OutlineNode(
                    $scenarioLike->getTitle(),
                    $tags,
                    $scenarioLike->getSteps(),
                    $scenarioLike->getExampleTable(),
                    $scenarioLike->getKeyword(),
                    $scenarioLike->getLine()
                );
            }
        }
        return $scenarios;
    }
}
