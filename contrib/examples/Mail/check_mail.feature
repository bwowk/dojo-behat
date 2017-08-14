@mail
Feature: Check if received email

  Background:
    Given the site url is "${site_url}"


  Scenario: Sending test mail
    Given I am on "/tests/new"
    When I fill in the following:
    | recipient-value    | jnj-automation-inbox+email-context-example@ciandt.com |
    | email_test_subject | Testing EmailContext: it works ;)                     |
    # you'll eventually run into elements that are not selenium-friendly like this one
    # it's an javascript custom input field
    # but don't worry, in this case you can run javascript code to manipulate that element
    # the solution will depend on the technology used on the page.
    # in this case, the email content fields use a CodeMirror element
    # so Googling for "CodeMirror Selenium" gave us what we needed (:
    And I run js "document.getElementsByClassName('CodeMirror')[0].CodeMirror.setValue('${mail_html_content}')"
    And I run js "document.getElementsByClassName('CodeMirror')[2].CodeMirror.setValue('${mail_plaintext_content}')"
    And I press "Add"
    And I press "Send Email"
    # the inbox to check is configured on behat.contrib.yml
    # on the suite > contexts > Ciandt\EmailContext values
    # (your's would be on behat.yml, usually)
    Then I should get an email like:
    | Subject  | Testing EmailContext: it works ;)                     |
    | To       | jnj-automation-inbox+email-context-example@ciandt.com |
    | From     | putsmail@putsmail.litmus.com                          |
    | HtmlBody | <p> HTML content </p>                                 |

