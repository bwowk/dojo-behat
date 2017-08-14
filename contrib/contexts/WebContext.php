<?php
namespace Ciandt;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use BrowserStack\Local as BrowserstackLocal;
use Ciandt\Behat\VisualRegressionExtension\Definitions\VisualCheckpoint;
use \Exception as Exception;
use Imagick;
use RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Web pages testing context.
 *
 * @author Bruno Wowk <bwowk@ciandt.com>
 */
class WebContext extends RawMinkContext
{

    const YELLOW = "\e[1;33m";

    private $default_timeout;
    private $scenario_tags;

    public function __construct($default_timeout = 10)
    {
        $this->default_timeout = $default_timeout;
    }

    /**
     * @BeforeScenario
     * @global BrowserstackLocal $browserstackLocal
     */
    public function startBrowserstackLocal()
    {
        global $browserstackLocal;

        if ($this->getMink()->hasSession('browser_stack')
            && getenv('BROWSERSTACK_ACCESS_KEY')
            && (!isset($browserstackLocal)
                ||
            !$browserstackLocal->isRunning() )) {
            $browserstackLocal = new BrowserstackLocal();
            $browserstackLocal->start(array(
                "onlyAutomate" => true,
                "proxyHost" => "mitmproxy",
                "proxyPort" => "8080",
                "forceproxy" => true,
                "forcelocal" => true,
                "force" => true,
                "localIdentifier" => "docker-behat",
                "logfile" => '/home/behat/.browserstack/log.txt'
            ));

            #stop BrowserStackLocal process on shutdown
            register_shutdown_function(function () {
                global $browserstackLocal;
                if (isset($browserstackLocal) && $browserstackLocal->isRunning()) {
                    $browserstackLocal->stop();
                }
            });
        }
    }



    /**
     * Gets elements by css selector or xpath.
     *
     * @param string $locator The css or xpath selector.
     * @param bool   $single  If it should return a single result.
     *
     * @return ElementInterface|array  Element or array of elements.
     *
     * @throws ElementNotFoundException
     * @throws RuntimeException
     */
    protected function findElements($selector, $single = false)
    {
        $page = $this->getSession()->getPage();
        $results = array();
        $type;
        // If it starts with //, it's a xpath
        if (preg_match("/^\/\/.*|^\(.*\)\[(\d+|last\(\))\]$/", $selector) === 1) {
            $type = 'xpath';
        } else {
            $type = 'css';
        }

        $results = $page->findAll($type, $selector);

        if (count($results) === 0) {
            $driver = $this->getSession()->getDriver();
            throw new ElementNotFoundException($driver, null, $type, $selector);
        }

        if (!$single) {
            return $results;
        }

        if ($single && count($results) > 1) {
            throw new RuntimeException("More than one result found. You need to provide a more specific selector.");
        }

        return end($results);
    }

    protected function findElement($selector)
    {
        return $this->findElements($selector, true);
    }

    /**
     * Sets the site base url to use with relative paths.
     * Example: Given the site url is "http://batman.com".
     * Example: Given I set the site url to "https://www.battle.net".
     *
     * @Given the site url is ":url"
     * @Given I set the site url to ":url"
     */
    public function setBaseUrl($url)
    {
        $this->setMinkParameter('base_url', $url);
    }

    /**
     * Runs arbitrary JavaScript on the page
     *
     * @When /^(?:|I )run js "(?P<js>[^"]*)"$/
     */
    public function runJsOnElement($script)
    {
        $this->getSession()->executeScript($script);
    }

    /**
     * Clicks on the element specified by css selector or xpath
     * Example: When I click on "a[href='/faq']"
     * Example: And I click on "//input[text()='Submit']"
     * Example: When I follow "//a[text()='Privacy policy']"
     * Example: And I follow "#site-map"
     * Example: When I press "//button[text()='Log In']"
     * Example: And I press ".logout-button"
     *
     * @When /^(?:|I )click on "(?P<selector>[^"]*)"$/
     * @When /^(?:|I )press "(?P<selector>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )follow "(?P<selector>(?:[^"]|\\")*)"$/
     *
     */
    public function clickOnElement($selector)
    {
        $element = $this->findElement($selector);
        $element->click();
    }

    /**
     * Waits until boolean JavaScript expression resolves to true.
     * Example: When I wait until "-"
     * Example: When I wait at most 5 until "-"
     *
     * @When /^(?:|I )wait (at most (?P<seconds>\d+) seconds? )?until "(?P<condition>[^"]*)"$/
     */
    public function waitForJsCondition($condition, $seconds = null)
    {
        if (!$seconds) {
            $seconds = $this->default_timeout;
        }
        $result = $this->getSession()->wait(1000 * $seconds, $condition);
        if (!$result) {
            throw new \Exception("Timeout while waiting for \"$condition\"");
        }
    }

    /**
     * @When /^I switch to iframe "(?P<iframe_id>[^"]*)"$/
     * @When I go back to the main frame
     */
    public function switchToIframe($iframe_id = null)
    {
        $this->getSession()->getDriver()->switchToIFrame($iframe_id);
    }

    /**
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     *
     */
    public function setUpTestEnvironment($scope)
    {
        $this->currentScenario = $scope->getScenario();
        $this->currentFeature = $scope->getFeature();
    }

