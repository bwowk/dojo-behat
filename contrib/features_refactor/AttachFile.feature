Feature: Test attaching and uploading a file

Scenario: Test attaching and uploading a file
Given I am on "http://the-internet/upload"
And I attach the file "docker-behat.png" to "#file-upload"
And I click on "//input[@value='Upload']"
Then I should see "File Uploaded!"
And I should see "docker-behat.png"
