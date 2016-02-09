<?php

$replayLocation = '/home/osuserve/public_html/web/replay/';

if (isset($_GET['c']) && isset($_GET['u']) && isset($_GET['h'])) {
	//TODO: login check
	echo file_get_contents($replayLocation.$_GET['c']);
}

?>
