<?php

namespace Abstract;

/**
 * Predstavlja HTTP zahtev
 * 
 * @package Abstract
 */
class Request
{
    /**
     * Konstrukot
     *
     * @param string $path putanja kojoj se pristupa
     * @param string $ipAddress ip adresa sa koje se pristupa
     */
    public function __construct(private string $path = "", private string $ipAddress = "")
    {
        //todo: validate $path and ip address
    }

    /**
     * IP adresa
     *
     * @return string
     */
    public function getIpAddress():string
    {
        return $this->ipAddress;
    }

    /**
     * IP adresa, ipv4 adresa
     *
     * @param string $ipAddress
     * @return void
     */
    public function setIpAddress(string $ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * Putanja ka resursu, bez domena
     *
     * @return string
     */
    public function getPath():string
    {
        return $this->path;
    }

    /**
     * Putanja ka resursu, bez domena
     *
     * @param string $path
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

}