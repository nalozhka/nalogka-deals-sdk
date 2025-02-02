<?php

namespace Fostenslave\NalogkaDealsSDK;

use Psr\Log\LoggerInterface;
use Fostenslave\NalogkaDealsSDK\Errors\AbstractError;
use Fostenslave\NalogkaDealsSDK\Exception\ApiErrorException;
use Fostenslave\NalogkaDealsSDK\Exception\NalogkaSdkException;
use Fostenslave\NalogkaDealsSDK\Exception\ServerErrorException;
use Fostenslave\NalogkaDealsSDK\Serialization\AbstractSerializationComponent;

class ApiClient
{
    private $baseUrl;

    private $parameters;

    /**
     * @var AbstractSerializationComponent
     */
    private $serializationComponent;


    private $logger;


    public function __construct($baseUrl, $parameters = [], $serializationComponent, LoggerInterface $logger = null)
    {
        $this->baseUrl = $baseUrl;

        $this->parameters = $parameters;

        $this->serializationComponent = $serializationComponent;

        $this->logger = $logger;
    }

    /**
     * @param $method
     * @param $path
     * @param array $data
     * @return array|null|object
     * @throws NalogkaSdkException
     * @throws ApiErrorException
     * @throws ServerErrorException
     */
    public function request($method, $path, $data = [])
    {
        $method = strtoupper($method);

        $headers = isset($this->parameters['headers']) ? $this->parameters['headers'] : [];

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        if ($method === "GET" && $data) {
            $url .= "?" . http_build_query($data);
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method !== "GET") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                $data_string = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                $headers['Content-Type'] = 'application/json';
                $headers['Content-Length'] = strlen($data_string);
            }
        }

        $curlReadyHeaders = [];
        foreach ($headers as $headerName => $headerValue) {
            $curlReadyHeaders[] = "{$headerName}: {$headerValue}";
        }
        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTREDIR, 7);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlReadyHeaders);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $rawResponse = curl_exec($ch);

        $responseInfo = curl_getinfo($ch);

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug("Метод: {method} \n Данные запроса: {data} \n Ответ сервера: {rawResponse} \n Данные ответа: {responseInfo}", [
                'method' => $method,
                'data' => $data,
                'rawResponse' => $rawResponse,
                'responseInfo' => $responseInfo,
                'curlError' => curl_error($ch),
            ]);
        }

        if (empty($rawResponse)) {
            if ($this->isErrorResponse($responseInfo['http_code'])) {
                throw new ServerErrorException($responseInfo['http_code'], "Не удалось получить ответ от сервера. HTTP код: {$responseInfo['http_code']}");
            } else {
                return null;
            }
        }

        $decodedResponse = json_decode($rawResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ServerErrorException($responseInfo['http_code'], "Не удалось декодировать ответ");
        }

        $deserializedResponse = $this->serializationComponent->deserialize($decodedResponse);

        if ($deserializedResponse instanceof AbstractError) {
            throw new ApiErrorException($deserializedResponse, $deserializedResponse->message, $responseInfo['http_code']);
        }

        return $deserializedResponse;
    }

    private function isErrorResponse($httpCode)
    {
        if (!in_array($httpCode, [200, 201, 202, 203, 204, 205, 206, 207, 208, 226])) {
            return true;
        }

        return false;
    }
}