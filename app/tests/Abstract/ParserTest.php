<?php 
namespace Abstract;

use PHPUnit\Framework\TestCase;
use Abstract\Util\FileGetContentsWrapper;

class ParserTest extends TestCase
{
    private FileGetContentsWrapper $fileGetContentsWrapper;

    protected function setUp():void
    {
        $this->fileGetContentsWrapper = $this->createMock( FileGetContentsWrapper::class );

        $this->fileGetContentsWrapper->method('fileGetContents')->will(
            $this->returnCallback(
                function($arg)
                {
                    if($arg == 'workflow1.json')
                       return $this->mockJson1();
                    elseif($arg == 'workflow2.json')
                       return $this->mockJson2();
                    else
                        throw new \Exception("File not found");
                }
            )
        );

        parent::setUp();
    }

    /**
     * @covers Parser
     */
    public function testParserConstructor()
    {
        $parser = $this->getSot();

        $parser->loadPaths(['workflow1.json']);
        $workflows = $parser->getWorkflows();

        $this->assertCount(1, $workflows);
    
        $workflow = array_shift($workflows);

        $this->assertObjectHasAttribute('WorkflowID', $workflow);
        $this->assertObjectHasAttribute('WorkflowName', $workflow);
        $this->assertObjectHasAttribute('Path', $workflow);
        $this->assertObjectHasAttribute('Params', $workflow);
        $this->assertObjectHasAttribute('Rules', $workflow);

        $mockObject = json_decode($this->mockJson1());

        $this->assertSame($workflow->WorkflowID, $mockObject->WorkflowID);
        $this->assertSame($workflow->WorkflowName, $mockObject->WorkflowName);
        $this->assertSame($workflow->Path, $mockObject->Path);

        $this->assertSame($workflow->Params[0]->Name,       $mockObject->Params[0]->Name);
        $this->assertSame($workflow->Params[0]->Expression, $mockObject->Params[0]->Expression);

        $this->assertSame($workflow->Rules[0]->RuleName,    $mockObject->Rules[0]->RuleName);
        $this->assertSame($workflow->Rules[0]->Expression,  $mockObject->Rules[0]->Expression);

        $this->assertSame($workflow->Params[1]->Name,       $mockObject->Params[1]->Name);
        $this->assertSame($workflow->Params[1]->Expression, $mockObject->Params[1]->Expression);

        $this->assertSame($workflow->Rules[1]->RuleName,   $mockObject->Rules[1]->RuleName);
        $this->assertSame($workflow->Rules[1]->Expression, $mockObject->Rules[1]->Expression);
    }

    /**
     * @covers Parser
     */
    public function testLoadPathsMultiple()
    {
        //$this->expectException(\Exception::class);
        $parser = $this->getSot();

        $parser->loadPaths(['workflow1.json', 'workflow2.json']);
        $workflows = $parser->getWorkflows();

        $this->assertCount(2, $workflows);
        $this->assertNotCount(1, $workflows);
    }

    /**
     * @covers Parser
     */
    public function testLoadPathsEmpty()
    {
        $parser = $this->getSot();

        $this->expectException(\Exception::class);

        $parser->loadPaths([]);
        $workflows = $parser->getWorkflows();

        $this->assertCount(2, $workflows);
        $this->assertNotCount(1, $workflows);
    }

    /**
     * @covers Parser
     */
    public function testWorkflowValidationFailOnPath()
    {
        $user = new User(User::ROLE_ADMIN);
        $request = new Request('/user/test/user','100.100.100.100');

        $parser = $this->getSot();

        $parser->loadPaths(['workflow1.json']);
        $result = $parser->validate($request, $user);

        $this->assertFalse($result);
    }

    /**
     * @covers Parser
     */
    public function testWorkflowValidationFailOnIp()
    {
        $user = new User(User::ROLE_ADMIN);
        $request = new Request('/admin/test/user','100.100.100.10');

        $parser = $this->getSot();

        $parser->loadPaths(['workflow1.json']);
        $result = $parser->validate($request, $user);

        $this->assertFalse($result);
    }

