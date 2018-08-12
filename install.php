#!/usr/bin/env php
<?php

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @version 0.0.1
 * @link https://github.com/ammarfaizi2/teapanel-nginx
 */

require __DIR__."/bootstrap/init.php";

$paneluser				= "icetea";
$phpsystemd 			= "/lib/systemd/system/php7.2-fpm.service";
$panelconfig 			= "/opt/teapanel-nginx/config/nginx";
$panelconfig_symlink	= "/etc/nginx/sites-enabled/teapanel";
$phpfpm_defaultpoold	= "/etc/php/7.2/fpm/pool.d/www.conf";
$phpfpm_masterpoold		= "/etc/php/7.2/fpm/pool.d/master.conf";
$phpfpm_prodpoold		= "/etc/php/7.2/fpm/pool.d/prod.conf";

if (trim(icexec("whoami")) !== "root") {
	icelog("You need to run this script as root!");
	exit(1);
}

icexec("chown -R root:root /opt/teapanel-nginx");
icelog("Adding new user \"%s\"...", $paneluser);
icexec("useradd $paneluser --home /opt/teapanel-nginx/home/$paneluser");

if (preg_match("/success/", icexec("id $paneluser && echo success"))) {
	if (! preg_match("/success/", icexec("echo \"123qweasdzxc\\n123qweasdzxc\" > /tmp/mypasswd && passwd $paneluser < /tmp/mypasswd && rm -f /tmp/mypasswd && echo success"))) {
		icelog("An error occured when changing password for user \"%s\"", $paneluser);
		exit(1);
	}
} else {
	icelog("Could not create user \"%s\"", $paneluser);
	exit(1);
}

if (! is_dir("/opt/teapanel-nginx/home/$paneluser")) {
	icexec("rm -rf /opt/teapanel-nginx/home/$paneluser");
	if (! preg_match("/success/", icexec("mkdir -pv /opt/teapanel-nginx/home/$paneluser && chown -R $paneluser:$paneluser /opt/teapanel-nginx/home/$paneluser && echo success"))) {
		icelog("An error occured when preparing $paneluser's home");
		exit(1);
	}
}

foreach (
	[
		"Adding \"$paneluser\" to \"adm\" group" => "adduser $paneluser adm",
		"Adding \"$paneluser\" to \"cdrom\" group" => "adduser $paneluser cdrom",
		"Adding \"$paneluser\" to \"sudo\" group" => "adduser $paneluser sudo",
	] as $key => $value) {
	icelog($key."...");
	if (! preg_match("/success/", icexec($value." && echo success"))) {
		icelog("An error occured when %s", $key);
		exit(1);
	}
}

icelog("Linking %s to %s...", $panelconfig, $panelconfig_symlink);
if (@link($panelconfig, $panelconfig_symlink)) {
	icelog("%s has been linked to %s", $panelconfig, $panelconfig_symlink);
} else {
	$last = error_get_last();
	if (isset($last["message"]) && $last["message"] === "link(): File exists") {
		icelog("File %s is exists", $panelconfig_symlink);
		icelog("Deleting %s...", $panelconfig_symlink);
		if (@unlink($panelconfig_symlink)) {
			icelog("%s has been deleted", $panelconfig_symlink);
			icelog("Linking %s to %s...", $panelconfig, $panelconfig_symlink);
			if (@link($panelconfig, $panelconfig_symlink)) {
				icelog("%s has been linked to %s", $panelconfig, $panelconfig_symlink);
			} else {
				icelog("Could not link %s to %s", $panelconfig, $panelconfig_symlink);
				exit(1);	
			}
		} else {
			icelog("Could not delete %s", $panelconfig_symlink);
			exit(1);
		}
	} else {
		icelog("Could not link %s to %s", $panelconfig, $panelconfig_symlink);
		exit(1);
	}
}

icelog("Restarting nginx service...");
if (! preg_match("/success/", icexec("systemctl restart nginx && echo success"))) {
	icexec("An error occured when restarting nginx");
}

