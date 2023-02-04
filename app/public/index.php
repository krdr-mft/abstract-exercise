<?php 


use Abstract\Parser;
use Abstract\User;
use Abstract\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$paths = [];
$paths[] = '../lib/workflow1.json';
$paths[] = '../lib/workflow2.json';

test(User::ROLE_ADMIN,      '/admin/test/user', '100.100.100.100', 'Testing Workflow 1', $paths );
test(User::ROLE_SUPERADMIN, '/admin/test/user', '100.100.100.100', 'Testing Workflow 1', $paths );
test(User::ROLE_ADMIN,      '/user/test/user', '100.100.100.100',  'Testing Workflow 1', $paths );
test(User::ROLE_ADMIN,      '/admin/test/user', '100.100.100.90',  'Testing Workflow 1', $paths );


test(User::ROLE_ADMIN,      '/admin/test/user', '100.100.100.10', 'Testing Workflow 2', $paths );
test(User::ROLE_SUPERADMIN, '/admin/test/user', '100.100.100.10', 'Testing Workflow 2', $paths );
test('Fake role',           '/admin/test/user', '100.100.100.10', 'Testing Workflow 2', $paths );
test(User::ROLE_ADMIN,      '/user/test/user',  '100.100.100.10', 'Testing Workflow 2', $paths );
test(User::ROLE_ADMIN,      '/admin/test/user', '100.100.100.1',  'Testing Workflow 2', $paths );
test(User::ROLE_SUPERADMIN, '/admin/test/user', '100.100.100.28',  'Testing Workflow 2', $paths );



function test($role, $path, $ip, $title, $paths)
{
  echo "<b>{$title}</b><br>";
  echo "<b>User role:</b> {$role}<br>";
  echo "<b>Request path:</b> '{$path}' <br>";
  echo "<b>Request ip:</b> '{$ip}'<br>";

  $user = new User($role);
  $request = new Request($path,$ip);
  
  $parser = new Parser($paths);
  $result = $parser->validate($request, $user);
  
  echo "Result:  ".print_r($result,true)."<br>";
  
  echo $result?'<span style="color: green">User validated</span>':'<span style="color: red">User not validated</span>';
  echo "<hr>";
}