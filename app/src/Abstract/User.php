<?php
namespace Abstract;

class User
{
    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_SUPERADMIN = "SUPERADMIN";

    public function __construct(private string $roleName = "")
    {

    }

    public function setRole(string $roleName)
    {
        $this->roleName = $roleName;
    }

    public function getRole():string
    {
        return $this->roleName;
    }

}