    /**
     * @covers Parser
     */
    public function testWorkflowValidationFailOnRole()
    {
        $user = new User('test');
        $request = new Request('/admin/test/user','100.100.100.10');

        $parser = $this->getSot();

        $parser->loadPaths(['workflow1.json']);
        $result = $parser->validate($request, $user);

        $this->assertFalse($result);
    }

    /**
     * @covers Parser
     */
    public function testWorkflowValidationPositive()
    {
        $user = new User(User::ROLE_ADMIN);
        $request = new Request('/admin/test/user','100.100.100.100');

        $parser = $this->getSot();

        $parser->loadPaths(['workflow1.json']);
        $result = $parser->validate($request, $user);

        $this->assertTrue($result);
    }

    /**
     * @covers Parser
     */
    public function testWorkflow2ValidationFailOnPath()
    {
        $user = new User(User::ROLE_ADMIN);
        $request = new Request('/user/test/user','100.100.100.10');

        $parser = $this->getSot();

        $parser->loadPaths(['workflow2.json']);
        $result = $parser->validate($request, $user);

        $this->assertFalse($result);
    }

    /**
     * @covers Parser
     */
    public function testWorkflow2ValidationFailOnIp()
    {
        $user = new User(User::ROLE_ADMIN);
        $request = new Request('/admin/test/user','100.100.100.50');

        $parser = $this->getSot();

        $parser->loadPaths(['workflow2.json']);
        $result = $parser->validate($request, $user);

        $this->assertFalse($result);
    }

    /**
     * @covers Parser
     */
    public function testWorkflow2ValidationFailOnRole()
    {
        $user = new User('test');
        $request = new Request('/admin/test/user','100.100.100.10');

        $parser = $this->getSot();

        $parser->loadPaths(['workflow2.json']);
        $result = $parser->validate($request, $user);

        $this->assertFalse($result);
    }

    /**
     * @covers Parser
     */
    public function testWorkflow2ValidationPositive()
    {
        $user = new User(User::ROLE_ADMIN);
        $request = new Request('/admin/test/user','100.100.100.1');

        $parser = $this->getSot();

        $parser->loadPaths(['workflow2.json']);
        $result = $parser->validate($request, $user);

        $this->assertTrue($result);

        $user = new User(User::ROLE_SUPERADMIN);
        $result = $parser->validate($request, $user);
    }



    private function getSot()
    {
        return new Parser($this->fileGetContentsWrapper);
    }

    private function mockJson1()
    {
        $json = <<<'JSON'
            {
                "WorkflowID": 1,
                "WorkflowName": "Allow only specific IP for ADMIN role",
                "Path": "/admin/*",
                "Params": [{
                        "Name": "ip_address",
                        "Expression": "$request.getIpAddress"
                    },
                    {
                        "Name": "user_role",
                        "Expression": "$user.getRole"
                    }
                ],
                "Rules": [{
                        "RuleName": "Allow only specific IP",
                        "Expression": "$ip_address == '100.100.100.100'"
                    },
                    {
                        "RuleName": "Check role",
                        "Expression": "$user_role == 'ADMIN'"
                    }
                ]
            }
        JSON;

        return $json;
    }

    private function mockJson2()
    {
        $json = <<<'JSON'
        {
            "WorkflowID": 2,
            "WorkflowName": "Allow only specific IPs for ADMIN and SUPER_ADMIN roles",
            "Path": "/admin/*",
            "Params": [{
                    "Name": "ip_address",
                    "Expression": "$request.getIpAddress"
            	},
            	{
                    "Name": "user_role",
                    "Expression": "$user.getRole"
                }
            ],
            "Rules": [{
                    "RuleName": "Allow only specific IP",
                    "Expression": "ip_range($ip_address, '100.100.100.1/28')"
            	},
            	{
                    "RuleName": "Check role",
                    "Expression": "in($user_role, 'ADMIN', 'SUPER_ADMIN')"
                }
            ]
        }
        JSON;

        return $json;
    }




}