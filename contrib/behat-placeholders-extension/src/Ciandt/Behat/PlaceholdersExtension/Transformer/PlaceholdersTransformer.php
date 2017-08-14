<?php

namespace Ciandt\Behat\PlaceholdersExtension\Transformer;

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Behat\Transformation\Transformer\ArgumentTransformer;
use Ciandt\Behat\PlaceholdersExtension\Config\PlaceholdersRepository;
use Ciandt\Behat\PlaceholdersExtension\Subscriber\BeforeScenarioSubscriber;
use Ciandt\Behat\PlaceholdersExtension\Transformation\RuntimePlaceholdersTransformation;

/**
 * Transforms a single argument value.

 */
class PlaceholdersTransformer implements ArgumentTransformer
{
   
    private $repository;
    
    public function __construct(PlaceholdersRepository $repository) {
        $this->repository = $repository;
    }

    
    public function supportsDefinitionAndArgument(DefinitionCall $definitionCall, $argumentIndex, $argumentValue) {
        return RuntimePlaceholdersTransformation::supportsArgument($argumentValue);        
    }

    public function transformArgument(DefinitionCall $definitionCall, $argumentIndex, $argumentValue) {
        if (RuntimePlaceholdersTransformation::supportsArgument($argumentValue)){
            return RuntimePlaceholdersTransformation::transformArgument($argumentValue,$this->repository);
        }
    }

}
