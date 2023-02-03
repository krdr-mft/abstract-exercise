<?php
namespace Abstract;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * @covers User
     */
    public function testClassConstructor()
    {
        $user = new User(User::ROLE_ADMIN);

        $this->assertIsString($user->getRole());
        $this->assertSame(User::ROLE_ADMIN, $user->getRole());
    }

    /**
     * @covers User
     */
    public function testSetMethod()
    {
        $user = new User();
        $user->setRole(User::ROLE_SUPERADMIN);

        $this->assertIsString($user->getRole());
        $this->assertSame(User::ROLE_SUPERADMIN, $user->getRole());

        $this->assertNotSame(User::ROLE_ADMIN, $user->getRole());
    }
}