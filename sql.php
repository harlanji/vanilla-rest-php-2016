<?php

$db = new PDO('sqlite::memory:');

$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$db->setAttribute( PDO::ATTR_CASE, PDO::CASE_LOWER );
$db->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );

$db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY NOT NULL, username TEXT NOT NULL)") . "\n";

// -- model
// follows entity pattern

class User {
  var $id;
  var $username;

  function __construct ($id, $username) {
    $this->id = $id;
    $this->username = $username;
  }
  
  function insert ($db) {
    return $db->prepare("INSERT INTO users (id, username) VALUES(:id, :username)")
      ->execute(array(':id' => $this->id,
                      ':username' => $this->username));
  }

  function update ($db) {
    return $db->prepare("UPDATE users SET username = :username WHERE id = :id")
      ->execute(array(':id' => $this->id,
                      ':username' => $this->username));
  }

  function delete ($db) {
    return $db->prepare("DELETE FROM users WHERE id = ?")
      ->execute(array($this->id));
  }

  static function selectById ($db, $id) {
    $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute(array($id));

    // not using FETCH_CLASS "User" because ctor gets called after PDO-assignment, clearing DB values
    $row = $stmt->fetch();

    if(!$row) { return null; }
    
    return new User($row['id'], $row['username']);
  }

  static function all ($db) {
    $stmt = $db->prepare("SELECT id, username FROM users");
    $stmt->execute();

    $rows = $stmt->fetchAll();

    if(!$rows) { return null; }

    $users = array_map(function($row) {
      return User::fromRow($row); // can this be used directly in array_map?
    }, $rows);

    return $users;
  }

  static function fromRow ($row) {
    return new User($row['id'], $row['username']);
  }

  function same ($other) {
    return $this->id == $other->id;
  }

  // object equality check works in our favor for pojos http://stackoverflow.com/questions/17008622/is-there-a-equals-method-in-php-like-there-is-in-java
}

// -- test

function test_model () {
  global $db;
  
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
