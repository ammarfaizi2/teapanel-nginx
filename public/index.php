<?php

require __DIR__."/../bootstrap/init_web.php";

if (isset($_SESSION["login"])) {
	require __DIR__."/home.php";
} else {
	require __DIR__."/login.php";
}
