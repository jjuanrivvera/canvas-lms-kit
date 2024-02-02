<?php

namespace CanvasLMS\Http;

use CanvasLMS\Config;
use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Exceptions\MissingApiKeyException;
use CanvasLMS\Exceptions\MissingBaseUrlException;

/**
 *
 */
class HttpClient implements HttpClientInterface
{
    /**
     * @var ClientInterface
     */
    private ClientInterface $client;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ClientInterface|null $client
     * @param LoggerInterface|null $logger
     */
    public function __construct(ClientInterface $client = null, LoggerInterface $logger = null)
    {
        $this->client = $client ?? new \GuzzleHttp\Client();
        $this->logger = $logger ?? new \Psr\Log\NullLogger();
    }

    /**
     * Get request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->request('GET', $url, $options);
    }

    /**
     * Post request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->request('POST', $url, $options);
    }

    /**
     * Put request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function put(string $url, array $options = []): ResponseInterface
    {
        return $this->request('PUT', $url, $options);
    }

    /**
     * Patch request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function patch(string $url, array $options = []): ResponseInterface
    {
        return $this->request('PATCH', $url, $options);
    }

    /**
     * delete request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function delete(string $url, array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $url, $options);
    }

    /**
     * Make an HTTP request
     * @param string $method
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     * @throws CanvasApiException
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        try {
            $requestOptions = $this->prepareDefaultOptions($url, $options);

            return $this->client->request($method, $url, $requestOptions);
        } catch (RequestException $e) {
            $this->logger->error($e->getMessage());
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new CanvasApiException($e->getMessage(), $e->getCode(), $response['errors'] ?? []);
        } catch (GuzzleException $e) {
            throw new CanvasApiException($e->getMessage(), $e->getCode(), []);
        }
    }

    /**
     * @param string $url
     * @param mixed[] $options
     * @return mixed[]
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    private function prepareDefaultOptions(string &$url, array $options): array
    {
        $appKey = Config::getAppKey();
        if (!$appKey) {
            throw new MissingApiKeyException();
        }

        $baseUrl = Config::getBaseUrl();
        if (!$baseUrl) {
            throw new MissingBaseUrlException();
        }

        $fullUrl = $baseUrl .
            'api/' .
            rtrim(Config::getApiVersion(), '/') .
            '/' .
            ltrim($url, '/');

        $options['headers']['Authorization'] = 'Bearer ' . $appKey;
        $url = $fullUrl;

        return $options;
    }
}
