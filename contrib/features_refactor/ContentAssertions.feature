Feature: Test content assertion steps

Scenario: Asserting text appears on page
  Given I am on "https://en.wikipedia.org/w/index.php?title=Brigadeiro&oldid=765702149"
  Then I should see "It is a democratic dessert that many people can enjoy."

Scenario: Asserting text does not appear on page
  Given I am on "http://the-internet/dynamic_loading/1"
  Then I should see "Start"
  When I click on "#start button"
  Then I should not see "Start"

Scenario: Testing dynamic content with regex
  Given I am on "http://the-internet/abtest"
  Then I should see text matching "(No )?A\/B Test( Control| Variation \d)?"

#@fails
#Scenario: Testing if text is not present with regex
#  Given I am on "http://the-internet/abtest"
#  Then I should not see text matching "(No )?A\/B Test( Control| Variation \d)?"

Scenario: Testing for text on specific elements
  Given I am on "http://the-internet/tables"
  Then I should see "$50.00" in the "//table[@id='table1']//tr[td[text()='Smith']]" element
  But I should not see "$50.00" in the "//table[@id='table1']//tr[td[text()='Frank']]" element

Scenario: Testing for html on specific elements
  Given I am on "http://the-internet/tables"
  Then the "//table[@id='table1']" element should contain "<td>$50.00</td>"
  And the "//table[@id='table2']" element should not contain "<td>Smith</td>"

Scenario: Asserting element is or isn't present
  Given I am on "http://the-internet/dynamic_loading/1"
  Then I should see an "#start" element
  But I should not see an "#finish" element
  And I should not see an "#yabadabadoo" element

Scenario: Asserting specific elements count
  Given I am on "http://the-internet/tables"
  Then I should see 5 ".last-name" elements
