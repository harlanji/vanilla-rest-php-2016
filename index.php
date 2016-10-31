<?php

require_once('core.php');
require_once('http.php');


function fakeHandler ($db, $method, $url, $headers, $postBody) {
  if ($url == "/success") {
    return new Response(200, "Hello!");
  } else if ($url == "/error") {
    return new Response(500, "Error!");
  }
}


// NOTE DB is in memory so it will be reset with each request... can persist to disk eg. in /tmp
$db = new PDO('sqlite::memory:');

setup($db);

$alice = new User(1, 'alice');
$alice->insert($db);

$bob = new User(2, 'bob');
$bob->insert($db);

handleRequest('restHandler', $db);

?>
