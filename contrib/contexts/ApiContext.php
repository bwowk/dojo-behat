<?php

namespace Ciandt;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\WebApiExtension\Context\ApiClientAwareContext;
use Flow\JSONPath\JSONPath;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_Assert as Assertions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use GuzzleHttp\RequestOptions;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
use PHPUnit\Framework\Assert;

class ApiContext implements ApiClientAwareContext  {

    /**
     * @var string
     */
    private $authorization;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var \GuzzleHttp\Message\RequestInterface|RequestInterface
     */
    private $request;

    /**
     * @var \GuzzleHttp\Message\ResponseInterface|ResponseInterface
     */
    private $response;

    private $placeHolders = array();

    /**
     * {@inheritdoc}
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Adds Basic Authentication header to next request.
     *
     * @param string $username
     * @param string $password
     *
     * @Given /^I am authenticating as "([^"]*)" with "([^"]*)" password$/
     */
    public function iAmAuthenticatingAs($username, $password)
    {
        $this->removeHeader('Authorization');
        $this->authorization = base64_encode($username . ':' . $password);
        $this->addHeader('Authorization', 'Basic ' . $this->authorization);
    }

    /**
     * Sets a HTTP Header.
     *
     * @param string $name  header name
     * @param string $value header value
     *
     * @Given /^I set header "([^"]*)" with value "([^"]*)"$/
     */
    public function iSetHeaderWithValue($name, $value)
    {
        $this->addHeader($name, $value);
    }

