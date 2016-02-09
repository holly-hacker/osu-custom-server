<?php
/*
require_once '/home/osuserve/public_html/web/inc/functions_db.php';
*/
function sqlconn() {
	require '/home/osuserve/public_html/web/inc/sqlconn.php';
	return $db;
}

function isUserBanned($playerID) {
	$db = sqlconn();
	$ret = true;

	$query = 'SELECT banned FROM users_data WHERE playerID = :player LIMIT 1';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':player', $playerID, PDO::PARAM_INT);
	$prepared->execute();
	$result = $prepared->fetch();
	$result = $result[0];

	if ($result == 0) {
		$ret = false;
	}
	return $ret;
}

function getAvatarID($playerID) {
	$db = sqlconn();
	$query = 'SELECT avatarID FROM users_data WHERE playerID = :user LIMIT 1';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':user', $playerID, PDO::PARAM_INT);
	$prepared->execute();
	$result = $prepared->fetch();
	$result = $result[0];

	//cookiezi: 124493
	//me: 2287881
	//lewa: 475021
	//WWW: 39828
	//peppy: 2
	//bancho: 3

	if ($result == '' || $result == '0') {
		$result = 475021;
	}

	return $result;
}

function getPlayerIDFromGlobal($username) {
	$db = sqlconn();
	$query = 'SELECT playerID FROM users_data WHERE displayName = :username LIMIT 1';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':username', $username, PDO::PARAM_STR);
	$succes = $prepared->execute();
	if ($succes) {
		$result = $prepared->fetchColumn();
		return $result;
	} else {
		return 'Query failed';
	}
}

function getPlayerIDFromOsu($username) {
	$db = sqlconn();
	$query = 'SELECT playerID FROM users_accounts WHERE osuname = :username LIMIT 1';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':username', $username, PDO::PARAM_STR);
	$succes = $prepared->execute();
	if ($succes) {
		$result = $prepared->fetchColumn();
		return $result;
	} else {
		return 'Query failed';
	}
}

function getUserName($playerID) {
	$db = sqlconn();
	$query = 'SELECT displayName FROM users_data WHERE playerID = :user LIMIT 1';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':user', $playerID, PDO::PARAM_INT);
	$prepared->execute();
	$result = $prepared->fetch();
	$result = $result[0];

	if ($result == '') {
		$result = $playerID;
	}

	return $result;
}

function getTotalScore($playerID) {
	$db = sqlconn();
	$query = 'SELECT SUM(score) FROM scores WHERE playerID = :user';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':user', $playerID, PDO::PARAM_INT);
	$prepared->execute();

	$totalScore = $prepared->fetch();
	return $totalScore[0];
}

function getTotalScoreForMode($playerID, $mode) {
	$db = sqlconn();
	$query = 'SELECT SUM(score) FROM scores WHERE playerID = :user AND mode = :mode';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':user', $playerID, PDO::PARAM_INT);
	$prepared->bindParam(':mode', $mode, PDO::PARAM_INT);
	$prepared->execute();

	$totalScore = $prepared->fetch();
	return $totalScore[0];
}

function getPlays($playerID) {
	$db = sqlconn();
	$query = 'SELECT ID FROM scores WHERE playerID = :playerID';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':playerID', $playerID, PDO::PARAM_INT);
	$prepared->execute();
	$rows = $prepared->fetchAll();
	$rowCount = count($rows);
	return $rowCount;
}

function getPlaysForMode($playerID, $mode) {
	$db = sqlconn();
	$query = 'SELECT ID FROM scores WHERE playerID = :playerID AND mode = :mode';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':playerID', $playerID, PDO::PARAM_INT);
	$prepared->bindParam(':mode', $mode, PDO::PARAM_INT);
	$prepared->execute();
	$rows = $prepared->fetchAll();
	$rowCount = count($rows);
	return $rowCount;
}

function getOsuAccounts($playerID) {
	$db = sqlconn();
	$query = 'SELECT osuname FROM users_accounts WHERE playerID = :playerID';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':playerID', $playerID, PDO::PARAM_INT);
	$prepared->execute();
	$rows = $prepared->fetchAll();
	return $rows;
}

function addOsuAccount($username, $password, $playerID) {
	$db = sqlconn();

	//if user already exist
	if (getPlayerIDFromOsu($username) != '') {
		echo "Already in db<br>";
		return false;
	}

	$query = 'INSERT INTO `users_accounts` (ID, osuname, playerID) 
			VALUES (NULL, :osuname, :playerID)';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':osuname', $username, PDO::PARAM_STR);
	$prepared->bindParam(':playerID', $playerID, PDO::PARAM_INT);
	$success = $prepared->execute();
	//echo 'Executed insert query: '.($prepared->execute() ? 'ok' : 'failed').'<br>';
	if (!success) {
		echo 'SQL error: ';
		print_r($prepared->errorInfo());
		echo '<br>';
	}

	return checkOsuLogin($username, $password);	//adding to database
}

