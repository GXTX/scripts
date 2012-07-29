<?php
/*  IPLocked Session Class
 *  ------------------------------------------
 *  Author: wutno (#/g/tv - Rizon)
 *
 *
 *  GNU License Agreement
 *  ---------------------
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 *  http://www.gnu.org/licenses/gpl-2.0.txt
 *
 *  CREATE TABLE IF NOT EXISTS `user_sessions` (
 *  `user_id` int(11) NOT NULL,
 *  `session_id` text NOT NULL,
 *  `ipv4` text NOT NULL,
 *  `timeout` int(11) NOT NULL
 *  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
 */

class sessionClass {
	public $logged_in;
	public $logged_userid;

	public function __construct() { #$logged_userid=false
		#$this->logged_in = false;
		#$this->logged_userid = $logged_userid;
		session_start();
		$this->checkSession();
	}

	public function destroySession($id){
		global $db;
		@session_unset();
		@session_destroy();
		$this->logged_userid = false;
		$this->logged_in = false;
		$db->query("DELETE FROM `user_sessions` WHERE `user_id` ='".$id."'") or die ($db->error);
	}

	public function openUser($id){
		global $db;
		$this->logged_userid = $_SESSION["userid"] = $id;
		$this->logged_in = true;
		$timeout = time()+1800; //30 minute timeout
		$db->query("INSERT INTO `user_sessions` (`user_id`, `session_id`, `ipv4`, `timeout`) VALUES ('".$id."', '".session_id()."', '".$_SERVER['REMOTE_ADDR']."', '".$timeout."')") or die ($db->error);
		$db->query("UPDATE `user_accounts` SET `loginip` ='".$_SERVER['REMOTE_ADDR']."', `lastlogin` ='".time()."' WHERE `id` ='".$id."'") or die ($db->error);
		$this->checkSession(); //why am I doing this again?
	}

	private function updateSession($sessionId){
		global $db;
		$timeout = time()+1800; //30 minute timeout (should let the admin set this with a global config value)
		$db->query("UPDATE `user_sessions` SET `timeout` ='".$timeout."' WHERE `session_id` ='".$sessionId."'") or die ($db->error);
	}

	private function checkSession(){ //if the users IP chages and has the same session_id() then it counts him as logged out, he has to have the same IP and session_id as in the DB
		global $db;
		if(isset($_SESSION["userid"])){
			$checksession = $db->query("SELECT `timeout` FROM `user_sessions` WHERE `user_id` ='".$_SESSION["userid"]."' AND `session_id` ='".session_id()."' AND `ipv4` = '".$_SERVER['REMOTE_ADDR']."'");
			if($checksession->num_rows == 1){
				$checksession = $checksession->fetch_array(MYSQLI_ASSOC);
				if($checksession['timeout'] > time()){ //valid user, isn't timedout
					$this->updateSession(session_id()); //make it update timeout every time he goes on to a page to keep him logged in
					$this->logged_userid = $_SESSION["userid"];
					$this->logged_in = true;
					return true;
				}
				else{
					$db->query("DELETE FROM `user_sessions` WHERE `session_id` ='".session_id()."'");
					$this->logged_userid = false;
					$this->logged_in = false;
					//return false;
				}
			}
			else if($checksession->num_rows != 0){ //since theres more than 1 lets delete all of them, its just crowding up the place
				$db->query("DELETE FROM `user_sessions` WHERE `session_id` ='".session_id()."'");
				$this->logged_userid = false;
				$this->logged_in = false;
				//return false; //we are returning false? Why not just return nothing.
			}
		}
	}

}
?>
