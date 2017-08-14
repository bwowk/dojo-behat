<?php

namespace Ciandt\Behat\PlaceholdersExtension\Transformation;

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Behat\Transformation\Transformation;
use Ciandt\Behat\PlaceholdersExtension\Config\PlaceholdersRepository;
use Ciandt\Behat\PlaceholdersExtension\PlaceholderContainer\PlaceholderContainer;
/**
 *
 * @author bwowk
 */
interface PlaceholdersTransformation extends Transformation {
    
   
    public static function supportsArgument($argument);
    
    public static function transformArgument($argument, $repository);
    
}