    /**
     * Sends HTTP request to specific relative URL.
     *
     * @param string $method request method
     * @param string $url    relative url
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)"$/
     */
    public function iSendARequest($method, $url)
    {
        $url = $this->prepareUrl($url);

        if (version_compare(ClientInterface::VERSION, '6.0', '>=')) {
            $this->request = new Request($method, $url, $this->headers);
        } else {
            $this->request = $this->getClient()->createRequest($method, $url);
            if (!empty($this->headers)) {
                $this->request->addHeaders($this->headers);
            }

        }
        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific relative URL.
     *
     * @param string $method request method
     * @param string $url    relative url
     *
     * @When I send :method requests to ":url" until it responds with :status
     */
    public function iRequestUntilResponseIs($method, $url, $status)
    {
        $retry = 10;
        do {
            $this->iSendARequest($method,$url);
        } while ($this->response->getStatusCode() != $status && $retry-- != 0);
        if ($this->response->getStatusCode() != $status) {
            throw new \Exception("Exceeded retry limit when trying to get status $status");
        }

    }

      /**
     * Sends HTTP request to specific URL with parameters from Table, encoding when 
     * mark with a 'x' in second column.
     * | to_encode |     |
     * | no_encode |  x  |
     *
     * @param string    $method request method
     * @param string    $url    relative url
     * @param TableNode $parameters   table of parameters
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with parameters:$/
     */
    public function iSendARequestWithParameters($method, $url, TableNode $parameters)
    {
        foreach ($parameters->getRowsHash() as $key => $val) {
           if($val != 'x') {
              $url .= (string)$key;  
           } else {
              $key = rawurlencode($key);
              $url .= (string)$key;
           }   
        }
        print_r($url);
        $this->iSendARequest($method, $url);
    }

    /**
     * Sends HTTP request to specific URL with field values from Table.
     *
     * @param string    $method request method
     * @param string    $url    relative url
     * @param TableNode $post   table of post values
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with values:$/
     */
    public function iSendARequestWithValues($method, $url, TableNode $post)
    {
        $url = $this->prepareUrl($url);
        $fields = array();

        foreach ($post->getRowsHash() as $key => $val) {
            $fields[$key] = $this->replacePlaceHolder($val);
        }

        $bodyOption = array(
          'body' => json_encode($fields),
        );

        if (version_compare(ClientInterface::VERSION, '6.0', '>=')) {
            $this->request = new Request($method, $url, $this->headers, $bodyOption['body']);
        } else {
            $this->request = $this->getClient()->createRequest($method, $url, $bodyOption);
            if (!empty($this->headers)) {
                $this->request->addHeaders($this->headers);
            }
        }

        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with raw body from PyString.
     *
     * @param string       $method request method
     * @param string       $url    relative url
     * @param PyStringNode $string request body
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with body:$/
     */
    public function iSendARequestWithBody($method, $url, PyStringNode $string)
    {
        $url = $this->prepareUrl($url);
        $string = $this->replacePlaceHolder(trim($string));

        if (version_compare(ClientInterface::VERSION, '6.0', '>=')) {
            $this->request = new Request($method, $url, $this->headers, $string);
        } else {
            $this->request = $this->getClient()->createRequest(
                $method,
                $url,
                array(
                    'headers' => $this->getHeaders(),
                    'body' => $string,
                )
            );
        }

        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with form data from PyString.
     *
     * @param string       $method request method
     * @param string       $url    relative url
     * @param PyStringNode $body   request body
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with form data:$/
     */
    public function iSendARequestWithFormData($method, $url, PyStringNode $body)
    {
        $url = $this->prepareUrl($url);
        $body = $this->replacePlaceHolder(trim($body));

        $fields = array();
        parse_str(implode('&', explode("\n", $body)), $fields);

        if (version_compare(ClientInterface::VERSION, '6.0', '>=')) {
            $this->request = new Request($method, $url, ['Content-Type' => 'application/x-www-form-urlencoded'], http_build_query($fields, null, '&'));
        } else {
            $this->request = $this->getClient()->createRequest($method, $url);
            /** @var \GuzzleHttp\Post\PostBodyInterface $requestBody */
            $requestBody = $this->request->getBody();
            foreach ($fields as $key => $value) {
                $requestBody->setField($key, $value);
            }
        }

        $this->sendRequest();
    }

    /**
     * Sends GET request to specific URL with query string 
     *
     * @param string       $url    relative url
     * @param TableNode $parameters   request parameters
     *
     * @When I send a GET request to ":url" with:
     */
    public function iSendAGetRequestWithQueryString($url, TableNode $parameters)
    {
        $query_string =  http_build_query($parameters->getRowsHash());
        $request = new Request('GET', $url);
        $request->
        $this->sendRequest();
    }


    /**
     * Checks that response has specific status code.
     *
     * @param string $code status code
     *
     * @Then /^(?:the )?response code should be (\d+)$/
     */
    public function theResponseCodeShouldBe($code)
    {
        $expected = intval($code);
        $actual = intval($this->response->getStatusCode());
        Assertions::assertSame($expected, $actual);
    }

    /**
     * Checks that response body contains specific text.
     *
     * @param string $text
     *
     * @Then the response should contain ":expected"
     * @Then the response should contain:
     */
    public function theResponseShouldContain($expected)
    {
        if ($expected instanceof PyStringNode){
            $expected = $expected->getRaw();
        }
        
        $actual = (string) $this->response->getBody();
        Assert::assertStringMatchesFormat('%A'.$expected.'%A', $actual);
    }

    /**
     * Checks that response body doesn't contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should not contain "([^"]*)"$/
     */
    public function theResponseShouldNotContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/';
        $actual = (string) $this->response->getBody();
        Assertions::assertNotRegExp($expectedRegexp, $actual);
    }

    /**
     * Checks that response body contains JSON from PyString.
     *
     * Do not check that the response body /only/ contains the JSON from PyString,
     *
     * @param PyStringNode $jsonString
     *
     * @throws RuntimeException
     *
     * @Then the response should contain json:
     */
    public function theResponseShouldContainJson(PyStringNode $expected)
    {
        $decoded = json_decode($this->response->getBody(), true);
        if ($decoded === NULL){
            throw new Exception("could not decode response body as json");
        }
        $actual = json_encode($decoded, JSON_PRETTY_PRINT);
        Assert::assertStringMatchesFormat($expected->getRaw(), $actual);
        
    }

    /**
     * Prints last response body.
     *
     * @Then print response
     */
    public function printResponse()
    {
        $request = $this->request;
        $response = $this->response;

        echo sprintf(
            "%s %s => %d:\n%s",
            $request->getMethod(),
            (string) ($request instanceof RequestInterface ? $request->getUri() : $request->getUrl()),
            $response->getStatusCode(),
            (string) $response->getBody()
        );
    }

    /**
     * Prepare URL by replacing placeholders and trimming slashes.
     *
     * @param string $url
     *
     * @return string
     */
    private function prepareUrl($url)
    {
        return ltrim($this->replacePlaceHolder($url), '/');
    }

    /**
     * Sets place holder for replacement.
     *
     * you can specify placeholders, which will
     * be replaced in URL, request or response body.
     *
     * @param string $key   token name
     * @param string $value replace value
     */
    public function setPlaceHolder($key, $value)
    {
        $this->placeHolders[$key] = $value;
    }

    /**
     * Replaces placeholders in provided text.
     *
     * @param string $string
     *
     * @return string
     */
    protected function replacePlaceHolder($string)
    {
        foreach ($this->placeHolders as $key => $val) {
            $string = str_replace($key, $val, $string);
        }

        return $string;
    }

    /**
     * Returns headers, that will be used to send requests.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Adds header
     *
     * @param string $name
     * @param string $value
     */
    protected function addHeader($name, $value)
    {
        if (isset($this->headers[$name])) {
            if (!is_array($this->headers[$name])) {
                $this->headers[$name] = array($this->headers[$name]);
            }

            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Removes a header identified by $headerName
     *
     * @param string $headerName
     *
     *@Given /^I unset header "([^"]*)"$/
     */
    public function removeHeader($headerName)
    {
        if (array_key_exists($headerName, $this->headers)) {
            unset($this->headers[$headerName]);
        }
    }

    private function sendRequest()
    {
        try {
            $this->response = $this->getClient()->send($this->request);
        } catch (RequestException $e) {
            $this->response = $e->getResponse();

            if (null === $this->response) {
                throw $e;
            }
        }
    }

    private function getClient()
    {
        if (null === $this->client) {
            throw new RuntimeException('Client has not been set in WebApiContext');
        }

        return $this->client;
    }
    
    /**
     * Stores value from JSON path in a placeholder.
     * JSON path reference: https://github.com/FlowCommunications/JSONPath#jsonpath-examples
     * 
     * Example: Given I get the "token" from the JSON path "$.oAuth.token"
     * Example: And I get the "user-id" from the JSON path "$.user.id"
     * 
     * @Given I get( the) ":placeholder" from the JSON path ":path"
     */
    public function setPlaceholderFromJson($placeholder, $path){
        if (!isset($this->placeholders)) {
            throw new RuntimeException("Cannot access the Placeholders Repository."
                . " Is the Placeholders extension enabled?");
        }
        $response = $this->response;
        $json = json_decode($response->getBody());
        $value = (new JSONPath($json))->find($path);
        
        $this->placeholders->setPlaceholder($placeholder,$value[0]);
    }

    /**
     * Assert JSON on given JSON Path matches the expected
     * JSON path reference: https://github.com/FlowCommunications/JSONPath#jsonpath-examples
     * 
     * Example: Then the JSON on "$.auth" should match:
     *     """
     *     {
     *         "username": "john",
     *         "password": "johndoe2017"
     *     }
     *     """
     * 
     * @Then the json on ":path" should match:
     */
    public function assertJsonOnPathMatches($path, PyStringNode $expected){

        $response_json = json_decode($this->response->getBody(), true);
        $query_result = (new JSONPath($response_json))->find($path);
        $actual = json_encode($query_result, JSON_PRETTY_PRINT);

        Assert::assertStringMatchesFormat($expected->getRaw(), $actual);
    }
    
}
