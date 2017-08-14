@visual-regression
Feature: Test if the Visual Regression Extension is working correctly
#you should run behat with the --visual-regression parameter:
#i.e.: behat --visual-regression features/VisualRegression/visualRegression.feature        

#to set the checkpoints as baselines you should also pass the --baseline parameter:
#i.e.: behat --visual-regression --baseline features/VisualRegression/visualRegression.feature  

@passing
Scenario: Visual Regression Passing
Given I am on "https://en.wikipedia.org/w/index.php?title=Mobile_phone&oldid=111436517"
And I set the visual checkpoint "Wikipedia Mobile Phone Article"

@failing
Scenario: Visual Regression Failing
Given I am on "https://en.wikipedia.org/wiki/Special:Random"
And I set the visual checkpoint "Random Wikipedia Article"

@skipped
Scenario: Visual Regression Skipped
Given I am on "https://en.wikipedia.org/wiki/Baseline/"
And I set the visual checkpoint "Wikipedia Baseline Article"
