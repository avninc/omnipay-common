<?php

/*
 * This file is part of the Omnipay package.
 *
 * (c) Adrian Macneil <adrian@adrianmacneil.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omnipay\Common\Message;

use Guzzle\Http\ClientInterface;
use Omnipay\Common\CreditCard;
use Omnipay\Common\Currency;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Abstract Request
 */
abstract class AbstractRequest implements RequestInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    protected $parameters;

    /**
     * @var \Guzzle\Http\ClientInterface
     */
    protected $httpClient;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $httpRequest;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Create a new Request
     *
     * @param ClientInterface $httpClient  A Guzzle client to make API calls with
     * @param HttpRequest     $httpRequest A Symfony HTTP request object
     */
    public function __construct(ClientInterface $httpClient, HttpRequest $httpRequest)
    {
        $this->httpClient = $httpClient;
        $this->httpRequest = $httpRequest;
        $this->initialize();
    }

    /**
     * Initialize the object with parameters.
     *
     * If any unknown parameters passed, they will be ignored.
     *
     * @param array An associative array of parameters
     */
    public function initialize(array $parameters = array())
    {
        if (null !== $this->response) {
            throw new RuntimeException('Request cannot be modified after it has been sent!');
        }

        $this->parameters = new ParameterBag();
        $this->parameters->replace($parameters);

        return $this;
    }

    public function getParameters()
    {
        return $this->parameters->all();
    }

    protected function getParameter($key)
    {
        return $this->parameters->get($key);
    }

    protected function setParameter($key, $value)
    {
        if (null !== $this->response) {
            throw new RuntimeException('Request cannot be modified after it has been sent!');
        }

        $this->parameters->set($key, $value);

        return $this;
    }

    public function getTestMode()
    {
        return $this->getParameter('testMode');
    }

    public function setTestMode($value)
    {
        return $this->setParameter('testMode', $value);
    }

    /**
     * Validate the request
     *
     * This method is called internally by gateways to avoid wasting time with an API call
     * when the request is clearly invalid.
     *
     * @param array an array of required parameters
     */
    public function validate(array $required)
    {
        foreach ($required as $key) {
            $value = $this->parameters->get($key);
            if (empty($value)) {
                throw new InvalidRequestException("The $key parameter is required");
            }
        }
    }

    public function getCard()
    {
        return $this->getParameter('card');
    }

    public function setCard($value)
    {
        if (is_array($value)) {
            $value = new CreditCard($value);
        }

        return $this->setParameter('card', $value);
    }

    public function getToken()
    {
        return $this->getParameter('token');
    }

    public function setToken($value)
    {
        return $this->setParameter('token', $value);
    }

    public function getAmount()
    {
        return (int) $this->getParameter('amount');
    }

    public function setAmount($value)
    {
        return $this->setParameter('amount', $value);
    }

    public function getAmountDecimal()
    {
        return number_format(
            $this->getAmount() / $this->getCurrencyDecimalFactor(),
            $this->getCurrencyDecimalPlaces(),
            '.',
            ''
        );
    }

    public function getCurrency()
    {
        return strtoupper($this->getParameter('currency'));
    }

    public function setCurrency($value)
    {
        return $this->setParameter('currency', $value);
    }

    public function getCurrencyNumeric()
    {
        if ($currency = Currency::find($this->getCurrency())) {
            return $currency->getNumeric();
        }
    }

    public function getCurrencyDecimalPlaces()
    {
        if ($currency = Currency::find($this->getCurrency())) {
            return $currency->getDecimals();
        }

        return 2;
    }

    private function getCurrencyDecimalFactor()
    {
        return pow(10, $this->getCurrencyDecimalPlaces());
    }

    public function getDescription()
    {
        return $this->getParameter('description');
    }

    public function setDescription($value)
    {
        return $this->setParameter('description', $value);
    }

    public function getTransactionId()
    {
        return $this->getParameter('transactionId');
    }

    public function setTransactionId($value)
    {
        return $this->setParameter('transactionId', $value);
    }

    public function getGatewayReference()
    {
        return $this->getParameter('gatewayReference');
    }

    public function setGatewayReference($value)
    {
        return $this->setParameter('gatewayReference', $value);
    }

    public function getClientIp()
    {
        return $this->getParameter('clientIp');
    }

    public function setClientIp($value)
    {
        return $this->setParameter('clientIp', $value);
    }

    public function getReturnUrl()
    {
        return $this->getParameter('returnUrl');
    }

    public function setReturnUrl($value)
    {
        return $this->setParameter('returnUrl', $value);
    }

    public function getCancelUrl()
    {
        return $this->getParameter('cancelUrl');
    }

    public function setCancelUrl($value)
    {
        return $this->setParameter('cancelUrl', $value);
    }

    public function getResponse()
    {
        if (null === $this->response) {
            throw new RuntimeException('You must call send() before accessing the Response!');
        }

        return $this->response;
    }
}