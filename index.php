<?php

require('sql.php');

test();

// --- core

class Response {
  var $status;
  var $body;
  var $headers;

  function __construct($status, $body, $headers = array()) {
    $this->status = $status;
    $this->body = $body;
    $this->headers = $headers;
  } 

  function __toString() {
    return 'response code: ' . $this->status;
  }
}

// map request to handler
function handleRequest ($handler, $db) {
  // -- read
  $method = $_SERVER['REQUEST_METHOD'];
  $url = $request['REQUEST_URI'];
  $headers = array_change_key_case(getallheaders(), CASE_LOWER);
  $postBody = stream_get_contents(STDIN);
  $postBody = decodeBody($headers['content-type'], $postBody);

  // -- handle
  $response = $handler($db, $method, $url, $headers, $postBody);

  // -- defaults
  if (!($response instanceof Response)) {
    $response = new Response(200, $response);
  }
  if (!array_key_exists('content-type', $response->headers)) {
    $response->headers['content-type'] = 'text/plain';
  }

  // -- write
  http_status_code($response->status);
  foreach ($response->headers as $k => $v) {
    header($k . ": " . $v);
  }
  echo encodeBody($response->headers['content-type'], $response->body);
}


// ---

function encodebody ($contentType, $bodyText) {
  if ($contentType == 'application/json') {
    return json_encode($bodyText);
  } else {
    return $bodyText;
  }
}

function decodeBody ($contentType, $bodyText) {
  if ($contentType == "application/json") {
    return json_decode($bodyText, true);
  } else if ($contentType == "application/x­www­form­urlencoded") {
    return $_POST;
  } else {
    return $bodyText;
  }
}

// ---

function updateUser($user, $postBody) {
  foreach (get_object_vars($user) as $field) {
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


function test_collection ($db) {
  $response = restHandler($db, "GET", "/users");

  echo encodeBody('application/json', $response);

}

function test_create ($db) {
  $userJson = '{"name": "Alice"}';
  $response = restHandler($db, "PUT", "/users/1", array('content-type' => 'text/json'), $userJson);

  echo $response;
}


  
function test_read ($db) {
  $response = restHandler($db, "GET", "/users/1");

  echo $response;
}


function test_update ($db) {
  $userJson = '{"name": "Alice"}';
  $response = restHandler($db, "POST", "/users/1", array('content-type' => 'text/json'), $userJson);

  echo $response;
}


function test_delete ($db) {
  $response = restHandler($db, "DELETE", "/users/1");

  echo $response;
}

function test () {
  $db = setup(new PDO('sqlite::memory:'));
  test_model($db);

  $db = setup(new PDO('sqlite::memory:'));
  test_collection($db);
  
  $db = setup(new PDO('sqlite::memory:'));
  test_create($db);
  
  $db = setup(new PDO('sqlite::memory:'));
  test_read($db);
  
  $db = setup(new PDO('sqlite::memory:'));
  test_update($db);
  
  $db = setup(new PDO('sqlite::memory:'));
  test_delete($db);
}