    /**
     * @AfterStep
     *
     * Check if the test has failed
     * If yes it takes a screenshot and saves it in the correct folder
     *
     * @param AfterStepScope $scope
     */
    public function afterStep(AfterStepScope $scope)
    {
        //Check if the test has failed
        if (!$scope->getTestResult()->isPassed()) {
            //Set fileFolder string and sanitize it
            $featureFolder = preg_replace('/\W/', '', $scope->getFeature()->getTitle());

            //Set fileName string (using the line as a name reference)
            $fileName = $scope->getStep()->getLine();

            $scenarioName = $this->currentScenario->getTitle();

            if (!file_exists('/code/reports/assets/screenshots/' . $featureFolder)) {
                mkdir('/code/reports/assets/screenshots/' . $featureFolder, 0777, true);
            }
            //take screenshot and save as the previously defined filename
            $this->saveScreenshot($fileName, '/code/reports/assets/screenshots/' . $featureFolder);
        }
    }

    /**
     * Scrolls to the top of the page
     *
     * @Given /^I scroll to the top$/
     *
     * Example: Given I scroll to the top
     * Example: And I scroll to the top
     */
    public function iScrollToTheTop()
    {
        $this->getSession()->executeScript('window.scrollTo(0,0);');
    }

    /**
     * Scrolls to the bottom of the page
     *
     * @Given /^I scroll to the bottom$/
     *
     * Example: Given I scroll to the bottom
     * Example: And I scroll to the bottom
     */
    public function iScrollToTheBottom()
    {
        $javascript = 'window.scrollTo(0,'
            . ' Math.max('
            . ' document.documentElement.scrollHeight,'
            . ' document.body.scrollHeight,'
            . ' document.documentElement.clientHeight));';
        $this->getSession()->executeScript($javascript);
    }

    /**
     * Scrolls to the top or bottom of a element given by css or xpath selector
     * If it wasn't specified, by default it will scroll to the top
     *
     * @Given /^I scroll to (?:the (?P<position>top|bottom) of )?"(?P<selector>.*)"$/
     *
     * Example: Given I scroll to "//h3[text()="Chapter 3"]"
     * Example: Given I scroll to the bottom of "//img[@alt="tyrannosaurus rex"]"
     * Example: And I scroll to the top of "#register-form"
     */
    public function iScrollToTheElement($position, $selector)
    {
        if (!$position) {
            $position = 'top';
        }
        $element = $this->findElement($selector);
        $xpath = $element->getXpath();
        $jsElement = "document.evaluate(\"$xpath\","
            . "document," //contextNode
            . "null," //namespaceResolver
            . "XPathResult.FIRST_ORDERED_NODE_TYPE," //resultType
            . "null)" //result
            . ".singleNodeValue";
        $bodyY = "document.body.getBoundingClientRect().top";
        $elementY = "$jsElement.getBoundingClientRect().$position";
        $offset = "$elementY - $bodyY";
        $javascript = "window.scrollTo(0, $offset);";
        $this->getSession()->executeScript($javascript);
        //wait untill scrolling is done
        $this->waitForJsCondition("document.readyState == 'complete'");
    }

    /**
     * Create visual comparison checkpoint
     * @Then /^I set the visual checkpoint "(?P<name>[^"]*)"$/
     */
    public function createVisualCheckpoint($name)
    {
        if (!$this->visualRegression) {
            return;
        }

        $checkpoint = new VisualCheckpoint();
        $checkpointId = preg_replace('/\W/', '', $name);
        $checkpoint->setId($checkpointId);
        $checkpoint->setName($name);
        $tags = array_merge($this->currentFeature->getTags(), $this->currentScenario->getTags());
        $checkpoint->setTags($tags);

        $visualRegressionFolder = "/code/visual_regression";

        $input   = new ArgvInput($_SERVER['argv']);
        $profile = $input->getParameterOption(array('--profile', '-p')) ? : 'default';

        $baseFolder = "$visualRegressionFolder/$profile";

        $baselineFolder = "$baseFolder/baselines";

        $checkpointFilename = "$checkpointId.png";


        if (!file_exists("$baselineFolder")) {
            mkdir("$baselineFolder", 0777, true);
        }


        #set baselines mode
        if ($this->baseline) {
            $this->saveScreenshot($checkpointFilename, $baselineFolder);
        } #if not baseline mode, compare and save screenshot if it has differences
        else {
            $reportFolder = "$baseFolder/report";
            if (!file_exists($reportFolder)) {
                mkdir($reportFolder, 0777, true);
            }
            $this->saveScreenshot("current_$checkpointFilename", $reportFolder);
            $checkpoint->setCurrent("current_$checkpointFilename");

            if (!file_exists("$baselineFolder/$checkpointFilename")) {
                echo("No baseline for $name. It must be created by running behat with --visual-regression and"
                    . " --baseline options. Skipping checkpoint");
                $checkpoint->setStatus(VisualCheckpoint::SKIPPED);
                $this->renderer->addCheckpoint($checkpoint);
                return;
            } else {
                copy("$baselineFolder/$checkpointFilename", "$reportFolder/baseline_$checkpointFilename");
                $checkpoint->setBaseline("baseline_$checkpointFilename");
            }

            $baseline = new Imagick("$baselineFolder/$checkpointFilename");
            $current = new Imagick("$reportFolder/current_$checkpointFilename");
            $diff = $baseline->compareImages($current, Imagick::METRIC_ABSOLUTEERRORMETRIC);
            $percentualDifference = $diff[1] * 100 / ($baseline->getimagewidth() * $baseline->getimageheight());
            $checkpoint->setDiffPercent($percentualDifference);
            if ($percentualDifference > 0.0) {
                $checkpoint->setStatus(VisualCheckpoint::PENDING);
                $diff[0]->writeImage("$reportFolder/diff_$checkpointFilename");
                $checkpoint->setDiff("diff_$checkpointFilename");
            } else {
                $checkpoint->setStatus(VisualCheckpoint::APPROVED);
            }
        }
        $this->renderer->addCheckpoint($checkpoint);
    }
    
