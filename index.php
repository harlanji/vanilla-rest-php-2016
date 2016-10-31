<?php

require('core.php');
require('http.php');


function fakeHandler ($db, $method, $url, $headers, $postBody) {
  if ($url == "/success") {
    return new Response(200, "Hello!");
  } else if ($url == "/error") {
    return new Response(500, "Error!");
  }
}


$db = setup(new PDO('sqlite::memory:'));

handleRequest(fakeHandler, $db);

?>
