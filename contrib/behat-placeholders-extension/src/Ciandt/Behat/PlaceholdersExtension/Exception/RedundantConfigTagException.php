<?php

namespace Ciandt\Behat\PlaceholdersExtension\Exception;

use RuntimeException;

/**
 * Description of RedundantConfigTagException
 *
 * @author bwowk
 */
class RedundantConfigTagException extends RuntimeException{
    public function __construct($tags) {
        $message = "Multiple config tags found. Should have only one: " . implode(', ', $tags);
        parent::__construct($message);
    }
}
