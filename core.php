<?php

require_once('sql.php');
require_once('http.php');

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
    return new Response(200, User::all($db));
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
      return new Response(200, $user);
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
