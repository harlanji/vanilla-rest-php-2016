<?php

require('index.php');

test();

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



