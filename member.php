<?php

namespace MangaReader;

/**
 * Member Class
 */
class Member
{
	function __construct(private $db=null)
	{
		# code...
	}
	
	function get_clean_url() 
	{
		$uri = trim(strtok($_SERVER['REQUEST_URI'], '?'));

		return $uri;
	}
	function is_in_url()
	{	
		$nothing_arr = [
			'/maestro',
			'/maestro/projects',
			'/maestro/add-manga',
			'/maestro/add-chapter',
			'/maestro/members',
			'/maestro/statistics',
			'/maestro/chats',
			'/maestro/notifications',
			'/maestro/settings',
			'/member',
			'/member/account',
			'/member/settings'
		];

		if (in_array($this->get_clean_url(), $nothing_arr))
		{
			return true;
		}
		return false;
	}

	function auth($t=0)
	{
		//session_start(); //just be safe, start the session
	    if(!isset($_SESSION["ss_auth"])) {
			if ($this->get_clean_url()==='/member/login') {
				return;
			}elseif ($this->get_clean_url()==='/member/register') {
				return;
			}else{
				header("Location: /member/login");
				exit();
			}
	    }else{
	    	if ($this->is_in_url()) {
				return;
			}else{
		    	if ($t===0) {
		    		header("Location: /member");
		    		exit();
		    	} else {
					header("Location: /maestro");
					exit();
		    	}
		    }

	    }
	}
	
	function register(array $_data)
	{
		$data = [
			'username'=>$_data['user_name'],
			'email'=>$_data['e_mail'],
			'fullname'=>$_data['first_name'].' '.$_data['last_name'],
			'nickname'=>$_data['user_name'],
			'password'=>$this->hash_password($_data['password'], (int)$_data['s_key']),
			'description'=>'-',
			'birth_date'=>date("Y-m-d H:i:s T", time()),
			'join_date'=>date("Y-m-d H:i:s T", time())
		];

		if ($this->check_username($data['username'])) {
			return ['error'=>'Username exists, change it.'];
		} elseif($this->check_email($data['email'])) {
			return ['error'=>'E-Mail exists. Try Login.'];
		} else {
			if($this->db->save('member', $data)) { return true; }
			else { return ['error'=>'Unexpected Error']; }
		}
		/*
		$_S=date("Y-m-d H:i:s T", time());

		$db->save('manga', $manga);

		if ($this->check_username($data['username'])) {
			return ['error']['Username exists, change it.'];
		} elseif($this->check_email($data['email'])) {
			return ['error']['E-Mail exists. Try Login.'];
		}
		*/
		
	}
	
	function login(array $_data)
	{	
		$data = [
			'username'=>$_data['user_name'],
			'password'=>$_data['password'],
			'secret'=>(int)$_data['s_key']
		];

		//echo "<pre>";
		if ($this->check_username($data['username'])) {
			if ($this->verify_password($data['password'], $data['secret'], $this->load_password($data['username']))) {
				return true;
				//return ['error'=>'Welcome '.$data['username']];
			} else {
				return ['error'=>'Wrong password or secret key'];
			}
			
		} else {
			return ['error'=>'Username: \''.$data['username'].'\' not found'];
		}
		
		//echo "</pre>";
		/*
		session_start();
		if ($rows == 1) {
			$_SESSION['ss_auth'] = true;
			// Redirect to user dashboard page
			header("Location: /member/dashboard");
		} else {
			//Incorrect
		}
		*/
	}

	function is_logged_in()
	{
		//session_start(); //just be safe, start the session

		if(isset($_SESSION['ss_auth']) && $_SESSION['ss_auth'] == true){
			return true;
		}
		return false;
	}
	
	function logout()
	{
		//session_start(); //just be safe, start the session
		if ($this->is_logged_in()) {
			// Destroy session
			if(session_destroy()) {
				// Redirecting To Login Page
				//header("Location: /member/login");
				return true;
			}
		} else {
			return false;
		}
	}
	
	function forgot($email)
	{
		# code...
	}
	
	function reset($ukey)
	{
		# code...
	}
	
	function get_user($id, $key='id')
	{
		$member = $this->db->read('member', ['where' => '`'.$key.'` = "'.$id.'"'], 'one');
		if (isset($member->id)) {
			$data = new \stdClass;
			$data->user_id=(int)$member->id;
			$data->user_name=$member->username;
			$data->nick_name=$member->nickname;
			$data->full_name=$member->fullname;
			/* $data->birth_date=date('d-m-Y', strtotime($member->birth_date)), */
			$data->join_date=date('d M Y', strtotime($member->join_date));
			$data->description=$member->description;
			
			return $data;
		} else {
			return ['error'=>'No member with this ID:'.$id];
		}
	}
	
	function edit($id, array $data)
	{
		if ($id !== $data['id']) {
			return false;
		} else {
			$this->db->save('member', $data, 'id');
			return true;
		}
	}
	
	function delete($id, $req_id)
	{
		if ($id !== $req_id) {
			return false;
		} else {
			$this->db->delete($id);
			return true;
		}
	}

	private function toNumber($dest)
    {
        if ($dest)
            return ord(strtolower($dest)) - 96;
        else
            return 0;
    }
	private function hash_password($password, $secret_key)
	{
		$pw = password_hash($password.$secret_key, PASSWORD_BCRYPT);
		
		return $pw;
	}
	private function verify_password($password, $secret_key, $hashed)
	{
		if (password_verify($password.$secret_key, $hashed)) {
			return true;
		} else {
			return false;
		}
	}

	private function load_password($user)
	{
		$member = $this->db->read('member', ['where' => '`username` = "'.$user.'"'], 'one');
		if (isset($member->id)) {
			return $member->password;
		} else {
			return false;
		}
	}

	function check_email($email)
	{
		$member = $this->db->row_count('member', $email, 'email');
		if ($member) {
			return true;
		} else {
			return false;
		}
	}
	function check_username($user)
	{
		$member = $this->db->row_count('member', $user, 'username');
		if ($member) {
			return true;
		} else {
			return false;
		}
	}

	function is_in_group($id)
	{
		$member = $this->db->row_count('group_stat', $id, 'uid');
		if ($member) {
			return true;
		} else {
			return false;
		}
	}
}