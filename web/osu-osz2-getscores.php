<?php 

include '/home/osuserve/public_html/web/inc/functions_db.php';

//$s = $_GET["s"];			//0 or 1
//$vv = $_GET["vv"];			//always 2
//$v = $_GET["v"];			//rankingType_0
$c = $_GET['c'];			//beatmap hash
//$beatmapFile = $_GET["f"];	//beatmap filename
$mode = $_GET['m'];			//playmode
//$i = $_GET["i"];			//some int
//$mods = $_GET["mods"];		//for mod-specific rankings
//$h = $_GET["h"];			//no idea
//$a = $_GET["a"];			//anticheat :p
$username = $_GET['us'];	//username of player that requested this leaderboard
$passwordHash = $_GET['ha'];	//passwordHash of player that requested this leaderboard

//check if user is valid
$playerID = getPlayerIDFromOsu($username);

echo '3|false|0|0|0'."\r\n";
//status
//status|***
//status|***|beatmapId|mapsetId|maxScores
//statuses: notsubmitted(-1), pending(0), unknown(1), ranked(2), approved(3)
//note that beatmapId and mapsetId change thumbnail

echo "\r\n";
//online offset, empty should be fine

echo "\r\n";
/*
song name + artist, format [data]item|[data]item
size: (float)
bold: (bool)
colour: (byte.byte.byte)
wait: (int)		//wait ms before showing
time: (int)		//no idea
hold: (int)		//wait this made it way more confusing now

some fancy colors:
255.255.0	Gold
255.0.255	pink-ish color
0.255.255	Cyan
*/

echo '9.28235'."\r\n";	//no idea, unused

if ($playerID == '')
	exit();

if (!checkOsuLogin($username, $passwordHash)) {
	echo "\r\n";
	echo  scoreString(0, 'You', 1, 0,
						0, 10, 50, 1, 0, 0,
						0, 0, 0, 1, 0);
	echo  scoreString(0, 'are', 0, 0,
						0, 0, 50, 1, 10, 0,
						0, 0, 0, 2, 0);
	echo  scoreString(0, 'invalid!', -1, 0,
						2, 10, 0, 1, 0, 0,
						0, 0, 0, 3, 0);
	exit();
}

if (isUserBanned($playerID)) {
	echo "\r\n";
	echo  scoreString(0, 'You', 1, 0,
						0, 10, 50, 1, 0, 0,
						0, 0, 0, 1, 0);
	echo  scoreString(0, 'are', 0, 0,
						0, 0, 50, 1, 10, 0,
						0, 0, 0, 2, 0);
	echo  scoreString(0, 'banned!', -1, 0,
						2, 10, 0, 1, 0, 0,
						0, 0, 0, 3, 0);
	exit();
}

getScores($c, $mode, $playerID);	//personal score
getScores($c, $mode, NULL);

function scoreString($replayId, $name, $score, $combo, 
					$count50, $count100, $count300, $countMiss, $countKatu, $countGeki, 
					$FC, $mods, $avatarID, $rank, $timestamp) {
	if ($timestamp != '0') {
		$actualDate = getActualDate($timestamp);
	} else {
		$actualDate = '644112000';
	}

	
	return "$replayId|$name|$score|$combo|$count50|$count100|$count300|$countMiss|$countKatu|$countGeki|$FC|$mods|$avatarID|$rank|$actualDate\r\n";
}

function getActualDate($input)
{
	$input -= (60*60*4);	//4h
	$year = substr($input, 0, 2);
	$month = substr($input, 2, 2);
	$day = substr($input, 4, 2);
	$hour = substr($input, 6, 2);
	$minute = substr($input, 8, 2);
	$second = substr($input, 10, 2);

	return strtotime("$month/$day/20".$year." $hour:$minute");	//dont ask
	//echo strtotime("02/23/2013 12:00")
	//2008/06/30
}

function getTries($playerID, $beatmapHash, $mode)
{
	//get array containing all plays from this user
	$db = sqlconn();
	$query = 'SELECT ID FROM scores WHERE beatmapHash = :beatmap AND playerID = :playerID AND mode = :mode ORDER BY score DESC';
	$prepared = $db->prepare($query);
	$prepared->bindParam(':beatmap', $beatmapHash, PDO::PARAM_STR);
	$prepared->bindParam(':playerID', $playerID, PDO::PARAM_INT);
	$prepared->bindParam(':mode', $mode, PDO::PARAM_INT);
	$prepared->execute();

	$count = $prepared->rowCount();
	return $count;
}

function getScores($beatmapHash, $mode, $playerID)
{
	$db = sqlconn();

	$personalScore = ($playerID == NULL);

	if ($personalScore) {
		//global scores
		$query = 'SELECT * FROM scores WHERE beatmapHash = :beatmap AND mode = :mode ORDER BY score DESC';
		$prepared = $db->prepare($query);
		$prepared->bindParam(':beatmap', $beatmapHash, PDO::PARAM_STR);
		$prepared->bindParam(':mode', $mode, PDO::PARAM_INT);
	} else  {
		//personal scores
		$query = 'SELECT * FROM scores WHERE beatmapHash = :beatmap AND playerID = :user AND mode = :mode ORDER BY score DESC LIMIT 1';
		$prepared = $db->prepare($query);
		$prepared->bindParam(':beatmap', $beatmapHash, PDO::PARAM_STR);
		$prepared->bindParam(':mode', $mode, PDO::PARAM_INT);
		$prepared->bindParam(':user', $playerID, PDO::PARAM_INT);
	}

	$prepared->execute();

	$i = 0;
	while ($row = $prepared->fetch()) {
		$i++;
		$playerID = $row['playerID'];
		$score = $row['score'];
		$combo = $row['combo'];
		$fc = $row['fc'];
		$mods = $row['mods'];
		$ID = $row['ID'];
		$count50 = $row['count50'];
		$count100 = $row['count100'];
		$count300 = $row['count300'];
		$countKatu = $row['countKatu'];
		$countGeki = $row['countGeki'];
		$countMiss = $row['countMiss'];
		$time = $row['time'];
		$completed = $row['completed'];

		$showScore = !isUserBanned($playerID);

		//customisation
		$avatarID = getAvatarID($playerID);
		$newUser = getUserName($playerID);

		//display play ID
		if (false) {
			$newUser = "$newUser (play $ID)";
		}

		//display tries
		if (true) {
			$tries = getTries($playerID, $beatmapHash, $mode);
			$newUser = "$newUser ($tries ".(($tries == 1) ? 'try' : 'tries').')';
		}

		if ($completed == 1) {
			//display quitted
			if (true) {
				$newUser = '[Q] '.$newUser;
			} else {
				if ($personalScore) {echo "\r\n";}
				continue;
			}
		}

		if ($completed == 0) {
			//display failed
			if (true) {
				$newUser = '[F] '.$newUser;
			} else {
				if ($personalScore) {echo "\r\n";}
				continue;
			}
		}

		if ($showScore) {
			echo scoreString($ID, $newUser, $score, $combo, $count50, $count100, $count300, $countMiss, $countKatu, $countGeki, $fc, $mods, $avatarID, $i, $time);
		}
		
	}

	if ($i == 0) {
		//no scores recieved
		echo "\r\n";
	}
}

?>
