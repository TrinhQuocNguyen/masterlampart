<?php
namespace Config;


/**
 * Class Database
 * @package Config
 */
class Env
{
	const DB_TYPE = 'MYSQL';
	const DB_HOST = '127.0.0.1';
	const DB_NAME = 'phplampart';
	const DB_USER = 'root';
	const DB_PASS = '';

	const APP_URL = 'http://masterlampart.me/';
	const EMAIL_FROM = 'nguyenquoctrinhctt3@gmail.com';
	const EMAIL_REPLY_TO = 'nguyenquoctrinhctt3@gmail.com';
	const SECRET_TOKEN = 'TrinhDepTraiTuyetVoi';
	const SENT_EMAIL_ACC = 'harveynashtms@gmail.com';
	const SENT_EMAIL_PASS = 'TrinhDepTrai';
	const EXPIRE_TIME = 120;// in seconds

	public static function env($key, $default = null)
	{
		$value = '';

		return $value;
	}


}