    /**
     * Create visual comparison checkpoint
     * @Then /^I set the visual checkpoint "(?P<name>[^"]*)" ignoring the elements:$/
     */
    public function createVisualCheckpointWithIgnores($name, TableNode $ignoredElementsTable)
    {
        $previousVisibility = array();
        
        // hide ignored elements
        foreach ($ignoredElementsTable->getRows() as $selector) {
            $elements = $this->findElements($selector[0]);
            foreach ($elements as $element) {
                $jsElement = "document.evaluate(\"{$element->getXpath()}\", document, null"
                . ", XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue";
                $previousVisibility[$jsElement] = $this->getSession()->evaluateScript("$jsElement.style.visibility;");
                $this->getSession()->executeScript("$jsElement.style.visibility = 'hidden';");
            }
        }
        
        $this->createVisualCheckpoint($name);
        
        //restore previous visibilities
        foreach ($previousVisibility as $jsElement => $visibility) {
            $this->getSession()->executeScript("$jsElement.style.visibility = '$visibility'");
        }
    }

    /**
     * Hover over an element given by css or xpath selector
     *
     * @When /^I hover over "(?P<selector>[^"]*)"$/
     *
     * Example: When I hover over "//div[@id='card-content']"
     * Example: When I hover over "#card-content"
     */
    public function iHoverOverTheElement($selector)
    {
        $element = $this->findElement($selector);
        $element->mouseOver();
    }

    /**
     * Waits until element appears or disappears
     *
     * Example: When I wait until "//a[text()='Log out']" appears
     * Example: And I wait at most 15 seconds until "#main-content" appears
     * Example: Then I wait at most 5 seconds until "//span[@class='loading']" disappears
     * Example: Then I wait at most 1 second until "//span[@class='loading']" disappears
     *
     * @When /^I wait (?>at most (?P<seconds>\d+) seconds? )?until "(?P<selector>[^"]*)" (?P<disappear>dis)?appears$/
     */
    public function waitForElement($selector, $seconds = null, $disappear = false)
    {
        if (!$seconds) {
            $seconds = $this->default_timeout;
        }
        $start = microtime(true);
        $end = $start + $seconds;
        do {
            try {
                $elementVisible = $this->findElement($selector)->isVisible();
            } catch (ElementNotFoundException $ex) {
                $elementVisible = false;
            }
            if ($elementVisible xor $disappear) {
                return;
            }
            usleep(100000);
        } while (microtime(true) < $end);
        throw new Exception("Timeout while waiting for \"$selector\" to {$disappear}appear.");
    }

    /**
     * Switches focus to the last opened browser window/tab
     *
     * @When /^I switch to the last opened (window|tab)$/
     *
     * Example: And I switch to the last opened window
     * Example: When I switch to the last opened tab
     */
    public function switchToLastWindow()
    {
        $windowNames = $this->getSession()->getWindowNames();
        $this->getSession()->switchToWindow(end($windowNames));
    }

    /**
     * Switches focus to a specific browser window/tab given by title
     * (where the title is given by the head > title html element)
     *
     * @When /^I switch to the (window|tab) "(?P<windowTitle>[^"]*)"$/
     *
     * Example: And I switch to the window "New window"
     * Example: When I switch to the tab "Example - Contact us"
     */
    public function switchToWindow($windowTitle)
    {
        $windows = $this->getWindowNames();
        if (!array_key_exists($windowTitle, $windows)) {
            throw new Exception("No window with title \"$windowTitle\" was found");
        }
        $this->getSession()->switchToWindow($windows[$windowTitle]);
    }

    /**
     * Closes the current focused window/tab
     *
     * @When /^I close the current (window|tab)/
     *
     * Example: And I close the current window
     * Example: And I close the current tab
     */
    public function closeCurrentWindow()
    {
        $this->getSession()->getDriver()->getWebDriverSession()->window();
        $this->switchToLastWindow();
    }

    /**
     * Checks if window/tab with specified title exists
     *
     * @Then /^I should(?P<not> not)? see a (window|tab) with title "(?P<windowTitle>[^"]*)"$/
     *
     * Example: Then I should see a window with title "New Page"
     * Example: Then I should see a tab with title "New Tab"
     */
    public function shouldSeeWindow($windowTitle, $not)
    {
        $should = ($not === "");
        $windowExists = array_key_exists($windowTitle, $this->getWindowNames());
        if ($windowExists xor $should) {
            throw new \Exception("Window/tab with title \"$windowTitle\" was$not found!");
        }
    }
    /*
     * returns an array of widow names
     * with the window titles as keys
     */
    public function getWindowNames()
    {
        $windows = array();
        $session = $this->getSession();
        $initialWindowName = $session->getWindowName();
        $windowNames = $session->getWindowNames();
        foreach ($windowNames as $windowName) {
            $session->switchToWindow($windowName);
            $title = $session->getPage()->find('xpath', '//title')->getHtml();
            $windows[$title] = $windowName;
        }
        $session->switchToWindow($initialWindowName);
        return $windows;
    }

