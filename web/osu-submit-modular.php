<?php

$disabled = false;

/*
-Explanation-
pl: process list, only if passed, encoded
x: 0 if failed, 1 if retried
ft: time in play before fail/retry
score: all score stuf you need, encoded
fs: ":False:False:False:True									", no idea what it means, encoded
c1: Disk signature+disk serial hash, uninstallID
pass: password, plain MD5 hash (wait what)
s: hardware data, encoded
i: no idea, only if passed
iv: IV for AES encoding
score (replay): replay file

-Formats-
pl:
 hash + " " + path + " | " + processName + " (" + windowTitle + ")"
score:
 beatmapHash:username:verificationHash:count300:count100:count50:countGeki:countKatu:countMiss:score:combo:FC:rank:mods:pass:playmode:date:version
c1:
 MD5(DiskSig + DiskSerial)|uninstallID
s:
 someHash:MAC addresses:someHash:someHash:someHash:randomShit

possible errors: (format: "error: {error}")
nouser 		(user not found in db)
pass 		(wrong pass)
inactive	(ban)
ban 		(ban)
beatmap 	(beatmap not ranked)
disabled 	(specific mods/mode)
oldver 		(old osu! version)
*/

include '/home/osuserve/public_html/web/inc/functions_db.php';
include '/home/osuserve/public_html/web/inc/aes.php';

if ($disabled)
	exit(displayError('disabled'));

$replayID = 0;
$replayLocation = '/home/osuserve/public_html/web/replay/';
$IV = $_POST['iv'];
$key = 'h89f2-890h2h89b34g-h80g134n90133';
$scoreEncrypted = $_POST["score"];
$scoreDecrypted = decryptText($scoreEncrypted);
$score = explode(':', $scoreDecrypted);		//BOOM

//get playerID
$playerID = getPlayerIDFromOsu($score[1]);


if ($playerID == '')
	displayError('nouser');
if (!checkOsuLogin($score[1], $_POST["pass"]))
	displayError('pass');
if (isUserBanned($playerID))
	displayError('ban');

//set failed or not
//pass: 2
//quit: 1
//fail: 0

$completed = 2;
if (isset($_POST['x']))
{
	//user didnt finish map
	$completed = $_POST['x'];
}

addToDatabase($score, $playerID, $completed);

//store replay file
storeReplay();

echo 'ok';

function decryptText($input)
{
	global $IV, $key;

	$aes = new AES($input, $key, 256);
	$aes->setIV(base64_decode($IV));
	$aes->setMode(AES::M_CBC);
	return $aes->decrypt();
}

function displayError($message)
{
	die('error: '.$message);
}

function addToDatabase($score, $playerID, $completed)
{
	global $replayID;
	$db = sqlconn();
	$query = 'INSERT INTO scores (ID, beatmapHash, playerID, score, combo, fc, mods, count300, count100, count50, countGeki, countKatu, countMiss, time, mode, completed)
				VALUES (NULL, :beatmap, :user, :score, :combo, :fc, :mods, :c300, :c100, :c50, :geki, :katu, :miss, :time, :mode, :completed)';
	$prepared = $db->prepare($query);

	$prepared->bindParam(':beatmap', $score[0], PDO::PARAM_STR);
	$prepared->bindParam(':user', $playerID, PDO::PARAM_INT);
	$prepared->bindParam(':score', $score[9], PDO::PARAM_INT);
	$prepared->bindParam(':combo', $score[10], PDO::PARAM_INT);
	$prepared->bindParam(':fc', $score[11], PDO::PARAM_INT);
	$prepared->bindParam(':mods', $score[13], PDO::PARAM_INT);
	$prepared->bindParam(':c300', $score[3], PDO::PARAM_INT);
	$prepared->bindParam(':c100', $score[4], PDO::PARAM_INT);
	$prepared->bindParam(':c50', $score[5], PDO::PARAM_INT);
	$prepared->bindParam(':geki', $score[6], PDO::PARAM_INT);
	$prepared->bindParam(':katu', $score[7], PDO::PARAM_INT);
	$prepared->bindParam(':miss', $score[8], PDO::PARAM_INT);
	$prepared->bindParam(':time', $score[16], PDO::PARAM_STR);
	$prepared->bindParam(':mode', $score[15], PDO::PARAM_INT);
	$prepared->bindParam(':completed', $completed, PDO::PARAM_INT);

	$result = $prepared->execute();
	$replayID = $db->lastInsertId();
	if (!$result) {
		displayError('beatmap');
	}
}

function storeReplay()
{
	if ($_FILES['score']['size'] == 0) {
		unlink($_FILES['score']['tmp_name']);
		return false;
	}
	global $replayLocation;
	global $replayID;
	//$uploadfile = $replayLocation . basename($_FILES['score']['name']);
	$uploadfile = $replayLocation.$replayID;
	if (move_uploaded_file($_FILES['score']['tmp_name'], $uploadfile)) {
		return true;
	} else { return false; }
}
?>
