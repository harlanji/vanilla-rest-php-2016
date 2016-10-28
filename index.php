<?php

require('sql.php');

// --- core

class Response {
  var $status;
  var $message;

  function __construct($status, $message) {
    $this->status = $status;
    $this->message = $message;
  } 

  function __toString() {
    return 'response code: ' . $this->status;
  }
}


// map request to handler
function handleRequest ($handler, $request) {
  $method = $request["method"];
  $url = $request["url"];
  $postBody = $request["body"];

  $response = $handler($method, $url, $postBody);

  // wrap raw response with 200 ok
  if (!($response instanceof Response)) {
    $response = new Response(200, $response);
  }

  // send response
}


// -- app service

function updateUser($user, $postBody) {
  foreach (get_object_vars($user) as $field) {
    if (array_key_exists($field, $postBody)) {
      $user->$field = $postBody[$field];
    }
  }
  return $user;
}

// map http request to model
function restHandler ($method, $url, $postBody = null) {
  global $db;

  if ($method == "GET" && $url == "/users") {
    return User::all($db);
  } else if (preg_match('/^\/users\/([\d]+)$/', $url, $matches, PREG_OFFSET_CAPTURE)) {
    $id = intval($matches[1][0]);
    $user = User::findById($db, $id);

    if ($method == "PUT") {
      if ($user) { return new Response(202, $user); }

      $user = updateUser(new User($id), $postBody);
      if ($user->create($db)) {
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

      if ($user->update($user)) {
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


function test_collection () {
  $response = restHandler("GET", "/users");

  echo $response;

}

function test_create () {
  $userJson = '{"name": "Alice"}';
  $response = restHandler("PUT", "/users/1", $userJson);

  echo $response;
}


  
function test_read () {
  $response = restHandler("GET", "/users/1");

  echo $response;
}


function test_update () {
  $userJson = '{"name": "Alice"}';
  $response = restHandler("POST", "/users/1", $userJson);

  echo $response;
}


function test_delete () {
  $response = restHandler("DELETE", "/users/1");

  echo $response;
}

function test () {
  test_model();

  test_collection();
  test_create();
  test_read();
  test_update();
  test_delete();
}

test_model();
