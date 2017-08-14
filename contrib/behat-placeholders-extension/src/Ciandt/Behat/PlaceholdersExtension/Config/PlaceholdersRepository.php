<?php
namespace Ciandt\Behat\PlaceholdersExtension\Config;

use Symfony\Component\Yaml\Yaml;
use Ciandt\Behat\PlaceholdersExtension\Utils\PlaceholderUtils;
use Ciandt\Behat\PlaceholdersExtension\PlaceholderContainer\PlaceholderContainer;
use Ciandt\Behat\PlaceholdersExtension\Exception\MissingSectionException;
use Ciandt\Behat\PlaceholdersExtension\Exception\UndefinedPlaceholderException;
use Exception;

/**
 * Description of ConfigsRepository
 *
 * @author bwowk
 */
class PlaceholdersRepository
{
    const PLACEHOLDER_REGEX = '/\${(?P<placeholder>[a-zA-Z0-9_-]+)}/';
    
    private $configs;
    
    private $beforeScenarioSubscriber;
    
    private $placeholders = array();

    /**
     * @var string
     */
    private $environment;

    public function __construct($configs_mapping, $beforeScenarioSubscriber)
    {
        $this->configs = $this->loadConfigFiles($configs_mapping);
        $this->beforeScenarioSubscriber = $beforeScenarioSubscriber;
    }

    /**
     *
     * @return string[]
     * @todo read configs and also bring alternative @config:section tags
     */
    public function getTags()
    {
        return array_keys($this->configs);
    }

    /**
     * reads the YAML placeholder definitions and returns an associative array
     * 
     * @param type $config_files
     * @todo user %paths.base% value
     */
    private function loadConfigFiles($configs_mapping)
    {
        $placeholder_maps = array();
        foreach ($configs_mapping as $tag => $file_path) {
            $placeholder_maps[$tag]['config'] = Yaml::parse(file_get_contents($file_path));
            $placeholder_maps[$tag]['path'] = $file_path;
        }
        return $placeholder_maps;
    }

    public function getConfig($key)
    {
        if (key_exists($key, $this->configs)) {
            return $this->configs[$key]['config'];
        }
        return null;
    }

    public function getFilePath($key)
    {
         if (key_exists($key, $this->configs)) {
            return $this->configs[$key]['path'];
        }
        return null;
    }
       
    public function getEnvironment()
    {
        return $this->environment;
    }

    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }
    
    private function getScenarioTags(){
        return $this->beforeScenarioSubscriber->getScenarioTags();
    }


    public function getReplacement($placeholder, $replaced = array()) {
        
        // if the current placeholder was already replaced before, this is a cyclic dependecy
        if (in_array($placeholder, $replaced)){
            $tree = implode('>', $replaced);
            throw new Exception("Cyclic placeholder dependecy detected. Trying to replace $placeholder again when already replaced: $tree");
        }
        
        // if there's a runtime placeholder defined for that key, return it right away
        if (array_key_exists($placeholder, $this->placeholders)) {
            return $this->placeholders[$placeholder];
        }
        
        $tags = $this->getScenarioTags();
        
        //@todo abort if there's no config tag
        $configTag = PlaceholderUtils::getConfigTag($tags);
        $configKey = PlaceholderUtils::getConfigKey($configTag);
        $section = PlaceholderUtils::getSectionKey($configTag);
        $placeholders = $this->getSectionPlaceholders($configKey, $section);
        
        $variantTags = PlaceholderUtils::filterVariantTags($tags, false);
        $variant = end($variantTags);
        $environment = $this->getEnvironment();
        $configPath = $this->getFilePath($configKey);
        $keys = array('$' . $variant, '$' . $environment, $placeholder);
        $treePosition = "$configPath>$section>placeholders";

        $replacement = $this->recursivePlaceholderSearch($keys, $placeholders, $treePosition);
        
        if (is_null($replacement)){
            throw new UndefinedPlaceholderException("No $placeholder placeholder was defined on runtime or on $treePosition>$placeholder for variant $variant and environment $environment");
        } 
        
        //if the replaced string doesn't have placeholders itself
        if (preg_match_all(self::PLACEHOLDER_REGEX, $replacement, $matches, PREG_SET_ORDER) == 0){
            return $replacement;
        }
        
        //if it does have, replace them before returning
        foreach ($matches as $match) {
            $replaced[] = $placeholder;
            $replacement = str_replace('${' . $match['placeholder'] . '}', $this->getReplacement($match['placeholder'], $replaced), $replacement);
        }
        return $replacement;
        
    }
    
    private function recursivePlaceholderSearch($keys, $values, $treePosition)
    {
        if (empty($keys) || is_scalar($values)) {
            return $values;
        }
        $key = array_pop($keys);
        if (key_exists($key, $values)) {
            $specificValue = $this->recursivePlaceholderSearch($keys, $values[$key], "$treePosition>$key");
            if ($specificValue) {
                return $specificValue;
            }
        } if (key_exists('$default', $values)) {
            $defaultValue = $this->recursivePlaceholderSearch($keys, $values['$default'], $treePosition . '>$default');
            if ($defaultValue) {
                return $defaultValue;
            }
        } 
        
        return null;
    }
    
    private function getSectionPlaceholders($configKey, $section){
        $config = $this->getConfig($configKey);
        if (!isset($config) || !key_exists($section, $config)){
            throw new MissingSectionException(
                    $configKey,
                    $this->getFilePath($configKey),
                    $section);
        }
        return $config[$section]['placeholders'];
    }
    
    public function setPlaceholder($key, $value) {
        $this->placeholders[$key] = $value;
    }
            
}
