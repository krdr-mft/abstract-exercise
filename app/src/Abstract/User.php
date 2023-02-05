<?php
namespace Abstract;

/**
 * Predstavlja korisnika u sistemu
 * 
 * @package Abstract
 */
class User
{
    /**
     * Administratorska rola
     */
    public const ROLE_ADMIN = 'ADMIN';
    /**
     * Superadmin rola
     */
    public const ROLE_SUPERADMIN = "SUPERADMIN";

    /**
     * Konstruktor
     *
     * @param string $roleName korisnicka rola korisnika
     */
    public function __construct(private string $roleName = "")
    {

    }
    
    /**
     * Korisnicka rola
     * 
     * TODO: Validacija
     *
     * @param string $roleName
     * @return void
     */
    public function setRole(string $roleName)
    {
        $this->roleName = $roleName;
    }

    /**
     * Korisnicka rola
     *
     * @return string
     */
    public function getRole():string
    {
        return $this->roleName;
    }

}