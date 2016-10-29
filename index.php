<?php

require('sql.php');
require('http.php');

// ---

function updateUser($user, $postBody) {
  foreach (get_object_vars($user) as $field => $v) {
    if (array_key_exists($field, $postBody)) {
      $user->$field = $postBody[$field];
    }
  }
  return $user;
}


// map http request to model
function restHandler ($db, $method, $url, $headers = array(), $postBody = null) {

  if ($method == "GET" && $url == "/users") {
    return User::all($db);
  } else if (preg_match('/^\/users\/([\d]+)$/', $url, $matches, PREG_OFFSET_CAPTURE)) {
    $id = intval($matches[1][0]);
    $user = User::selectById($db, $id);

    if ($method == "PUT") {
      if ($user) { return new Response(202, $user); }

      $user = updateUser(new User(1, ''), $postBody);
      if ($user->insert($db)) {
        return new Response(201, $user); 
      } else {
        return new Response(400, 'could not create user');
      }
    }
    
    if (!$user) {
      return new Response(404, 'not found');
    }

    switch ($method) {
    case "GET":
      return $user;
    case "POST":
      $user = updateUser($user, $postBody);

      if ($user->update($db)) {
        return new Response(200, $user);
      } else {
        return new Response(400, 'could not update user');
      }
      return $user;
    case "DELETE":
      if($user->delete($db)) {
        return new Response(200, '');
      }
    }
  }

}


// --- tests


function test_collection ($db) {
  $alice = new User(1, 'Alice');
  $bob = new User(2, 'Bob');

  $alice->insert($db);
  $bob->insert($db);

  $response = restHandler($db, 'GET', '/users');

  echo "2 results? " . count($response) . "\n";
  
  echo "has alice? " . in_array($alice, $response) . "\n";
  echo "has bob? " . in_array($bob, $response) . "\n";
}

function test_create ($db) {
  $userJson = array('id' => 1, 'username' => 'Alice');
  $response = restHandler($db, "PUT", "/users/1", array(), $userJson);

  $alice = User::selectById($db, 1);

  echo "create: alice username = Alice X?: " . ($alice->username == "Alice") . "\n";
}


  
function test_read ($db) {
  $alice = new User(1, 'Alice');
  $alice->insert($db);

  $response = restHandler($db, 'GET', '/users/1');

  
  echo "read: has alice? " . ($response && $response->id == 1) . "\n";
}


function test_update ($db) {
  $alice = new User(1, 'Alice');

  $alice->insert($db);

  $userJson = array('username' => 'Alice X');
  $response = restHandler($db, "POST", "/users/1", array(), $userJson);

  $alice = User::selectById($db, 1);

  echo "alice username = Alice X?: " . ($alice->username == "Alice X") . "\n";
}


function test_delete ($db) {
  $response = restHandler($db, "DELETE", "/users/1");

  $alice = new User(1, 'Alice');

  $alice->insert($db);

  $userJson = array('username' => 'Alice X');
  $response = restHandler($db, "DELETE", "/users/1");

  $alice = User::selectById($db, 1);

  echo "alice deleted?: " . ($alice == null) . "\n";
}
