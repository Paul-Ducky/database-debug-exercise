<?php
declare(strict_types=1);

$sports = ['Football', 'Tennis', 'Ping pong', 'Volley ball', 'Rugby', 'Horse riding', 'Swimming', 'Judo', 'Karate'];

function openConnection(): PDO
{
    // No bugs in this function, just use the right credentials.
    $dbhost = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $db = "debug-ex";

    $driverOptions = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO('mysql:host=' . $dbhost . ';dbname=' . $db, $dbuser, $dbpass, $driverOptions);
}

$pdo = openConnection();

if(!empty($_POST['firstname']) && !empty($_POST['lastname']) && !empty($_POST['sports'])) {
    //@todo possible bug below? fixed (3)
    if(empty($_POST['id'])) {
        $handle = $pdo->prepare('INSERT INTO user (firstname, lastname, year) VALUES (:firstname, :lastname, :year)');
        $message = 'Your record has been added';
    } else {
        //@todo why does this not work? -> doesn't handle multiple sports --> does now
        $handle = $pdo->prepare('UPDATE user set firstname = :firstname, lastname = :lastname, year = :year WHERE user.id = :id');
        $handle->bindValue(':id', $_POST['id']);
        $message = 'Your record has been updated';
    }

    $handle->bindValue(':firstname', $_POST['firstname']);
    $handle->bindValue(':lastname', $_POST['lastname']);
    $handle->bindValue(':year', date('Y'));
    $handle->execute();

    if(!empty($_POST['id'])) {
        $handle = $pdo->prepare('DELETE FROM sport WHERE user_id = :id');
        $handle->bindValue(':id', $_POST['id']);
        $handle->execute();
        $userId = $_POST['id'];
    } else {
        $userId = $pdo->lastInsertId();
    }

    //@todo Why does this loop not work? If only I could see the bigger picture. --> loop fixed (2/3)
    foreach($_POST['sports'] AS $sport) {

        $handle = $pdo->prepare('INSERT INTO sport (user_id, sport) VALUES (:userId, :sport)');
        $handle->bindValue(':userId', $userId);
        $handle->bindValue(':sport', $sport);
        $handle->execute();
    }
}
elseif(isset($_POST['delete'])) {
    //@todo BUG? Why does always delete all my users? --> fixed (2)
    $handle = $pdo->prepare('DELETE FROM user WHERE user.id = :id');
    //The line below just gave me an error, probably not important. Annoying line.
    $handle->bindValue(':id', $_POST['id']);
    $handle->execute();

    $message = 'Your record has been deleted';
}

//@todo Invalid query? --> problem with multiple sports per user --> fully fixed (2)
$handle = $pdo->prepare('SELECT user.id, concat_ws(" ",firstname, lastname) AS name, GROUP_CONCAT(distinct sport separator ", ") as sport FROM user LEFT JOIN sport ON user.id = sport.user_id WHERE year = :year GROUP BY user.id');
$handle->bindValue(':year', date('Y'));
$handle->execute();
$users = $handle->fetchAll();

$saveLabel = 'Save record';
if(!empty($_GET['id']) && empty($_POST)) { // fixed the getting stuck.
    $saveLabel = 'Update record';

    $handle = $pdo->prepare('SELECT user.id, firstname, lastname FROM user where user.id = :id');
    $handle->bindValue(':id', $_GET['id']);
    $handle->execute();
    $selectedUser = $handle->fetch();

    //This segment checks all the current sports for an existing user when you update him. Currently that is not working however. :-( --> now it works
    $selectedUser['sports'] = [];
    $handle = $pdo->prepare('SELECT sport FROM sport where user_id = :id');
    $handle->bindValue(':id', $_GET['id']);
    $handle->execute();
    foreach($handle->fetchAll() AS $sport) {
        $selectedUser['sports'][] = implode("",$sport);//@todo I just want an array of all sports of this, why is it not working? --> fixed. didnt work with array in array
        }
}

if(empty($selectedUser['id'])) {
    $selectedUser = [
        'id' => '',
        'firstname' => '',
        'lastname' => '',
        'sports' => []
    ];
}

require 'resources/view.php';
// All bugs where written with Love for the learning Process. No actual bugs where harmed or eaten during the creation of this code.