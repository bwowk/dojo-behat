Feature: As an user I want to be able to check if Google Analytics events are being triggered

Scenario: When I click on some element that triggers a GA event
Given I am on "https://enhancedecommerce.appspot.com/item/9bdd2"
And I start google analytics listener
When I click on "#addToCart"
Then I have google analytics keys
| key | value       |
| ec  | Ecommerce   |
| ea  | Add to Cart |

Scenario: Checking event after page redirect
Given I am on "https://enhancedecommerce.appspot.com"
And I start google analytics listener
And I click on "#homepage-9bdd2-1.itemLink"
And I start google analytics listener
Then I have google analytics keys
| key | value         |
| ec  | Ecommerce     |
| ea  | Product Click |

Scenario: When I navigate to a page that has a pageview event
Given I am on "https://enhancedecommerce.appspot.com/item/9bdd2"
And I start google analytics listener
Then I have google analytics keys
| key | value    |
| t   | pageview |
