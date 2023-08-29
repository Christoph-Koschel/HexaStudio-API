<?php

class Request
{
    public int $method;
    public string $logicalName;
    public string $token;
    public string $authName;
    public RequestProperties $arguments;
}

class RequestProperties
{
    private array $data;
    private string $logicalName;

    public function __construct(array $data, string $logicalName)
    {
        $this->data = $data;
        $this->logicalName = $logicalName;
    }

    public function get(string $name): mixed
    {
        if (!isset($this->data[$name])) {
            res_set_attribute("error", "Missing argument '$name'");
            log_error($this->logicalName, "Request failed with code 'STATUS_CORRUPTED_REQUEST'");
            res_send(STATUS_CORRUPTED_REQUEST);
        }

        return $this->data[$name];
    }
}

class FetchRequest extends Request
{
    public static function map(Request $req): FetchRequest
    {
        $map = new FetchRequest();
        $map->method = $req->method;
        $map->arguments = $req->arguments;
        $map->logicalName = $req->logicalName;
        $map->token = $req->token;
        $map->authName = $req->authName;

        return $map;
    }
}

class InsertRequest extends Request
{
    public static function map(Request $req): InsertRequest
    {
        $map = new InsertRequest();
        $map->method = $req->method;
        $map->arguments = $req->arguments;
        $map->logicalName = $req->logicalName;
        $map->token = $req->token;
        $map->authName = $req->authName;

        return $map;
    }
}

class UpdateRequest extends Request
{
    public static function map(Request $req): UpdateRequest
    {
        $map = new UpdateRequest();
        $map->method = $req->method;
        $map->arguments = $req->arguments;
        $map->logicalName = $req->logicalName;
        $map->token = $req->token;
        $map->authName = $req->authName;

        return $map;
    }
}

class DeleteRequest extends Request
{
    public static function map(Request $req): DeleteRequest
    {
        $map = new DeleteRequest();
        $map->method = $req->method;
        $map->arguments = $req->arguments;
        $map->logicalName = $req->logicalName;
        $map->token = $req->token;
        $map->authName = $req->authName;

        return $map;
    }
}