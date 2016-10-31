<?php

require('core.php');

test();




function test () {
  $db = setup(new PDO('sqlite::memory:'));
  test_model($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_collection($db);
  
  $db = setup(new PDO('sqlite::memory:'));
  test_create($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_create_exists($db);


  $db = setup(new PDO('sqlite::memory:'));
  test_read($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_read_none($db);
  
  $db = setup(new PDO('sqlite::memory:'));
  test_update($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_update_none($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_delete($db);
}



// -- Domain Model



// -- test

function test_model ($db) {
  $alice = new User(1, 'alice');
  $alice->insert($db);

  $bob =  new User(2, 'bob');
  $bob->insert($db);

  $alice1 = User::selectById($db, 1);


  echo "alice == alice1 before... equals: " . ($alice == $alice1) . " same: " . $alice->same($alice1) . "\n";
  $alice1->username = "alicex";
  echo "alice == alice1 after... equals: " . ($alice == $alice1) . " same: " . $alice->same($alice1) . "\n";

  $alice1->update($db);
  $alice2 = User::selectById($db, 1);

  echo "All Users 1: " . json_encode(User::all($db)) . "\n"; // alicex, bob


  $bob1 = User::selectById($db, 2);
  $bob1->delete($db);
  $bob2 = User::selectById($db, 2);

  echo "Alice: " . json_encode($alice2) . "\n"; // username=alicex
  echo "Bob: " . json_encode($bob2) . "\n"; // null

  echo "All Users 2: " . json_encode(User::all($db)) . "\n"; // alicex
}



// -- REST handler


function test_collection ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $alice = new User(1, 'Alice');
  $bob = new User(2, 'Bob');

  $alice->insert($db);
  $bob->insert($db);

  $response = restHandler($db, 'GET', '/users');

  echo "right status? " . ($response->status == 200);

  echo "2 results? " . count($response->body) . "\n";
  
  echo "has alice? " . in_array($alice, $response->body) . "\n";
  echo "has bob? " . in_array($bob, $response->body) . "\n";
}

function test_create ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $userJson = array('id' => 1, 'username' => 'Alice');
  $response = restHandler($db, "PUT", "/users/1", array(), $userJson);

  $alice = User::selectById($db, 1);

  echo "right status? " . ($response->status == 201) . "\n";


  echo "alice username = Alice?: " . ($alice->username == "Alice") . "\n";
}

function test_create_exists ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $alice = new User(1, 'Alice');
  $alice->insert($db);


  $userJson = array('id' => 1, 'username' => 'Alice X');
  $response = restHandler($db, "PUT", "/users/1", array(), $userJson);

  $alice = User::selectById($db, 1);

  echo "right status? " . ($response->status == 202) . "\n";


  echo "alice username = Alice?: " . ($alice->username == "Alice") . "\n";
}


  
function test_read ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $alice = new User(1, 'Alice');
  $alice->insert($db);

  $response = restHandler($db, 'GET', '/users/1');

  echo "right status? " . ($response->status == 200) . "\n";


  
  echo "has alice? " . ($response && $response->body->id == 1) . "\n";
}

function test_read_none ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $response = restHandler($db, 'GET', '/users/1');

  echo "right status? " . ($response->status == 404) . "\n";
}



function test_update ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $alice = new User(1, 'Alice');

  $alice->insert($db);

  $userJson = array('username' => 'Alice X');
  $response = restHandler($db, "POST", "/users/1", array(), $userJson);

  $alice = User::selectById($db, 1);

  echo "right status? " . ($response->status == 200) . "\n";



  echo "alice username = Alice X?: " . ($alice->username == "Alice X") . "\n";
}

function test_update_none ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $userJson = array('username' => 'Alice X');
  $response = restHandler($db, "POST", "/users/1", array(), $userJson);

  $alice = User::selectById($db, 1);
  echo "no alice? " . ($alice == null) . "\n";

  echo "right status? " . ($response->status == 404) . "\n";
}




function test_delete ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $response = restHandler($db, "DELETE", "/users/1");

  $alice = new User(1, 'Alice');

  $alice->insert($db);

  $userJson = array('username' => 'Alice X');
  $response = restHandler($db, "DELETE", "/users/1");

  $alice = User::selectById($db, 1);

  echo "right status? " . ($response->status == 200) . "\n";


  echo "alice deleted?: " . ($alice == null) . "\n";
}

function test_delete_none ($db) {
  echo "-- " . __FUNCTION__ . "\n";

  $userJson = array('username' => 'Alice X');
  $response = restHandler($db, "DELETE", "/users/1", array(), $userJson);

  echo "right status? " . ($response->status == 404) . "\n";
}





