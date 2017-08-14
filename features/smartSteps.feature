Feature: smartSteps

@smartStep
Scenario: the history should show result 2
  And I press equals
  Then It should print:
  """
  + 1
  + 1
  %s
  2
  """