function addGlobalAccount($username, $password) {
	$password = md5($password);
	$db = sqlconn();

	//if user already exists
	if (getPlayerIDFromGlobal($username) != '') {
		echo "Already in db<br>";
		return false;
	}

	$query = 'INSERT INTO users_data (displayName) 
			VALUES (:username)';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':username', $username, PDO::PARAM_STR);
	echo ($prepared->execute() ? "Added succesfully<br>" : "Error when adding<br>");


	$salt = substr(md5(rand()), 0, 5);
	$prepared = $db->prepare('UPDATE users_data SET passwordHash = :passhash, salt = :salt WHERE displayName = :username');
	$prepared->bindParam(':username', $username, PDO::PARAM_STR);
	$prepared->bindParam(':passhash', hashPassword($password, $salt), PDO::PARAM_STR);
	$prepared->bindParam(':salt', $salt, PDO::PARAM_STR);	//random string
	return $prepared->execute();

	//return checkGlobalLogin($username, $password);
}

function checkOsuLogin($user, $pass) {
	//make pass more secure
	$db = sqlconn();
	$query = 'SELECT passwordHash, salt, ID FROM users_accounts WHERE osuname = :user LIMIT 1';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':user', $user, PDO::PARAM_STR);
	$prepared->execute();
	$result = $prepared->fetchAll();
	$count = count($result);

	if ($count != 0) {
		$row = $result[0];
		$passHash = $row['passwordHash'];
		$salt = $row['salt'];

		if ($passHash == '') {
			//password not in db yet
			$db = sqlconn();

			$salt = substr(md5(rand()), 0, 5);

			$prepared = $db->prepare('UPDATE users_accounts SET passwordHash = :passhash, salt = :salt WHERE osuname = :user');
			$prepared->bindParam(':user', $user, PDO::PARAM_STR);
			$prepared->bindParam(':passhash', hashPassword($pass, $salt), PDO::PARAM_STR);
			$prepared->bindParam(':salt', $salt, PDO::PARAM_STR);	//random string
			$prepared->execute();

			return ($prepared->rowCount() == 1);
		}

		if (hashPassword($pass, $salt) == $passHash)
			return true;

	} else {
		//echo 'User not in database.</br>';
		//user not in database
	}
	return false;
}

function checkGlobalLogin($playerID, $pass) {
	$pass = md5($pass);
	$db = sqlconn();
	$query = 'SELECT passwordHash, salt FROM users_data WHERE playerID = :ID LIMIT 1';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':ID', $playerID, PDO::PARAM_INT);
	$prepared->execute();
	$result = $prepared->fetchAll();
	$count = count($result);

	if ($count != 0) {
		$row = $result[0];
		$passHash = $row['passwordHash'];
		$salt = $row['salt'];

		if ($passHash == '') {
			$salt = substr(md5(rand()), 0, 5);

			$prepared = $db->prepare('UPDATE users_data SET passwordHash = :passhash, salt = :salt WHERE playerID = :ID');
			$prepared->bindParam(':ID', $playerID, PDO::PARAM_INT);
			$prepared->bindParam(':passhash', hashPassword($pass, $salt), PDO::PARAM_STR);
			$prepared->bindParam(':salt', $salt, PDO::PARAM_STR);	//random string
			$prepared->execute();

			return ($prepared->rowCount() == 1);
		}

		if (hashPassword($pass, $salt) == $passHash)
			return true;

	} else {/*not in db*/}
	return false;
}

function hashPassword($pass, $salt) {
	return md5(md5($pass).$salt);
}

function generateRandomString($length = 10) {	//the magic of the internet :)
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function getMostPlayedMaps($playerID)
{
	$query = '	SELECT       `value`,
			             COUNT(`value`) AS `value_occurrence` 
			    FROM     `my_table`
			    WHERE `playerID` = :playerID
			    GROUP BY `value`
			    ORDER BY `value_occurrence` DESC
			    LIMIT    10;';
}

function getLevel($n)
{
	for ($i=0; $i < 100; $i++) { 
		$scoreToBeat = (5000 / 3) * (4*pow($i, 3) - 3*pow($i, 2) - $i) + 1.25 * pow(1.8, ($i - 60));
		if ($n < $scoreToBeat) {
			return $i;
		}
	}
	return "level too high :c";
}
?>