if (file_exists($phpsystemd)) {
	icelog("Reading PHP systemd file %s...", $phpsystemd);
	$s = file_get_contents($phpsystemd);
	if (preg_match("/(?:ExecStart=)(.+)(?:\n)/Usi", $s, $m)) {
		$s = explode(trim($m[0]), $s, 2);
		$s = implode("ExecStart={$m[1]} -R", $s);
		icelog("Rewriting PHP systemd file %s...", $phpsystemd);
		if (! file_put_contents($phpsystemd, $s)) {
			icelog("Could not rewrite PHP systemd file %s", $phpsystemd);
			exit(1);
		}
		icelog("Reloading daemon: systemctl daemon-reload...");
		if (! preg_match("/success/", icexec("systemctl daemon-reload && echo success"))) {
			icelog("An error occured when reloading daemon");
			exit(1);
		}
	} else {
		icelog("Could not find \"ExecStart\" variable in %s", $phpsystemd);
		exit(1);
	}
} else {
	icelog("Could not find PHP systemd file %s: No such file or directory", $phpsystemd);
	exit(1);
}

if (file_exists($phpfpm_defaultpoold)) {
	$ok = 1;
} elseif (file_exists($phpfpm_defaultpoold.".bak")) {
	$ok = 1;
	$phpfpm_defaultpoold = $phpfpm_defaultpoold.".bak";
} else {
	$ok = 0;
}

if ($ok) {
	icexec("Reading PHP FPM default pool.d %s...", $phpfpm_defaultpoold);
	$s = file_get_contents($phpfpm_defaultpoold);
	icelog("Preparing PHP FPM master pool.d %s...", $phpfpm_masterpoold);
	$s = str_replace("[www]", "[teapanel-master]", $s, $n);
	if (!$n) {
		icelog("Could not find [www] state");
	}
	$s = str_replace("listen.owner = www-data", "listen.owner = root", $s, $n);
	if ((!$n) && (!preg_match("/listen\.owner\s?=\s?root/Usi", $s))) {
		icelog("Could not find the listen.owner variable in %s", $phpfpm_defaultpoold);
		exit(1);
	}
	$s = str_replace("listen.group = www-data", "listen.group = root", $s, $n);
	if ((!$n) && (!preg_match("/listen\.group\s?=\s?root/Usi", $s))) {
		icelog("Could not find the listen.group variable in %s", $phpfpm_defaultpoold);
		exit(1);
	}

	icelog("Writing PHP FPM master pool.d %s...", $phpfpm_masterpoold);
	if (! file_put_contents($phpfpm_masterpoold, $s)) {
		icelog("An error occured when writing PHP FPM master pool.d %s", $phpfpm_masterpoold);
		exit(1);
	}

	icelog("Preparing PHP FPM prod pool.d %s...", $phpfpm_prodpoold);
	$s = str_replace("[teapanel-master]", "[teapanel-prod]", $s, $n);
	if (!$n) {
		icelog("Could not find [teapanel-master] state");
	}
	$s = str_replace("listen.owner = root", "listen.owner = $paneluser", $s, $n);
	$s = str_replace("listen.group = root", "listen.group = $paneluser", $s, $n);
	$s = str_replace("listen = /run/php/php7.2-fpm.sock", "listen = 127.0.0.1:34441", $s, $n);
	
	if (!$n) {
		icelog("Could not find php-fpm unix socket");
		exit(1);
	}

	icelog("Writing PHP FPM prod pool.d %s...", $phpfpm_prodpoold);
	if (! file_put_contents($phpfpm_prodpoold, $s)) {
		icelog("An error occured when writing PHP FPM prod pool.d %s", $phpfpm_prodpoold);
		exit(1);
	}

	if (! preg_match("/.+\.bak$/", $phpfpm_defaultpoold)) {
		rename($phpfpm_defaultpoold, $phpfpm_defaultpoold.".bak");
	}

	icelog("Restarting PHP FPM service: systemctl restart php7.2-fpm...");
	if (! preg_match("/success/", icexec("systemctl restart php7.2-fpm && echo success"))) {
		icelog("An error occured when restarting PHP FPM service");
		exit(1);
	}

} else {
	icexec("Could not find PHP FPM default pool.d %s: No such file or directory", $phpfpm_defaultpoold);
}