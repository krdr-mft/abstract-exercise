<?php 

namespace Abstract;

use Abstract\Request;
use Abstract\User;
use Abstract\Util\FileGetContentsWrapper;
use Exception;

/**
 * Parsira i proverava pravila. Pravila su data u vidu JSON fajla koji
 * se nalazi na disku i prosledjuje klasi.
 * 
 * @package Abstract
 */
class Parser
{
    /**
     * Kolekcija parsiranih pravila (workflows)
     *
     * @var array
     */
    private $workflows = [];

    /**
     * Dozvoljeno pravilo "in" - vrednost se nalazi u odredjenom skupu
     * Primer: in($parameter, $value1, $value2,...)
     */
    const METHOD_IN = 'in';
    /**
     * Dozveljeno pravilo 'ip_range' - data ipv4 adresa se nalazi u
     * odredjenom opsegu. Primer: ip_range($ip_address, '100.100.100.1/28')
     */
    const METHOD_IP_RANGE = 'ip_range' ;

    /**
     * Lista dozvoljenih pravila
     *
     * @var array
     */
    private $methodsAvailable = [self::METHOD_IN, self::METHOD_IP_RANGE];

    /**
     * Konstruktor
     *
     * @param FileGetContentsWrapper $fileGetContentsWrapper varper za metodu koja vraca pravila u json formatu
     */
    public function __construct(private FileGetContentsWrapper $fileGetContentsWrapper)
    {

    }

    /**
     * Ucitava putanje ka JSON fajlovima koji sadrze pravila (workflows)
     * Jedno pravilo/workflow po fajlu.
     *
     * @param array $paths lista putanja relativnih ka ovom fajlu.
     * @return void
     */
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

    /**
     * Ucitava, parsira i smesta pravilo/workflow
     *
     * @param String $path putanja ka 
     * @return void
     */
    public function load(String $path)
    {
        $file = $this->fileGetContentsWrapper->fileGetContents($path);

        $workflow = json_decode($file, false);

        $this->workflows[$workflow->WorkflowID] = $workflow;
    }

    /**
     * Lista parsiranih pravila/workflows
     *
     * @return array
     */
    public function getWorkflows()
    {
        return $this->workflows;
    }

    /**
     * Proverava ispunjenosti pravila
     *
     * @param Request $request podaci u HTTP zahtevu
     * @param User $user korisnik
     * @param String $workflowID Koristimo samo ako hocemo da testiramo jedno pravilo
     * @return Boolean
     */
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

    /**
     * Validira jedno pravilo/workflow
     *
     * @param Request $request podaci u HTTP zahtevu
     * @param User $user korisnik
     * @param object $flow
     * @return bool
     */
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

        return $ruleResult;
    }

    /**
     * Razresava vrednosti parametara kako je navedeno u 'workflow' fajlovima
     * Mozemo se osloniti na mogucnost PHP-a da kreira samog sebe iz stringova
     * ali takvo resnje nije bezbedno i sklono greskama.
     * Ovaj pristup je odgovarajuci jer radimo sa ogranicenim, malim,
     * brojem objekata i metoda.
     * 
     * @param Request $request
     * @param User $user
     * @param array $parts
     * @return String ip address, access path or user role
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

    /**
     * Izvrasava validacione izraze u pravilima. Omogucena su validaciona pravila
     * jednakosti ( $value1 == $value2 ), nalazi se u skupu (in($target, $value1, $value2)), 
     * opseg ip adrese (ip_address($ip_adress, $ip_range))
     *
     * @param string $ruleExpression izraz koji sadrzi validaciono pravilo
     * @param array $output ulazne vrednosti koje treba da se validiraju
     * @return boolean
     */
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

    /**
     * Provera da li se ip adresa nalazi u opsegu.
     * $expression sadrzi izraz koji treba proveriti, ali i opseg u kome treba
     * da se nalazi. $input je Array u kome se nalazi vrednost za uporedjivanje.
     * 
     * Npr. ip_range($ip_address, '100.100.100.1/28')
     * Da bi se ovaj izraz pravilno izvrsio, $input mora da sadrzi kljuc 'ip_address':
     * $input = ['ip_address' => '100.100.100.1'].
     * 
     * @param String $expression
     * @param array $input
     * @throws Exception $input ne sadrzi 'ip_address'
     * @return boolean
     */
    private function ip_range($expression, $input)
    {
        if(!array_key_exists('ip_address', $input))
        {
            throw new Exception('IP address (ip_address) not found in $input');
        }

        extract($input);

        $expression = str_replace(['ip_range','(',')','\'',' '],'',$expression);
        $args = explode(',',$expression);
        $range = array_pop($args);

        $result = $this->ip_in_range($ip_address, $range);

        return $result;
    }

    /**
     * Provera da li se odredjeni parametar nalazi medju datim vrednostima.
     * $expression sadrzi izraz koji treba proveriti, ali i vrednosti sa kojima
     * treba da se poredi. $input je Array  u kome se nalazi vrednost za uporedjivanje.
     * 
     * Npr. in($user_role, 'admin', 'superadmin').
     * Da bi se ovaj izraz pravilno izvrsio, $input mora da sadrzi kljuc 'user_role':
     * $input = ['user_role' => 'admin'].
     * String 'admin' ce se uporediti sa ['admin', 'superadmin']
     *
     * @param String $expression
     * @param array $input
     * @throws Exception $input ne sadrzi 'user_role'
     * @return boolean
     */
    private function in($expression, $input)
    {
        if(!array_key_exists('user_role', $input))
        {
            throw new Exception('User role (user_role) not found in $input');
        }

        extract($input);
        $expression = str_replace(['in','(',')','\'',' '],'',$expression);
        $args = explode(',',$expression);
        array_shift($args);
        
        $result = in_array($user_role, $args);
        return $result;
    }

    /**
     * Proverava da li je zahtevana putanja i dozveljena
     * Vrlo jednostavno parsiranje stringova, nista komplikovano
     *
     * @param String $requestPath putanja zahteva
     * @param String $comparePath dozvoljena putanja 
     * @return boolean
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
     * Proverava da li je ip adresa u datom opsegu
     * Preuzeto sa https://gist.github.com/tott/7684443
     * 
     * @param  string $ip    Adresa za proveru u ipv4 formatu, npr. 127.0.0.1
     * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, takodje 127.0.0.1 mora biti prosledjeno, a /32 pretpostavljeno
     * @return boolean true ako je adresa u opsegu / false ako nije
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