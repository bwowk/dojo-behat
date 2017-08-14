<?php

namespace Ciandt\Behat\PlaceholdersExtension\Exception;

use RuntimeException;
use Ciandt\Behat\PlaceholdersExtension\PlaceholderContainer\PlaceholderContainer;
/**
 * Description of MissingSectionException
 *
 * @author bwowk
 */
class MissingSectionException extends RuntimeException{
    
        
    public function __construct($configKey, $configPath, $section) {
        $message = "Section $configKey:$section doesn't exist on $configPath";
        parent::__construct($message);
    }

    
}
