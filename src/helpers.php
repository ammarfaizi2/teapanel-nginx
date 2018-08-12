<?php

if (! function_exists("icelog")) {
	/**
	 * @param string $format
	 * @param string $params
	 * @return string
	 */
	function icelog(string $format, string ...$params)
	{
		fprintf(STDOUT, sprintf(
			"[%s] %s\n",
			date("Y-m-d H:i:s"),
			sprintf($format, ...$params)
		));
	}
}

if (! function_exists("icexec")) {
	/**
	 * @param string $cmd
	 * @return string
	 */
	function icexec(string $cmd)
	{
		return shell_exec($cmd." 2>&1");
	}
}
