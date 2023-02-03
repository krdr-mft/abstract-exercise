<?php
namespace Abstract;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /**
     * @covers Request
     */
    public function testClassConstructor()
    {
        $request = new Request('/user/admin/test/user','100.100.100.1');

        $this->assertIsString($request->getPath());
        $this->assertSame('/user/admin/test/user', $request->getPath());

        $this->assertIsString($request->getIpAddress());
        $this->assertSame('100.100.100.1', $request->getIpAddress());
    }

    /**
     * @covers Request
     */
    public function testPathSetter()
    {
        $request = new Request();
        $request->setPath('/admin/test');

        $this->assertIsString($request->getPath());
        $this->assertSame('/admin/test',$request->getPath());
    }

    /**
     * @covers Request
     */
    public function testIpSetter()
    {
        $request = new Request();
        $request->setIpAddress('100.100.100.1');

        $this->assertIsString($request->getIpAddress());
        $this->assertSame('100.100.100.1',$request->getIpAddress());
    }
}