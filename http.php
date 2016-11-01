<?php

// see encodeBody and decodeBody
define("ACCEPT_TYPES", array('text/plain', 'application/json'));

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
    return 'response code: ' . $this->status . ', headers: ' . json_encode($this->headers) . "\n" . json_encode($this->body);
  }
}

function handleRequest_raw ($method, $url, $headers, $postBody, $handler, $db) {
  if (!$headers) {
    $headers = array();
  }

  if (!array_key_exists('accept', $headers)) {
    $headers['accept'] = 'text/plain';
  }

  if (!in_array($headers['accept'], ACCEPT_TYPES)) {
    return new Response(406, 'acceptable content type could not be given', array('content-type' => 'text/plain'));
  }

  $postBody = $postBody ? decodeBody($headers['content-type'], $postBody) : null;

  // -- handle
  $response = $handler($db, $method, $url, $headers, $postBody);

  // -- defaults
  if ($response == null) {
    $response = new Response(404, "not found");
  }  else if (!($response instanceof Response)) {
    $response = new Response(200, $response);
  }
  if (!array_key_exists('content-type', $response->headers)) {
    $response->headers['content-type'] = $headers['accept'];
  }

  if ($headers['accept'] != $response->headers['content-type']) {
    $response = new Response(406, 'acceptable content type could not be given', array('content-type' => 'text/plain'));
  } else {
    $response->body = encodeBody($response->headers['content-type'], $response->body);
  }
  return $response;
}


// map request to handler
function handleRequest ($handler, $db) {
  // -- read
  $method = $_SERVER['REQUEST_METHOD'];
  $url = $_SERVER['REQUEST_URI'];
  $headers = array_change_key_case(getallheaders(), CASE_LOWER);
  $postBody = ($method == "POST" || $method == "PUT") ? stream_get_contents(STDIN) : null;

  $response = handleRequest_raw($method, $url, $headers, $postBody, $handler, $db);

  writeResponse($response);
}


function writeResponse ($response) {
  http_status_code($status);

  foreach ($headers as $k => $v) {
    header($k . ": " . $v);
  }
  
  echo $body;
}


// ---

function encodeBody ($contentType, $bodyText) {
  if ($contentType == 'application/json') {
    return json_encode($bodyText);
  } else {
    return print_r($bodyText, true);
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