    /**
     * Set a browser window size to a specific dimension
     *
     * @Given /^I resize the browser window to "([^"]*)" x "([^"]*)"$/
     *
     * Example: And I resize the browser window to "1280" x "600"
     */
    public function iSetBrowserWindowSizeToX($width, $height)
    {
        $this->getSession()->resizeWindow((int)$width, (int)$height, 'current');
    }

    /**
     * Maximize the window if it is not maximized already
     *
     * @Given /^I maximize the browser window$/
     *
     * Example: And I maximize the browser window
     */
    public function iSetBrowserWindowSizeToMax()
    {
        $this->getSession()->maximizeWindow();
    }
    
    /**
     * Fill in a CKEDITOR instance given by it's <textarea> css/xpath selector with the given content
     * TIP: To find the ID, execute "CKEDITOR.instances" on Firebug/Chrome Console
     *
     * @Then I fill in ckeditor field :selector with :value
     * @Then I fill in ckeditor field :selector with:
     *
     * Example: I fill in ckeditor "//textarea[@id='ckdemo']" with "My example text"
     */
    public function iFillInCKEditorOnFieldWith($selector, $value)
    {
        $el = $this->findElement($selector);

        $fieldId = $el->getAttribute('id');

        if (empty($fieldId)) {
            throw new Exception('Could not find an id for that CKEditor field.');
        }

        $this->getSession()
            ->executeScript("CKEDITOR.instances[\"$fieldId\"].setData(\"$value\");");
    }
    
//    Debug functions


    protected function assertContains($haystack, $needle)
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception("Failed asserting that"."\n$haystack\n"."contains"."\n$needle");
        }
    }

    protected function assertDoesntContain($haystack, $needle)
    {
        if (strpos($haystack, $needle)) {
            throw new Exception("Failed asserting that"."\n$haystack\n"." does not contain"."\n$needle");
        }
    }

    protected function assertEquals($strA, $strB)
    {
        if ($strA !=  $strB) {
            throw new Exception("Failed asserting that"."\n$strA\n"."is equal to"."\n$strB");
        }
    }

    protected function assertNotEquals($strA, $strB)
    {
        if ($strA ===  $strB) {
            throw new Exception("Failed asserting that"."\n$strA\n"."is not equal to"."\n$strB");
        }
    }


    /**
     * Echo a string containing the name of currently
     * opened windows
     *
     * @Given /^I list all open windows$/
     *
     * Example: And I list all open windows
     */
    public function echoWindowNames()
    {
        #print using ANSI colors
        $yellow = self::YELLOW;
        echo($yellow . implode(' | ', array_keys($this->getWindowNames())));
    }

    /**
     * Echo the page text
     *
     * @Given /^I echo the page's text$/
     *
     * Example: And I echo the page's text
     */
    public function echoPageText()
    {
        #print using ANSI colors
        $yellow = self::YELLOW;
        echo($yellow . $this->getSession()->getPage()->getText());
    }


