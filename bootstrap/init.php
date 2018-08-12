<?php

if (! defined("INIT")) {
	define("INIT", 1);
	require __DIR__."/../config/main.php";

	if (! defined("BASEPATH")) {
		throw new Error("BASEPATH is not defined!");
	}

	/**
	 * @param string $class
	 * @return void
	 */
	function iceteaInternalAutoloader(string $class): void
	{
		$class = str_replace("\\", "/", $class);
		
		if (file_exists($f = BASEPATH."/src/classes/".$class.".php")) {
			require $f;
			return;
		}

		if (substr($class, 0, 3) === "Phx") {
			if (file_exists($f = BASEPATH."/src/classes/".substr($class, 4).".php")) {
				require $f;
				return;
			}
		}
	}

	spl_autoload_register("iceteaInternalAutoloader");
	require BASEPATH."/src/helpers.php";
}
