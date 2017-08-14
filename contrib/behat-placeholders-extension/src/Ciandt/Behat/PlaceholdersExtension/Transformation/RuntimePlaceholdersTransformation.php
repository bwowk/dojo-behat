<?php


namespace Ciandt\Behat\PlaceholdersExtension\Transformation;

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Call\RuntimeCallee;
use Ciandt\Behat\PlaceholdersExtension\Config\PlaceholdersRepository;
use Ciandt\Behat\PlaceholdersExtension\PlaceholderContainer\PlaceholderContainer;
use Ciandt\Behat\PlaceholdersExtension\PlaceholderContainer\PlaceholderContainerStepNode;

/**
 *
 */
 class RuntimePlaceholdersTransformation extends RuntimeCallee implements PlaceholdersTransformation {

    public function __toString() {
        return 'UserDefinedPlaceholderTransform';
    }

    public function getPattern() {
        return PlaceholdersRepository::PLACEHOLDER_REGEX;
    }

    protected static function replaceStringPlaceholders(&$string, $repository) {
        preg_match_all(PlaceholdersRepository::PLACEHOLDER_REGEX, $string, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $placeholder = $match['placeholder'];
            $replacement = $repository->getReplacement($placeholder);
            $string = str_replace('${' . $placeholder . '}', $replacement, $string);
        }
        return $string;
    }

    public static function transformArgument($argument, $repository) {
        if (is_string($argument)) {
            return self::replaceStringPlaceholders($argument, $repository);
        }

        if ($argument instanceof PyStringNode) {
            $strings = $argument->getStrings();
            foreach ($strings as &$string){
                self::replaceStringPlaceholders($string, $repository);
            }
            return new PyStringNode($strings, $argument->getLine());
        }
        
        if ($argument instanceof TableNode) {
            $table = $argument->getTable();
            array_walk_recursive($table, 'self::replaceTablePlaceholders', $repository);
            return new TableNode($table);
        }
    }
    
    private static function replaceTablePlaceholders(&$item, $key, $repository){
        $item = self::replaceStringPlaceholders($item, $repository);
    }
    

    public static function supportsArgument($argument)
    {
        if (is_string($argument) && preg_match(PlaceholdersRepository::PLACEHOLDER_REGEX, $argument) === 1) {
            return true;
        }

        if ($argument instanceof PyStringNode &&
            preg_match(PlaceholdersRepository::PLACEHOLDER_REGEX, $argument->getRaw()) === 1) {
            return true;
        }
        
        if ($argument instanceof TableNode &&
            preg_match(PlaceholdersRepository::PLACEHOLDER_REGEX, $argument->getTableAsString()) === 1) {
            return true;
        }
        
        return false;
    }
}
