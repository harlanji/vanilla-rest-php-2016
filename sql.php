<?php

function setup ($db) {

  $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
  $db->setAttribute( PDO::ATTR_CASE, PDO::CASE_LOWER );
  $db->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );

  $db->exec("CREATE TABLE users (id TEXT PRIMARY KEY NOT NULL, username TEXT NOT NULL)") . "\n";


  return $db;
}

// -- model
// follows entity pattern

class User {
  var $id;
  var $username;

  function __construct ($id, $username) {
    $this->id = $id;
    $this->username = $username;
  }


  function validate () {
    return $this->id
      && strlen($this->username) > 0;
  }

  static function nextId($db) {
    return uniqid("user-");
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
    
    return User::fromRow($row);
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
