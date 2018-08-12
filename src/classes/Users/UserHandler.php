<?php

namespace Users;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package Users
 * @version 0.0.1
 */
class UserHandler
{
	public function getUserHosts()
	{
		if (file_exists($f = USERS."/{$user}/credentials.json")) {
			$cred = json_decode(file_get_contents($f), true);
			
		} else {
			throw new Exception();
		}
	}
}