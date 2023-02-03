<?php 


use Abstract\Parser;
use Abstract\User;
use Abstract\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$paths = [];
$paths[] = '../lib/workflow1.json';
$paths[] = '../lib/workflow2.json';

$user = new User(User::ROLE_ADMIN);
$request = new Request('/admin/test/user','100.100.100.100');

$parser = new Parser($paths);
$result = $parser->validate($request, $user);

echo $result?'User validated':'User not validated';
