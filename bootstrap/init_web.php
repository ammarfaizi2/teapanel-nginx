<?php

if (! defined("INIT_WEB")) {
	require __DIR__."/init.php";

	session_start();
	define("INIT_WEB", 1);
}
