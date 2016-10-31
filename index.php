<?php

require_once('core.php');
require_once('http.php');


// NOTE DB is in memory so it will be reset with each request... can persist to disk eg. in /tmp
$db = new PDO('sqlite::memory:');

setup($db);

$alice = new User(1, 'alice');
$alice->insert($db);

$bob = new User(2, 'bob');
$bob->insert($db);

handleRequest('restHandler', $db);

?>
