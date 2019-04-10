<?php

namespace Fostenslave\NalogkaDealsSDK\Request;

class DeleteTrackDealRequest extends AbstractRequest
{
    private $id;
    private $trackingId;

    public function id($id)
    {
        $this->id = $id;

        return $this;
    }

    public function trackingId($trackingId)
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    protected function getHttpMethod()
    {
        return self::METHOD_DELETE;
    }

    protected function getHttpPath()
    {
        return "/deals/{$this->id}/tracks/{$this->trackingId}";
    }

    /**
     * @return null
     * @throws \Fostenslave\NalogkaDealsSDK\Exception\ApiErrorException
     * @throws \Fostenslave\NalogkaDealsSDK\Exception\NalogkaSdkException
     * @throws \Fostenslave\NalogkaDealsSDK\Exception\ServerErrorException
     */
    public function request()
    {
        return parent::request();
    }
}