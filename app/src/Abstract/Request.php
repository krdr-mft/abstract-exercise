<?php

namespace Abstract;

class Request
{
    public function __construct(private string $path = "", private string $ipAddress = "")
    {
        //todo: validate $path and ip address
    }

    public function getIpAddress():string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    public function getPath():string
    {
        return $this->path;
    }

    public function setPath(string $path)
    {
        $this->path = $path;
    }

}