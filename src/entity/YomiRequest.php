<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 22:10
 */

namespace sinri\yomi\entity;


class YomiRequest
{
    const YOMI_REQUEST_TYPE_ORDER = "ORDER";
    const YOMI_REQUEST_TYPE_REQUEST = "REQUEST";

    const YOMI_REQUEST_HANDLE_DONE = "DONE";
    const YOMI_REQUEST_HANDLE_FAILED = "FAILED";

    /**
     * @return mixed
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * @return mixed
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    protected $requestType;
    protected $requestData;

    public function __construct($type, $data)
    {
        $this->requestType = $type;
        $this->requestData = $data;
    }

    public function handle($callback)
    {
        $result = call_user_func_array($callback, [$this->requestData]);
        return $result;
    }
}