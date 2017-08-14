Feature: Test waiting for specific conditions

Scenario: Inserting a text on CKEDITOR field
Given I am on "http://ckeditor.com/features"
When I fill in ckeditor field "#ckdemo" with "My example text"
Then I wait until "CKEDITOR.instances.ckdemo.getData().includes('<p>My example text</p>')"

