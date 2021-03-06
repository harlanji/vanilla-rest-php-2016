<?php

require_once('core.php');

define("GET_HEADERS", array('accept' => 'application/json'));
define("JSON_HEADERS", array('content-type' => 'application/json', 'accept' => 'application/json'));



test();


function test () {
  // app model
  $db = setup(new PDO('sqlite::memory:'));
  test_model($db);

  // -- app rest handlers
  $db = setup(new PDO('sqlite::memory:'));
  test_collection($db);
  
  $db = setup(new PDO('sqlite::memory:'));
  test_create($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_create_exists($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_create_invalid($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_read($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_read_none($db);
  
  $db = setup(new PDO('sqlite::memory:'));
  test_update($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_update_none($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_update_invalid($db); 

  $db = setup(new PDO('sqlite::memory:'));
  test_delete($db);

  // -- http handler logic
  test_encoders();
  
  $db = setup(new PDO('sqlite::memory:'));
  test_get_handleRequest_ok($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_get_handleRequest_error($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_get_handleRequest_404($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_get_handleRequest_weird_accept($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_post_handleRequest_ok($db);
}



function fakeHandler ($db, $method, $url, $headers, $postBody) {
  if ($url == "/success") {
    return new Response(200, "Hello!");
  } else if ($url == "/error") {
    return new Response(500, "Error!");
  } else if ($method == "POST" && $url == "/post") {
    return new Response(200, $postBody);
  }
}

// dummy
function http_status_code($code) {

}

function test_encoders () {
  assert(encodeBody("text/plain", "a") == "a");
  assert(encodeBody("application/json", "a") == "\"a\"");

  assert(decodeBody("text/plain", "a") == "a");
  assert(decodeBody("application/json", "\"a\"") == "a");


  $data = array("a" => 1);
  assert(strpos("Array", encodeBody("text/plain", $data)) == 0);
  assert(encodeBody("application/json", $data) == "{\"a\":1}");

  assert(decodeBody("text/plain", "a") == "a");
  assert(decodeBody("application/json", "{\"a\":1}") == $data);




}

function test_get_handleRequest_ok ($db) {
  $headers = array();

  $response = handleRequest_raw("GET", "/success", GET_HEADERS, null, 'fakeHandler', $db);

  echo 'response: ' . $response;


  assert($response->status == 200);
  assert($response->headers['content-type'] == 'application/json');
}

function test_get_handleRequest_404 ($db) {
  $headers = array();

  $response = handleRequest_raw("GET", "/fdsafdfas", GET_HEADERS, null, 'fakeHandler', $db);

  echo 'response: ' . $response;


  assert($response->status == 404);
  assert($response->headers['content-type'] == 'application/json');

}

function test_get_handleRequest_error ($db) {
  $headers = array();

  $response = handleRequest_raw("GET", "/error", GET_HEADERS, null, 'fakeHandler', $db);

  echo 'response: ' . $response;


  assert($response->status == 500);
  assert($response->headers['content-type'] == 'application/json');

}

function test_get_handleRequest_weird_accept ($db) {
  $headers = array();

  $response = handleRequest_raw("GET", "/success", array('accept' => 'test/weird-format'), null, 'fakeHandler', $db);

  echo 'response: ' . $response;

  assert($response->status == 406);
  assert($response->headers['content-type'] == 'text/plain');

}

function test_post_handleRequest_ok ($db) {
  $headers = array();

  $response = handleRequest_raw("POST", "/post", JSON_HEADERS, '"some stuff"', 'fakeHandler', $db);

  echo 'response: ' . $response;

  assert($response->status == 200);
  assert($response->headers['content-type'] == 'application/json');
  assert($response->body == '"some stuff"');
}





// -- test

function test_model ($db) {
  $alice = new User(1, 'alice');
  $alice->insert($db);

  $bob =  new User(2, 'bob');
  $bob->insert($db);

  $alice1 = User::selectById($db, 1);


  echo "alice == alice1 before... equals: " . assert($alice == $alice1) . " same: " . assert($alice->same($alice1)) . "\n";
  $alice1->username = "alicex";
  echo "alice == alice1 after... equals: " . assert($alice != $alice1) . " same: " . assert($alice->same($alice1)) . "\n";

  $alice1->update($db);
  $alice2 = User::selectById($db, 1);

  $all1 = User::all($db);

  echo "All Users 1: " . json_encode($all1) . "\n";

  assert(count($all1) == 2);
  assert(in_array($bob, $all1));
  assert(in_array($alice2, $all1));


  $bob1 = User::selectById($db, 2);
  $bob1->delete($db);
  $bob2 = User::selectById($db, 2);

  echo "Alice: " . json_encode($alice2) . "\n";
  echo "Bob: " . json_encode($bob2) . "\n";
  assert($alice2->username == "alicex");
  assert($bob2 == null);

  $all2 = User::all($db);

  echo "All Users 2: " . json_encode($all2) . "\n";
  assert(count($all2) == 1);
  assert($all2 == array($alice2));
}





// -- REST handler




function test_collection ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $alice = new User(1, 'Alice');
  $bob = new User(2, 'Bob');

  $alice->insert($db);
  $bob->insert($db);

  $response = restHandler($db, 'GET', '/users');

  echo "right status? " . assert($response->status == 200) . "\n";

  echo "2 results? " . assert(count($response->body) == 2) . "\n";
  
  echo "has alice? " . assert(in_array($alice, $response->body)) . "\n";
  echo "has bob? " . assert(in_array($bob, $response->body)) . "\n";
}

function test_create ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $userJson = array('id' => 1, 'username' => 'Alice');
  $response = restHandler($db, "PUT", "/users/1", JSON_HEADERS, $userJson);

  $alice = User::selectById($db, 1);

  echo $response;

  echo "right status? " . assert($response->status == 201) . "\n";


  echo "alice username = Alice?: " . assert($alice->username == "Alice") . "\n";
}


function test_create_invalid ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $userJson = array('id' => 1, 'FAKE' => 'Alice');
  $response = restHandler($db, "PUT", "/users/1", JSON_HEADERS, $userJson);

  $alice = User::selectById($db, 1);

  echo "right status? " . assert($response->status == 401) . "\n";


  echo "alice null? " . assert($alice == null) . "\n";
}

function test_create_exists ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $alice = new User(1, 'Alice');
  $alice->insert($db);


  $userJson = array('id' => 1, 'username' => 'Alice X');
  $response = restHandler($db, "PUT", "/users/1", JSON_HEADERS, $userJson);

  $alice = User::selectById($db, 1);

  echo "right status? " . assert($response->status == 202) . "\n";


  echo "alice username = Alice?: " . assert($alice->username == "Alice") . "\n";
}


  
function test_read ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $alice = new User(1, 'Alice');
  $alice->insert($db);

  $response = restHandler($db, 'GET', '/users/1');

  echo "right status? " . assert($response->status == 200) . "\n";


  
  echo "has alice? " . assert($response && $response->body->id == 1) . "\n";
}

function test_read_none ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $response = restHandler($db, 'GET', '/users/1');

  echo "right status? " . assert($response->status == 404) . "\n";
}



function test_update ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $alice = new User(1, 'Alice');

  $alice->insert($db);

  $userJson = array('username' => 'Alice X');
  $response = restHandler($db, "POST", "/users/1", JSON_HEADERS, $userJson);

  $alice = User::selectById($db, 1);

  echo "right status? " . assert($response->status == 200) . "\n";



  echo "alice username = Alice X?: " . assert($alice->username == "Alice X") . "\n";
}

function test_update_none ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $userJson = array('username' => 'Alice X');
  $response = restHandler($db, "POST", "/users/1", JSON_HEADERS, $userJson);

  $alice = User::selectById($db, 1);
  echo "no alice? " . assert($alice == null) . "\n";

  echo "right status? " . assert($response->status == 404) . "\n";
}

function test_update_invalid ($db) {
 echo "-- " . __FUNCTION__ . "\n";

  $alice = new User(1, 'Alice');

  $alice->insert($db);

  $userJson = array('FAKE' => 'Alice X');
  $response = restHandler($db, "POST", "/users/1", JSON_HEADERS, $userJson);

  $alice = User::selectById($db, 1);

  echo "right status? " . assert($response->status == 401) . "\n";

  echo "alice username = Alice?: " . assert($alice->username == "Alice") . "\n";
}





function test_delete ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $response = restHandler($db, "DELETE", "/users/1");

  $alice = new User(1, 'Alice');

  $alice->insert($db);

  $userJson = array('username' => 'Alice X');
  $response = restHandler($db, "DELETE", "/users/1");

  $alice = User::selectById($db, 1);

  echo "right status? " . assert($response->status == 200) . "\n";


  echo "alice deleted?: " . assert($alice == null) . "\n";
}

function test_delete_none ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $userJson = array('username' => 'Alice X');
  $response = restHandler($db, "DELETE", "/users/1", array(), $userJson);

  echo "right status? " . assert($response->status == 404) . "\n";
}