//    Mink inherited steps

    /**
     * Opens homepage
     *
     * Example: Given I am on the homepage
     * Example: When I go to the homepage
     *
     * @Given I am on the homepage
     * @When I go to the homepage
     */
    public function iAmOnHomepage()
    {
        $this->visitPath('/');
    }

    /**
     * Opens specified page
     *
     * Example: Given I am on "http://www.batman.com"
     * Example: And I am on "/articles/isBatmanBruceWayne"
     * Example: When I go to "/articles/isBatmanBruceWayne"
     *
     * @Given /^I am on "(?P<page>[^"]+)"$/
     * @When /^I go to "(?P<page>[^"]+)"$/
     */
    public function visit($page)
    {
        $this->visitPath($page);
    }

    /**
     * Reloads current page
     *
     * Example: When I reload the page
     * Example: And I reload the page
     *
     * @When /^I reload the page$/
     */
    public function reload()
    {
        $this->getSession()->reload();
    }

    /**
     * Moves backward one page in history
     *
     * Example: When I move backward one page
     *
     * @When /^I move backward one page$/
     */
    public function back()
    {
        $this->getSession()->back();
    }

    /**
     * Moves forward one page in history
     * Example: And I move forward one page
     *
     * @When /^I move forward one page$/
     */
    public function forward()
    {
        $this->getSession()->forward();
    }

    /**
     * Fills in form field with specified css/xpath selector
     * Example: When I fill in "//input[@id = 'username']" with: "bwayne"
     * Example: And I fill in "bwayne" for "#username"
     * Example: And I fill in "textarea[@id = 'description']" with:
     * """
     * Multi-line and indented text
     * ==============================
     * Here is the first paragraph of my input.
     *  The indentation will be preserved
     *      Everything enclosed in """ will be inputed on the field.
     * """
     *
     * @When /^(?:|I )fill in "(?P<selector>[^"]*)" with "(?P<value>[^"]*)"$/
     * @When /^(?:|I )fill in "(?P<selector>[^"]*)" with:$/
     * @When /^(?:|I )fill in "(?P<value>[^"]*)" for "(?P<selector>[^"]*)"$/
     */
    public function fillField($selector, $value)
    {
        $field = $this->findElement($selector);
        $field->setValue($value);
    }

    /**
     * Fills in form fields with provided table
     * Example: When I fill in the following"
     *          | //input[@id = 'username'] | bruceWayne   |
     *          | //input[@id = 'password'] | iLoveBats123 |
     * Example: And I fill in the following"
     *          | #username | bruceWayne   |
     *          | #password | iLoveBats123 |
     *
     * @When /^I fill in the following:$/
     */
    public function fillFields(TableNode $fields)
    {
        foreach ($fields->getRowsHash() as $selector => $value) {
            $this->fillField($selector, $value);
        }
    }

    /**
     * Selects option with specified text/value in select field with specified css/xpath selector
     * Use also/additionally keyword when selecting multiple options
     * Example: When I select "Bats" from "user_fears"
     * Example: And I select "Bats" from "user_fears"
     * Example: When I additionally select "Deceased" from "parents_alive_status"
     * Example: And I also select "Deceased" from "parents_alive_status"
     * @When /^I (?P<additional>(?>also|additionally) )?select "(?P<option>[^"]*)" from "(?P<select>[^"]*)"$/
     */
    public function selectOption($select, $option, $additional = false)
    {
        $select = $this->findElement($select);
        $select->selectOption($option, $additional);
    }

    /**
     * Checks checkbox with specified css/xpath selector
     * Example: When I check "Pearl Necklace" from "itemsClaimed"
     * Example: And I check "Pearl Necklace" from "itemsClaimed"
     *
     * @When /^I check "(?P<selector>[^"]*)"$/
     */
    public function checkOption($selector)
    {
        $checkbox = $this->findElement($selector);
        $checkbox->check();
    }

    /**
     * Unchecks checkbox with specified css/xpath selector
     * Example: When I uncheck "Broadway Plays" from "hobbies"
     * Example: And I uncheck "Broadway Plays" from "hobbies"
     *
     * @When /^I uncheck "(?P<selector>[^"]*)"$/
     */
    public function uncheckOption($selector)
    {
        $checkbox = $this->findElement($selector);
        $checkbox->uncheck();
    }

    /**
     * Attaches file to field with specified css/xpath selector
     * Example: When I attach "bwayne_profile.png" to "profileImageUpload"
     * Example: And I attach "bwayne_profile.png" to "profileImageUpload"
     *
     * @When /^I attach the file "(?P<path>[^"]*)" to "(?P<selector>[^"]*)"$/
     */
    public function attachFileToField($selector, $path)
    {
        $field = $this->findElement($selector);

        if ($this->getMinkParameter('files_path')) {
            $ds = DIRECTORY_SEPARATOR;
            $fullPath = rtrim(realpath($this->getMinkParameter('files_path')), $ds).$ds.$path;
            if (is_file($fullPath)) {
                $path = $fullPath;
            }
        }

        $field->attachFile($path);
    }

    /**
     * Checks that current page URL is equal to specified
     * Example: Then I should be on "/"
     * Example: And I should be on "/bats"
     * Example: And I should be on "http://google.com"
     *
     * @Then /^I should be on "(?P<page>[^"]+)"$/
     */
    public function assertPageAddress($page)
    {
        $this->assertSession()->addressEquals($this->locatePath($page));
    }

    /**
     * Checks, that current page is the homepage
     * Example: Then I should be on the homepage
     * Example: And I should be on the homepage
     *
     * @Then I should be on the homepage
     */
    public function assertHomepage()
    {
        $this->assertSession()->addressEquals($this->locatePath('/'));
    }

    /**
     * Checks, that current page PATH matches regular expression
     * Example: Then the url should match "superman is dead"
     * Example: Then the uri should match "log in"
     * Example: And the url should match "log in"
     *
     * @Then /^the (?i)url(?-i) should match (?P<pattern>"(?:[^"]|\\")*")$/
     */
    public function assertUrlRegExp($pattern)
    {
        $this->assertSession()->addressMatches($pattern);
    }

    /**
     * Checks, that page contains specified text
     * Example: Then I should see "Who is the Batman?"
     * Example: And I should see "Who is the Batman?"
     *
     * @Then /^I should see "(?P<text>.*)"$/
     * @Then /^I should see:$/
     */
    public function assertPageContainsText($text)
    {
        $this->assertSession()->pageTextContains($text);
    }

    /**
     * Checks, that page doesn't contain specified text
     * Example: Then I should not see "Batman is Bruce Wayne"
     * Example: And I should not see "Batman is Bruce Wayne"
     *
     * @Then /^(?:|I )should not see "(?P<text>.*)"$/
     */
    public function assertPageNotContainsText($text)
    {
        $this->assertSession()->pageTextNotContains($text);
    }

    /**
     * Checks, that page contains text matching specified pattern
     * Example: Then I should see text matching "Batman, the vigilante"
     * Example: And I should not see "Batman, the vigilante"
     *
     * @Then /^(?:|I )should see text matching (?P<pattern>"(?:[^"]|\\")*")$/
     */
    public function assertPageMatchesText($pattern)
    {
        $this->assertSession()->pageTextMatches($pattern);
    }

    /**
     * Checks, that page doesn't contain text matching specified pattern
     * Example: Then I should see text matching "Bruce Wayne, the vigilante"
     * Example: And I should not see "Bruce Wayne, the vigilante"
     *
     * @Then /^(?:|I )should not see text matching (?P<pattern>"(?:[^"]|\\")*")$/
     */
    public function assertPageNotMatchesText($pattern)
    {
        $this->assertSession()->pageTextNotMatches($pattern);
    }

    /**
     * Checks, that HTML response contains specified string
     * Example: Then the response should contain "<h1>Batman is the hero Gotham deserves.</h1>"
     * Example: And the page source should contain "<title>Peanut Butter Jelly Time</title>"
     * Example: And the page html should contain "<script src='https://www.google-analytics.com/analytics.js'></script>"
     *
     * @Then /^the response should contain "(?P<text>(?:[^"]|\\")*)"$/
     * @Then the page source should contain ":text"
     * @Then the page html should contain ":text"
     */
    public function assertResponseContains($text)
    {
        $this->assertSession()->responseContains($text);
    }

    /**
     * Checks, that HTML response doesn't contain specified string
     * Example: Then the response should not contain "<h1>Wrong Heading</h1>"
     * Example: And the page source should not contain "<link rel="alternate" href="http://example.com/" />"
     * Example: And the page html should not contain "<script src='https://www.google-analytics.com/ga.js'></script>"
     *
     * @Then /^the response should not contain "(?P<text>(?:[^"]|\\")*)"$/
     * @Then the page source should not contain ":text"
     * @Then the page html should not contain ":text"
     */
    public function assertResponseNotContains($text)
    {
        $this->assertSession()->responseNotContains($text);
    }

    /**
     * Checks, that element with specified css/xpath selector contains specified text
     * Example: Then I should see "Batman" in the "heroes_list" element
     * Example: And I should see "Batman" in the "heroes_list" element
     *
     * @Then /^(?:|I )should see "(?P<text>(?:[^"]|\\")*)" in the "(?P<selector>[^"]*)" element$/
     */
    public function assertElementContainsText($selector, $text)
    {
        $element = $this->findElement($selector);
        $this->assertContains($element->getText(), $text);
    }

    /**
     * Checks, that element with specified css/xpath selector doesn't contain specified text
     * Example: Then I should not see "Bruce Wayne" in the "heroes_alter_egos" element
     * Example: And I should not see "Bruce Wayne" in the "heroes_alter_egos" element
     *
     * @Then /^(?:|I )should not see "(?P<text>(?:[^"]|\\")*)" in the "(?P<element>[^"]*)" element$/
     */
    public function assertElementNotContainsText($selector, $text)
    {
        $element = $this->findElement($selector);
        $this->assertDoesntContain($element->getText(), $text);
    }

    /**
     * Checks, that element with specified css/xpath selector contains specified HTML
     * Example: Then the "body" element should contain "style=\"color:black;\""
     * Example: And the "body" element should contain "style=\"color:black;\""
     *
     * @Then /^the "(?P<element>[^"]*)" element should contain "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function assertElementContains($selector, $value)
    {
        $element = $this->findElement($selector);
        $this->assertContains($element->getHtml(), $value);
    }

    /**
     * Checks, that element with specified css/xpath selector doesn't contain specified HTML
     * Example: Then the "body" element should not contain "style=\"color:black;\""
     * Example: And the "body" element should not contain "style=\"color:black;\""
     *
     * @Then /^the "(?P<element>[^"]*)" element should not contain "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function assertElementNotContains($selector, $value)
    {
        $element = $this->findElement($selector);
        $this->assertDoesntContain($element->getHtml(), $value);
    }

    /**
     * Checks, that element with specified css/xpath selector exists on page
     * Example: Then I should see a "body" element
     * Example: And I should see a "body" element
     *
     * @Then /^(?:|I )should see an? "(?P<element>[^"]*)" element$/
     */
    public function assertElementOnPage($selector)
    {
        $element = $this->findElement($selector);
        if (!$element->isVisible()) {
            throw new Exception("The element $selector is not visible");
        }
    }

    /**
     * Checks, that element with specified css/xpath selector doesn't exist on page
     * Example: Then I should not see a "canvas" element
     * Example: And I should not see a "canvas" element
     *
     * @Then /^(?:|I )should not see an? "(?P<element>[^"]*)" element$/
     */
    public function assertElementNotOnPage($selector)
    {
        try {
            $element = $this->findElement($selector);
            if ($element->isVisible()) {
                throw new Exception("The element $selector is visible");
            }
        } catch (ElementNotFoundException $ex) {
            return;
        }
    }

    /**
     * Checks, that form field with specified css/xpath selector has specified value
     * Example: Then the "username" field should contain "bwayne"
     * Example: And the "username" field should contain "bwayne"
     *
     * @Then /^the "(?P<selector>(?:[^"]|\\")*)" field should contain "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function assertFieldContains($selector, $value)
    {
        $field = $this->findElement($selector);
        $this->assertContains($field->getValue(), $value);
    }

    /**
     * Checks, that form field with specified css/xpath selector doesn't have specified value
     * Example: Then the "username" field should not contain "batman"
     * Example: And the "username" field should not contain "batman"
     *
     * @Then /^the "(?P<selector>(?:[^"]|\\")*)" field should not contain "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function assertFieldNotContains($selector, $value)
    {
        $field = $this->findElement($selector);
        $this->assertDoesntContain($field->getValue(), $value);
    }

    /**
     * Checks, that (?P<num>\d+) elements with specified css/xpath selector exist on the page
     * Example: Then I should see 5 "div" elements
     * Example: And I should see 5 "div" elements
     *
     * @Then /^(?:|I )should see (?P<num>\d+) "(?P<element>[^"]*)" elements?$/
     */
    public function assertNumElements($num, $selector)
    {
        $elements = $this->findElements($selector);
        $count = count($elements);
        if ($num != $count) {
            throw new Exception("There are $count $selector element(s), but there should be only $num");
        }
    }

    /**
     * Checks, that checkbox with specified css/xpath selector is checked
     * Example: Then the "remember_me" checkbox should be checked
     * Example: And the "remember_me" checkbox is checked
     *
     * @Then /^the "(?P<selector>(?:[^"]|\\")*)" checkbox should be checked$/
     * @Then /^the checkbox "(?P<selector>(?:[^"]|\\")*)" (?:is|should be) checked$/
     */
    public function assertCheckboxChecked($selector)
    {
        $checkbox = $this->findElement($selector);
        if (!$checkbox->isChecked()) {
            throw new Exception("The checkbox $selector is not checked");
        }
    }

    /**
     * Checks, that checkbox with specified css/xpath selector is unchecked
     * Example: Then the "newsletter" checkbox should be unchecked
     * Example: Then the "newsletter" checkbox should not be checked
     * Example: And the "newsletter" checkbox is unchecked
     *
     * @Then /^the "(?P<selector>(?:[^"]|\\")*)" checkbox should not be checked$/
     * @Then /^the checkbox "(?P<selector>(?:[^"]|\\")*)" should (?:be unchecked|not be checked)$/
     * @Then /^the checkbox "(?P<selector>(?:[^"]|\\")*)" is (?:unchecked|not checked)$/
     */
    public function assertCheckboxNotChecked($selector)
    {
        $checkbox = $this->findElement($selector);
        if ($checkbox->isChecked()) {
            throw new Exception("The checkbox $selector is checked");
        }
    }

    /**
     * Prints current URL to console.
     * Example: Then print current URL
     * Example: And print current URL
     *
     * @Then /^print current URL$/
     */
    public function printCurrentUrl()
    {
        echo $this->getSession()->getCurrentUrl();
    }

    /**
     * Prints last response to console
     * Example: Then print current response
     * Example: And print current response
     *
     * @Then /^print last response$/
     */
    public function printLastResponse()
    {
        echo (
            $this->getSession()->getCurrentUrl()."\n\n".
            $this->getSession()->getPage()->getContent()
        );
    }
    
  /**
   * Start GA Request Script to listening ga requests
   * You need to start every time you load a new page or reload it
   * Example: Then I start google analytics listener
   *
   * @Then /^I start google analytics listener$/
   */
    public function startGoogleAnalytics()
    {
        $gaRequestScript = $this->getGaRequestJsFile();
        $this->getSession()->executeScript($gaRequestScript);
        
        // When the script is added to page, its necessary to wait it finishes to load
        // and ready current requests
        $this->getSession()->wait(2500, "false");
    }

 /**
  * Validate if analytics keys were sent to google
  * Example: Then I have google analytics keys
  *          | key | value        |
  *          | ea  | tab-recent  |
  *          | ec  | notification |
  *
  * @Then /^I have google analytics keys$/
  */
    public function googleAnalyticsWasSend(TableNode $keys)
    {
        $hash = $keys->getHash();

        $keysValueParameters = "";
        foreach ($hash as $row) {
            $keysValueParameters .= '{key: "'.$row['key'].'", value:"'.$row['value'].'"},';
        }
        $keysValueParameters = rtrim($keysValueParameters, ",");

        // Its necessary to wait analytics request finish, so, it can be read
        $this->getSession()->wait(2000, "false");
        $script = 'return gaRequest.keysWereSent(' .$keysValueParameters. ');';

        $keysWereSent = $this->getSession()->getDriver()->getWebDriverSession()->execute(
            array('script' => $script, 'args' => array())
        );
        if (!$keysWereSent) {
            throw new \Exception(sprintf('The google analytics keys and values were not sent.'));
        }
    }

 /**
  * Return Google Analytics Script that listen GA requests
  */
    public function getGaRequestJsFile()
    {
        return '
            var gaScriptRequest = document.createElement("script");
            gaScriptRequest.append(
            /**
             * GA extension
             * Used to verify if ga events are working correctly
             */
            function GARequest() {

                var gaRequetsCookieName = "gaRequests";

                /**
                 * Cookie for gaRequests
                 * set: set cookie
                 * read: return cookie
                 * clearAll: clear all cookie
                 * clearOldest: keep only last X urls on cookie
                 */
                var cookie = {
                    set: function (value) {
                        var urls = this.read(gaRequetsCookieName);
                        if (urls) {
                            urls += ",";
                        } else {
                            urls = "";
                        }
                        console.log("urls: " + urls);

                        for (var i = 0; i < value.length; i++) {
                            urls += value[i].toString() + ",";
                        }
                        urls = urls.substr(0, urls.length - 1);

                        document.cookie = gaRequetsCookieName + "=" + urls; // remove last ,
                        gaRequest.removeAll();
                        console.log("Request Cleaned");
                    },

                    read: function () {
                        var result = document.cookie.match(new RegExp(gaRequetsCookieName + "=([^;]+)"));
                        result && (result = result[1]);
                        return result;
                    },

                    clearAll: function () {
                        document.cookie = gaRequetsCookieName + "=";
                    },

                    clearOldest: function () {
                        var urlsFromCookie = gaRequest.getAllFromCookie();            

                        var storageLimit = 5;
                        var maxStorageLimitWasExceeded = urlsFromCookie.length > storageLimit;
                        if (!maxStorageLimitWasExceeded) {
                            return;
                        }

                        var urls = [];
                        var qtyUrlsExceeded = urlsFromCookie.length - storageLimit;
                        for (var i = 0; i < urlsFromCookie.length; i++) {
                            if (i >= qtyUrlsExceeded) {
                                var url = urlsFromCookie[i].href;
                                urls.push(url);
                            }
                        }

                        this.clearAll();
                        this.set(urls);
                    }
                }

                /**
                 * set sleep with Promise
                 */
                function sleep(ms) {
                    return new Promise(resolve => setTimeout(resolve, ms));
                }

                /**
                 * Remove all requests, including non GA requests
                 */
                function removeAll() {
                    window.performance.clearResourceTimings();
                }

                /**
                 * Get all GA requests and return an array
                 * of URL object
                 */
                function getAll() {
                    var allRequests = window.performance.getEntries();

                    var allGAUrls = [];
                    for (var i = 0; i < allRequests.length; i++) {
                        var requestName = allRequests[i].name;
                        var isGoogleAnalytics = requestName.match("www.google-analytics.com");

                        if (isGoogleAnalytics) {
                            var url = new URL(requestName);
                            allGAUrls.push(url);
                        }
                    }

                    return allGAUrls;
                }

                /**
                 * Get all GA requests from cookie and return an array
                 * of URL object
                 */
                function getAllFromCookie() {
                    var allGAUrls = [];

                    var urlsFromCookie = cookie.read();
                    if (!urlsFromCookie) {
                        return allGAUrls;
                    }

                    var allGaRequests = cookie.read().split(",");

                    for (var i = 0; i < allGaRequests.length; i++) {
                        var url = new URL(allGaRequests[i]);
                        allGAUrls.push(url);
                    }

                    return allGAUrls;
                }

                /**
                 * Get all GA requests that matches with all GA keys and values
                 * return a array of URL object
                 * how to use: 
                 *      ga.Request.allByKeysValue({ key: "ea", value: "open-article" }, { key: "ec", value: "news" })
                 */
                function getAllByKeysValue() {
                    var keysValue = getParameters(arguments);
                    var matchedKeysValue = [];

                    //var allGAUrls = getAll();
                    var allGAUrls = getAllFromCookie();
                    for (var i = 0; i < allGAUrls.length; i++) {
                        var currentGAUrl = allGAUrls[i];

                        if (allKeysMatched(keysValue, currentGAUrl)) {
                            matchedKeysValue.push(currentGAUrl);
                        }
                    }

                    return matchedKeysValue;
                }

                /**
                 * Get args[] - received parameters
                 * return parameters
                 */
                function getParameters() {
                    var keysValue = [];
                    var args = arguments[0];
                    for (var i = 0; i < args.length; i++) {
                        keysValue[i - 0] = args[i];
                    }

                    return keysValue[0];
                }

                /**
                 * Verify if all keys and values match with current url parameters
                 * return if all keys and values matched
                 */
                function allKeysMatched(keysValue, url) {
                    for (var i = 0; i < keysValue.length; i++) {
                        var urlKeyValue = url.searchParams.get(keysValue[i].key);
                        var matchKeyValue = urlKeyValue === keysValue[i].value;

                        if (!matchKeyValue) {
                            return false;
                        }
                    }

                    return true;
                }

                /**
                 * Used for external call (public method). Its for get arguments parameters
                 * Get all GA requests that matches with all GA keys and values
                 * return a array of URL object
                 * how to use: 
                 *        ga.Request.allByKeysValue({ key: "ea", value: "open-article" }, { key: "ec", value: "news" })
                 */
                function externalGetAllByKeysValue() {
                    return getAllByKeysValue(arguments);
                }

                /**
                 * Verify if all keys and values match with current url parameters
                 * return true or false
                 */
                function keysWereSent() {
                    var matchedUrls = getAllByKeysValue(arguments);
                    if (matchedUrls.length > 0) {
                        return true;
                    }

                    return false;
                }

                /**
                 * Add new requets to cookie
                 */
                function addRequestToCookie() {
                    var allGaRequests = getAll();
                    console.log("added requests " + allGaRequests.length);
                    cookie.set(allGaRequests);
                    cookie.clearOldest();
                };

                /**
                 * Add observa to ga.P object
                 * when a new ga request is added, this method calls addRequestToCookie();
                 */
                function addEntryObservable() {
                    var gaIsLoaded = false;
                    console.log("GA Loading...");
                    while (!gaIsLoaded) {
                        if (ga.P) {
                            Object.defineProperty(ga.P, "push", {
                                configurable: false,
                                enumerable: false, // hide from for...in
                                writable: false,
                                value: function () {
                                    var requestQty = arguments.length;
                                    console.log("waiting request 50ms...");
                                    sleep(200).then(function () { addRequestToCookie(); });
                                    return requestQty;
                                }
                            });

                            gaIsLoaded = true;
                        }
                    }
                    console.log("GA Loaded.");
                    addRequestToCookie();
                }

                console.log("Init");
                sleep(2000).then(function () { addEntryObservable(); });

                // Public methods
                return {
                    getAllFromCookie: getAllFromCookie,
                    getAllByKeysValue: externalGetAllByKeysValue,
                    removeAll: removeAll,
                    keysWereSent: keysWereSent,
                    cookie: cookie
                }

            });

            gaScriptRequest.append("var gaRequest = new GARequest()");
            document.getElementsByTagName("head")[0].appendChild(gaScriptRequest);';
    }
}
