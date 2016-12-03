<?php
namespace App\Service;

use App\Model\User;
use App\Model\Token;
use Config\Env;
use DoctrineTest\InstantiatorTestAsset\ExceptionAsset;
use \Exception;

/**
 * Class UserService
 * @package App\Service
 */
class UserService extends Service
{

	/**
	 * validate data to register a new user
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function registration($data = [])
	{


		$result            = [];
		$result['error']   = false;
		$result['message'] = [];

		if (!validate($data['fullname'], 'fullname')) {
			$result['error']     = true;
			$result['message'][] = 'Fullname contains a-Z and letters, length : 4-30';
		}

		if (!validate($data['username'], 'username')) {
			$result['error']     = true;
			$result['message'][] = 'Username contains a-Z0-9 and underscore, length : 4-30';
		}

		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$result['error']     = true;
			$result['message'][] = 'Email invalid';
		}

		if (!validate($data['password'], 'password')) {
			$result['error']     = true;
			$result['message'][] = 'Password contains a-Z0-9, special characters follow: @#$%!, length : 3-20';
		}

		if ($data['password'] != $data['re_password']) {
			$result['error']     = true;
			$result['message'][] = 'Re-password invalid';
		}

		if ($data['password'] == $data['username']) {
			$result['error']     = true;
			$result['message'][] = 'Username and password must different';
		}

		if (strlen($data['address']) == 0) {
			$result['error']     = true;
			$result['message'][] = 'Address is required';
		}

//		if ($data['code'] != $_SESSION['code_capcha']) {
//			//$result['error'] = true;
//			$result['message'][] = 'Sercurity code invalid';
//		}

		if (!(($data['sex'] == 1) || ($data['sex'] == 2))) {
			$result['error']     = true;
			$result['message'][] = 'Sex invalid';
		}

		if (!(checkdate(explode('-', $data['birthday'])[1], explode('-', $data['birthday'])[2],
				explode('-', $data['birthday'])[0]) && (strtotime($data['birthday']) < time()))
		) {
			$result['error']     = true;
			$result['message'][] = 'Birthday invalid';
		}

		if ($result['error'] == false) {
			$user = new User;

			// check duplicate username
			$username_check = $user->where('username', $data['username'])->first();
			if ($username_check) {
				$result['error']     = true;
				$result['message'][] = 'Username is existed';
			}

			// check duplicate email
			$email_check = $user->where('email', $data['email'])->first();

			if ($email_check) {
				$result['error']     = true;
				$result['message'][] = 'Email is existed';
			}
			// If there is no any errors -> create
			if ($result['error'] == false) {
				unset($result['error']);
				unset($result['message']);
				unset($data['re_password']);
				unset($data['code']);
				$data['password'] = md5($data['password']);

				//insert
				if ($user->insert($data)) {
					$current_user = $user->get_insert();

					//create token
					$token_code = md5(time());
					$data_token = [
						'user_id' => $current_user['id'],
						'token'   => $token_code,
						'type'    => "account",
						'status'  => 0,
					];
					$token      = new Token;
					if ($token->insert($data_token)) {
						$header        = mail_header();
						$content_email = "Click <a href='http://masterlampart.me/user/confirm/$token_code'>here</a> to active account in <a href='http://dev.lampart.com.vn'>http://dev.lampart.com.vn</a> \n ";
						@mail('@lampart-vn.com', 'Active account', $content_email, $header);
					}
					$result['error'] = false;
				} else {
					$result['error']     = true;
					$result['message'][] = 'Error when create a new user -- Not defined yet';
				}
			}
		}

		return $result;
	}


	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function login($data = [])
	{
		try {
			//validate
			if (!(validate($data['username'], 'username') && validate($data['password'], 'password'))) {
				throw new Exception("Username or password invalid");
			}

			//check user exist
			$user = new User();
			$user = $user->login($data['username'], $data['password']);

			if (!$user) {
				throw new Exception("Username or password invalid");
			}

			// check active status
//			if ($user['status'] == 0) {
//				 throw new Exception("Please active account before login");
//			}

			$result = ["error" => false, "user" => $user];
		} catch (Exception $e) {
			$result["error"]     = true;
			$result['message'][] = $e->getMessage();
		}

		return $result;
	}

	public function confirm($key)
	{
		try {
			$token      = new Token();
			$token_info = $token->where('token', $key)->where('status', 0)->first();
			if (!$token_info) {
				throw new Exception("Token not exists");
			}

			$confirm_class = "App\\Service\\Confirm\\Confirm" . ucfirst(strtolower($token_info["type"]));
			if (!class_exists($confirm_class)) {
				throw new Exception("Type token not exists");
			}

			$confirm        = new  $confirm_class($token_info);
			$confirm_result = $confirm->confirm();

			if (!$confirm_result["status"]) {
				throw new Exception($confirm_result["message"]);
			}

			$result["error"]   = false;
			$result["message"] = $confirm_result["message"];
		} catch (Exception $e) {
			$result = ["error" => true, "message" => $e->getMessage()];
		}

		return $result;
	}

	public function change_profile($id, $params = [])
	{
		$result = $params;
		$error  = false;
		// validate
		if (!validate($params['fullname'], 'fullname')) {
			$error               = true;
			$result['message'][] = 'Fullname contains a-Z and letters, length : 4-30';
		}
		if (!validate($params['address'], 'address')) {
			$error               = true;
			$result['message'][] = 'Address is required';
		}
		if (!validate($params['sex'], 'sex')) {
			$error               = true;
			$result['message'][] = 'Sex is invalid';
		}

		// from yy-mm-dd to mm-dd-yy
		if (!(checkdate(explode('-', $params['birthday'])[1], explode('-', $params['birthday'])[2], explode('-', $params['birthday'])[0]) && (strtotime($params['birthday']) < time()))) {
			$error               = true;
			$result['message'][] = 'Birthday is invalid';
			$result['message'][] = 'Birthday is invalid';
		}
		// update user
		if (!$error) {
			$user = new User();
			if ($user->update_id($id, $params)) {
				$result          = $params;
				$result['error'] = false;
			} else {
				$result['error']     = false;
				$result['message'][] = '(Error)- Please check you data again.';
			}
		} else {
			$result['error'] = true;
		}

		return $result;
	}

	public function change_password($params = [])
	{
		try {
			$id               = $params["id"];
			$password         = $params["password"];
			$new_password     = $params["new_password"];
			$confirm_password = $params["confirm_password"];
			// validate
			if (($password == '') || ($new_password == '') || ($confirm_password == '')) {
				throw new Exception("Please enter all fields");
			}
			if (!(validate($password, 'password') && validate($new_password, 'password'))) {
				throw new Exception("Password a-Z0-9, special characters !@#$%, length 3-20");
			}
			if ($new_password != $confirm_password) {
				throw new Exception("Confirm password invalid");
			}
			if ($password == $new_password) {
				throw new Exception("New password is current password");
			}
			$user   = new User();
			$result = $user->where('id', $id)->where('password', md5($password))->first();

			if (!$result) {
				throw new Exception("Current password is invalid");
			}
			// update
			$result = $user->where('id', $id)->update(['password' => md5($new_password)]);
			if (!$result) {
				throw new Exception("Update password failed");
			}
			$result = ["error" => false];
		} catch (Exception $e) {
			$result = ["error" => true, "message" => $e->getMessage()];
		}

		return $result;
	}

	public function change_email($params = [])
	{
		try {
			// validate
			if ($params['email'] == '') {
				throw new Exception("Email is empty");
			}
			if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception("Email is invalid");
			}
			$id    = $params['id'];
			$email = $params['email'];
			$user  = new User();

			// check duplicate
			$result = $user->where('email', $email)->first();
			if ($result) {
				throw new Exception("Email is existed");
			}
			// Check whether request is exist or not
			$token  = new Token();
			$result = $token->where('content', $email)->where("user_id", $id)
				->where("status", 0)->first();
			if ($result) {
				throw  new Exception("The request is existed");
			}

			// if not, then create a new token
			$token_code = md5(time() . Env::SECRET_TOKEN);
			$data_token = [
				'token'   => $token_code,
				'user_id' => $id,
				'content' => $email,
				'type'    => "email",
				'status'  => 0,
			];
			$result     = $token->insert($data_token);
			if (!$result) {
				throw new Exception("Update email failed (Can not insert token)");
			}

			$to      = 'nguyenquoctrinhctt3@gmail.com';
			$subject = 'Change your email in masterlampart';
			$message = "Click <a href='" . Env::APP_URL . "/user/confirm/$token_code'>here</a>
 				to agree to change email for $email \n ";
			$headers = get_mail_header();

			$res = mail($to, $subject, $message, $headers);
			if (!$res) {
				throw new Exception("Mail is not sent yet or not accepted");
			}

			$result = ["error" => false];

		} catch (Exception $e) {
			$result = ["error" => true, "message" => $e->getMessage()];
		}

		return $result;
	}
}