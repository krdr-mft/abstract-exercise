<?php 
namespace Abstract;

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @covers Parser
     */
    public function testParserConstructorNoFile()
    {
        $this->expectException(\Exception::class);
        $parser = new Parser([]);
    }

    /**
     * @covers Parser
     */
    public function testParserConstructorWrongFile()
    {
        $this->expectException(\Exception::class);
        $parser = new Parser(['/test']);
    }

    /**
     * @covers Parser
     */
    public function testParserConstructor()
    {
        $parser = new Parser(['mocks/workflow1.json']);
        $workflows = $parser->getWorkflows();

        $this->assertCount(1, $workflows);
    
        $wowrkflow = $workflows[0];

        $this->assertObjectHasAttribute('WorkflowID', $workflow);
        $this->assertObjectHasAttribute('WorkflowName', $workflow);
        $this->assertObjectHasAttribute('Path', $workflow);
        $this->assertObjectHasAttribute('Params', $workflow);
        $this->assertObjectHasAttribute('Rules', $workflow);

        $mockObject = $this->mockJson1();

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
    public function testParserConstructorMultiple()
    {
        //$this->expectException(\Exception::class);
        $parser = new Parser(['mocks/workflow1.json','mocks/workflow2.json']);
        $workflows = $parser->getWorkflows();

        $this->assertCount(2, $workflows);
    }

    /**
     * @covers Parser
     */
    public function testWorkflowValidationFailOnPath()
    {
        $user = new User(User::ROLE_ADMIN);
        $request = new Request('/user/test/user','100.100.100.100');

        $parser = new Parser(['mocks/workflow1.json']);
        $result = $parser->validate();

        $this->assertFalse($result);
    }

    private function mockJson1()
    {
        $json = <<<JSON
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

        return json_decode($json);
    }



}