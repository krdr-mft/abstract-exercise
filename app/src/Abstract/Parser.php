<?php 

namespace Abstract;

use Abstract\Request;
use Abstract\User;
use Exception;

class Parser
{
    private $workflows = [];

    const METHOD_IN = 'in';
    const METHOD_IP_RANGE = 'ip_range' ;

    private $methodsAvailable = [self::METHOD_IN, self::METHOD_IP_RANGE];

    public function __construct(private array $paths = [])
    {
        $this->loadPaths($this->paths);
    }

    public function loadPaths( array $paths)
    {
        if(empty($paths))
        {
            throw new Exception('No paths provided');
        }

        foreach($paths as $path)
        {
            $this->load($path);
        }
    }

    public function load(String $path)
    {
        if(!file_exists($path))
        {
            throw new Exception(sprintf('Couldnt read file %s', $path) );
        }

        $file = file_get_contents($path);

        $workflow = json_decode($file, false);

        $this->workflows[$workflow->WorkflowID] = $workflow;
    }

    public function getWorkflows()
    {
        return $workflows;
    }

    public function validate(Request $request, User $user, $workflowID = null)
    {
        $workflows = [];

        if(!is_null($workflowID))
        {
            if(!isset($this->workflows[$workflowID]))
            {
                throw new Exception( sprintf('Unknown workflow ID %s', $workflowID));
            }
  
            return $this->validateWorkflow($request, $user, $this->workflows[$workflowID]);
        }

        $workflows = $this->workflows;

        $result = false;

        foreach($workflows as $flow)
        {
            $result =  $result || $this->validateWorkflow($request, $user, $flow);
        }

        return $result;
    }

    private function validateWorkflow(Request $request, User $user, $flow)
    {
        $compare = $this->comparePaths($request->getPath(),$flow->Path);

        //compare failure, path doesn't applies to this workflow
        if(!$compare)
            return false;

        $output = [];

        
        foreach($flow->Params as $param)
        {
            $parts = explode('.',$param->Expression);

            $output[$param->Name] = $this->resolve($request, $user, $parts);
        }

        $ruleResult = true;

        foreach ($flow->Rules as $rule)
        {
            $ruleResult = $ruleResult && $this->evalExpression($rule->Expression,$output);
        }

        return $ruleResult ;
    }

    /**
     * Resolves values of parameters as stated in workflow files.
     * We could relly on PHP's ability to create itself from the strings
     * but that solution is unsafe and error prone. As long as we work
     * with finite number of object, this is fine.
     *
     * @param Request $request
     * @param User $user
     * @param array $parts
     * @return void
     */
    private function resolve(Request $request, User $user, array $parts)
    {
        $objectName = $parts[0];
        $methodName = $parts[1];

        switch($objectName)
        {
            case '$request':
                $object = $request;
                break;
            case '$user':
                $object = $user;
                break;
            default:
                throw new Exception( sprintf("Unknown type %s",$objectName));
        }

        return $object->{$methodName}();
    }

    private function evalExpression(string $ruleExpression, array $output)
    {
        extract($output);

        //checking for most obvious rule
        if(str_contains( $ruleExpression, "==" ))
        {
            eval('$ruleResult = ('.$ruleExpression.');');

            return $ruleResult;
        }

        foreach($this->methodsAvailable as $methodName)
        {

            if(str_starts_with( $ruleExpression, $methodName ))
            {
                if($this->$methodName($ruleExpression, $output))
                    return true;
            }
        }

        return false;

    }

    private function ip_range($expression, $input)
    {
        extract($input);

        $expression = str_replace(['ip_range','(',')','\'',' '],'',$expression);
        $args = explode(',',$expression);
        $range = array_pop($args);

        $result = $this->ip_in_range($ip_address, $range);

        return $result;
    }

    private function in($expression, $input)
    {
        extract($input);
        $expression = str_replace(['in','(',')','\'',' '],'',$expression);
        $args = explode(',',$expression);
        array_shift($args);
        
        $result = in_array($user_role, $args);
        return $result;
    }

    /**
     * Ultra simple string comparasion.
     *
     * @param String $requestPath
     * @param String $comparePath
     * @return void
     */
    private function comparePaths(String $requestPath, String $comparePath)
    {
        $starPosition = strpos($comparePath,'*');

        if($starPosition !== false)
        {
            $requestPath = trim(substr($requestPath,0,$starPosition),'/ ');
        }

        $comparePath = trim(substr($comparePath,0,$starPosition),'/ ');

        return $comparePath == $requestPath;      

    }

    /**
     * Check if a given ip is in a network
     * Function taken from https://gist.github.com/tott/7684443
     * 
     * @param  string $ip    IP to check in IPV4 format eg. 127.0.0.1
     * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
     * @return boolean true if the ip is in this range / false if not.
     */
    private function ip_in_range( $ip, $range ) 
    {
        if ( strpos( $range, '/' ) == false ) 
        {
            $range .= '/32';
        }
        // $range is in IP/CIDR format eg 127.0.0.1/24
        list( $range, $netmask ) = explode( '/', $range, 2 );
        $range_decimal = ip2long( $range );
        $ip_decimal = ip2long( $ip );
        $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
        $netmask_decimal = ~ $wildcard_decimal;

        $result = ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) ;

        return $result;
    }
}