<?php
namespace Ciandt;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Fetch\Server;
use Fetch\Message;

/**
 * Email IMAP Inbox testing context.
 *
 * @author Bruno Wowk <bwowk@ciandt.com>
 */
class EmailContext implements Context
{

    public function __construct($server, $port, $username, $password, $options = array())
    {
        $this->server = new Server($server, $port);
        $this->server->setAuthentication($username, $password);
    //    $this->server->setOptions(OP_SHORTCACHE);
        $this->options = $options;
        $this->setDefaults();
    }

    private $availableFields = array('To', 'Cc', 'From', 'Body', 'HtmlBody');

    protected function setDefaults()
    {
        //  Timeto wait between retries in seconds
        if (!isset($this->options['wait'])) {
            $this->options['wait'] = 3;
        }
        //  How many times to retry
        if (!isset($this->options['retry'])) {
            $this->options['retry'] = 10;
        }
        if (!isset($this->options['debug'])) {
            $this->options['debug'] = false;
        }
        if (!isset($this->options['delete'])) {
            $this->options['delete'] = false;
        }
    }

  /**
   * Checks if an email was received recently on the configured inbox
   * For each row, it checks if that email field contains the given text
   * The checking is case insensitive
   * Example:
   * Then I should get an email like:
   * | Subject | email subject            |
   * | To      | to@email.com             |
   * | To      | other@email.com          |
   * | CC      | cc@email.com             |
   * | BCC     | bcc@email.com            |
   * | From    | from@email.com           |
   * | Body    | Text to find inside body |
   * @Then /^(?:|I )should get an email like:$/
   */
    public function shouldGetEmailWith(TableNode $table)
    {

        $rows = $table->getRows();

        $yesterday = date("D, d M Y", strtotime("-1 days"));
        $mailField = array();
        $retry = $this->options['retry'];
        $wait = $this->options['wait'];
        $debug = $this->options['debug'];
        while ($retry > 0) {
          //force refresh of the mailbox to get latest emails
            $this->server->setMailBox();

          //check for emails received today and marked as unread
            $emails = $this->server->search("UNSEEN SINCE \"{$yesterday}\"");

            foreach ($emails as $email) {
                $mailField['To'] = $email->getAddresses("to", true);
                $mailField['Cc'] = $email->getAddresses("cc", true);
                $mailField['From'] = $email->getAddresses("from", true);
                $mailField['Body'] = $email->getMessageBody();
                $mailField['HtmlBody'] = $email->getHtmlBody();

                if ($debug) {
                    echo "checking mail:\n";
                    echo "Subject: {$email->getSubject()}\n"
                    . "To: {$mailField['To']}\n"
                    . "Cc: {$mailField['Cc']}\n"
                    . "From: {$mailField['From']}\n"
                    . "Date: " . date('d/m/Y G:i', $email->getDate()) . "\n"
                    . "Body: {$mailField['Body']}\n"
                    . "HtmlBody: {$mailField['HtmlBody']}\n";
                }

                //for each table row, check if email matches the given criteria
                for ($i = 1; $i < count($rows); $i++) {
                    $field = $rows[$i][0];
                    $value = $rows[$i][1];

                    //if the field name is not one of the expected ones
                    if (!in_array($field, $this->availableFields)) {
                        throw new \InvalidArgumentException(sprintf(
                            "Unknown field %s.\n\r Available fields are: %s",
                            $field,
                            implode(',', $this->availableFields)
                        ));
                    }
                    //if it doesn't match this criteria, continue checking next email
                    if (strpos(strtolower($mailField[$field]), strtolower($value)) === false) {
                        continue 2;
                    }
                }
                //if the current email matched all criterias:
                if ($this->options['delete']) {
                    $email->delete();
                    $this->server->expunge();
                } else {
                    $email->setFlag('\Seen');
                }
                return;
            }
            //if no email matched the criterias
            sleep($wait);
            $retry--;
        }
        //if retries limit exceeded
        throw new \RuntimeException("Email not received.");
    }
}
