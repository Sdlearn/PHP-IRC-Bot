<?php
set_time_limit(0);
ini_set('display_errors', 'on');
$config = array(
        'server' => '', // server, install ssl, use ssl://irc.hackthissite.org (port 7000)
        'port'   => , // port numbers regular = 6667, ssl = 6697, 7000
        'channel' => '', // channel name
        'name'   => '', // name
        'nick'   => '',  // nick
        'pass'   => '', // password
	'owner'  => '',
);


// Plan on breaking out of the use of these global variables soon

$user = NULL;
$fullUser = NULL;
$message = NULL;
$filesize = NULL;
$playertotal = NULL;

class IRCBot {
        var $socket;
        var $ex = array();
        var $state = 0;
        var $myhealth = array();

        function __construct($config) {
           $this->socket = fsockopen($config['server'], $config['port']);
           $this->main($config);
        }

        function login($config) {
            $this->send_data('USER', $config['nick'].' here.com '.$config['nick'].' :'.$config['name']);
            $this->send_data('NICK', $config['nick']);
        }

        function main($config) {
          $data = trim(fgets($this->socket)); //not setting a length for fgets(), will keep reading untill it gets a newline (which should be at around 256 bytes but could be way way later
          if ($data == "") { // if the buffer is empty
            usleep(50000); //5ms
            $this->main($config); //recursion instead of a loop, how fancy of you
            }
              else {
                 echo nl2br($data);
                 flush();
                 $this->ex = explode(' ', $data); // exploding the data from the buffer into an array called ex, via spaces
                 if($this->ex[0] == 'PING') {
                    $this->send_data('PONG', $this->ex[1]); //Plays ping-pong with the server to stay connected.
                 }
                    else {
                        $addr = strtolower(substr($this->ex[0],1));
                        if ($this->state == 0) {
                            $server_addr = $addr; //this address is useful for checking for server notices (like if your nick changes failed or pretty much any failures)
                            sleep(2);
                            $this->login($config); //basic irc auth required to connect
                            $this->state++;
                        }

                        $command = trim($this->ex[3]);
                        $nick= strtolower(substr($this->ex[0],1,stripos($this->ex[0],"!")-1)); //escape this shit if using a db
                        $chan = strtolower(trim($this->ex[2]));

                        if ((trim($this->ex[1]) == "NOTICE") && ($this->state == 1) && ($nick == "nickserv")) {
                            $this->join_channel($config['channel']);
                            $this->state++;
                        }

              //***************************************************************************************************
              //***************************************************************************************************
/*if($this->ex[0] == ':flurbbot!flurbbot@HTS-DE1BB303.hsd1.ma.comcast.net') {
    $this->send_message('Fuck you flurberty');
}*/

                        /*for($i=1; $i <= (count($this->ex)); $i++) { // youtube video finder
                            $input .= $this->ex[$i]." ";
                        }
                        $input = rtrim($input);
                        if(preg_match( '#youtube.com/watch?[^\s]*v=([0-9a-zA-Z_\-]*)#i',$input,$matches)) {
                            $site = "http://www.".$matches[0].$matches[1];

                            //$this->send_message($matches[1]);
                            $content = file_get_contents($site);

                            $search = '<meta name="twitter:title" content="';
                            $searchb = '<meta name="twitter:description" content="';

                            $pieces = explode($search, $content);
                            $piece = explode ('">', $pieces[1]);

                            $piecesb = explode($searchb, $content);
                            $pieceb = explode('">', $piecesb[1]);

                            $this->send_message("Youtube URL: ".htmlspecialchars_decode($piece[0])."\n");
                            $this->send_message("Video Description: ".htmlspecialchars_decode($pieceb[0])."\n");
                        }*/


              switch($command) { // list of commands the bot will respond to from users
                 /* case '::':
                      $text = $this->get_message();
                      if(preg_match('/http://www.youtube.com/watch?v=/', $text)) {
                          $this->send_message("Match found!");
                      }
                      $this->send_message("Debugging: ".$text);*/
                /*case ':$test':
                    if($this->is_banned() == true) { // if the user is banned
                        break;
                    }
                    // test code
                      break;*/

              //----------------------------------------------------------------------------------------------------------------
                  case ':$youtube':
                     $site = $this->ex[4];
                      $content = file_get_contents($site);

                      $search = '<meta name="twitter:title" content="';
                      $searchb = '<meta name="twitter:description" content="';

                      $pieces = explode($search, $content);
                      $piece = explode ('">', $pieces[1]);

                     $piecesb = explode($searchb, $content);
                     $pieceb = explode('">', $content);

                      $this->send_message("Youtube URL: ".htmlspecialchars_decode($piece[0])."Description: ".htmlspecialchars_decode($pieceb[0])."\n");
                      break;

                  case ':$addplayer':
                      $this->con_mysql();
                      $health = 100; // health
                      $healthTotal = 100;
                      $speed = 100; //speed
                      $attack = 100; // attack
                      $defense = 100;// defense
                      $name = $this->ex[4]; //name
                      $level = 1; // level
                      $class = $this->ex[5]; //level
                      mysql_query("INSERT INTO players (health, healthTotal, speed, attack, defense, name, level, class) VALUES ('".$health."', '".$healthtotal."', ".$speed."', '".$attack."', '".$defense."', '".$name."', '".$level."', '".$class."')");
                      $this->send_message("Query sent, check to make sure it worked...");
                      break;


               //----------------------------------------------------------------------------------------------------------------

                  case ':$register':
                      $this->who_is();
                      $this->con_mysql();
                      $user = rtrim($GLOBALS['user']);
                      $pass = $this->ex[4];
                      $salt = "0osdf87ijflkj";
                      $pass = $salt.$pass;
                      $pass = md5(rtrim($pass));
                      mysql_query("INSERT INTO ninja_login (login_name, login_pass) VALUES ('".$user."', '".$pass."')");
                      $this->send_message("You have successfully registered an account!");
                      break;

               //----------------------------------------------------------------------------------------------------------------

                  case ':$account': // change to $account with the switch case of login, logout, status
                      $choice = $this->ex[4];
                      $this->con_mysql();
                      $this->who_is();
                      $user = $GLOBALS['user'];
                      switch($choice) {
                          case 'info':
                              if($this->check_login() != true) {
                                  $this->send_message("You must first be logged in to get the account details!");
                              }
                              else {
                                  $getinfo = mysql_query("SELECT * FROM players WHERE name='".$user."'");
                                  $getinventory = mysql_query("SELECT * FROM inventory WHERE name='".$user."'");
                                  while($info = mysql_fetch_array($getinfo)) {
                                      $health = $info['health'];
                                      $speed = $info['speed'];
                                      $attack = $info['attack'];
                                      $defense = $info['defense'];
                                      $name = $info['name'];
                                      $level = $info['level'];
                                      $class = $info['class'];
                                      $exp = $info['exp'];
                                      $healthtotal = $info['healthTotal'];
                                      $this->send_message("General Stats >> Health: ".$health." Max Health: ".$healthtotal.", Speed: ".$speed.", Attack: ".$attack.", Defense: ".$defense.", Name: ".$name.", Level: ".$level.", Class: ".$class.", Exp: ".$exp);
                              }
                                  while($inventory = mysql_fetch_array($getinventory)) {
                                      $weapon = $inventory['weapon'];
                                      $healthpots = $inventory['healthpot'];
                                      $poisonpots = $inventory['poisonpot'];
                                      $gold = $inventory['gold'];
                                      $armor = $inventory['armor'];
                                      $helmet = $inventory['helmet'];
                                      $leggings = $inventory['leggings'];
                                      $this->send_message("Equiped With >> Weapon: ".$weapon.", Armor: ".$armor.", Helmet: ".$helmet.", Leggings: ".$leggings.", Gold: ".$gold.", Health Potions: ".$healthpots.", Poison Potions: ".$poisonpots);
                                  }
                              }
                                  break;
                          case 'status':
                              if($this->check_login() == true) {
                                  $this->send_message("You are currently logged in!");
                              }
                              else {
                                  $this->send_message("You are not logged into an account!");
                              }
                              break;
                          case'login':
                              $pass = $this->ex[5]; // if the user placed a password here
                              //$this->check_login($pass);
                              $salt = "0osdf87ijflkj";
                              $pass = rtrim(md5($salt.$pass));
                              $value = "True";
                              $getpass = mysql_query("SELECT login_pass FROM ninja_login WHERE login_name='".$user."'");
                              while($row = mysql_fetch_array($getpass)) {
                                  if($row['login_pass'] == $pass) {
                                      mysql_query("UPDATE ninja_login SET logged_in='".$value."' WHERE login_name='".$user."'"); // Query to set logged_in to "True"
                                      $this->send_message("You have successfully logged in!");
                                      break;
                                  }
                                  else{
                                      $this->send_message("ERROR: The password did not match for your username!");
                                      break;
                                  }
                              }
                              break;
                          case 'logout':
                              if($this->check_login() == false) {
                              $this->send_message("You can not log out of an account you are not logged into!");
                              break;
                          }
                              $query = mysql_query("SELECT logged_in FROM ninja_login WHERE login_name='".$user."'");
                              $false = "False";
                              while($row = mysql_fetch_array($query)) {
                                  if($row['logged_in'] == "True") {
                                      mysql_query("UPDATE ninja_login SET logged_in='".$false."'");
                                      $this->send_message("You have successfully logged out!");
                                      break;
                                  }
                              }
                      }
                    break;

               //----------------------------------------------------------------------------------------------------------------

                  case ':$getplayer':
                      $this->con_mysql();
                      $user = $this->ex[4];
                      $query = mysql_query("SELECT * FROM players WHERE name='".$user."'");
                      while($row = mysql_fetch_array($query)) {
                          $this->send_message("The user is: ".$row['name']." and has ".$row['health']." health!");
                         /* foreach($row as $message) {
                              $this->send_message($message);
                          }*/
                      }
                      break;
              //-----------------------------------------------------------------------------------------------------------------

                  case ':$ninja':
                      $this->con_mysql();
                      if($this->is_banned() == true) {
                          break;
                      }
                     // if($this->is_admin() != true) {
                       //   $this->send_message("Protecting this function from the l337 kids around the block!");
                    //  }
                      if($this->is_admin() xor $this->is_mod() != true) {
                          $this->send_message("You are not authorized to use this command, sorry!");
                          break;
                      }
                      switch($this->ex[4]) {
                          case 'start':

                              break;

                          case 'attack':
                              $this->con_mysql(); // connecting to ninja db
                              $user = $this->ex[5]; // grabbing the user to attack
                              if($this->check_login() == "True") { // see if user is logged in
                                $this->attack_user($user); // send attack request
                                $chance = rand(1,2); // 50% chance to get loot
                                if($chance == 1) {
                                    $random = rand(1,6); // random is to be used to select a sword via the sword_id
                                    $query = mysql_query("SELECT type FROM swords WHERE sword_id='".$random."'");
                                    $this->send_message("You found a ".$query);
                                    /*while($row = mysql_fetch_array($query)) {
                                        $que = "SELECT "
                                        $this->send_message("You have found a ".$row["$random"]);
                                    }*/
                                }
                                  else {
                                      $this->send_message("No loot was found!");
                                  }
                              }
                              else {
                                  $this->send_message("You must be logged in to attack another player!");
                              }
                              break;
                      }
                      break;
                    /*
                                        $nick
                            Will change the nickname of the bot
                            check if user running the command is admin
                            if so, run the command; otherwise, do not.
                     */

			    case ':$nick':
                    if($this->is_banned() == true) { // if the user is banned
                        break;
                    }
                    $nick = $this->ex[4]; // storing the specified nick
				    if($this->is_admin() == true) { // if the user is the admin
				        $this->send_data('NICK', $nick); // change the nick
				    }
				    else { // if the user is not the admin
				        $this->send_message("Only Ninjex and mods can use this command!");
				    }
				    break;

             //------------------------------------------------------------------------------------------------------------------

                  case ':$lulz':
                      if($this->is_banned() == true) { // if the user is banned
                          break;
                      }
                      $namefile = @fopen("game/names.txt", "r");
                      $nametitle = @fopen("game/nametitle.txt", "r");
                      $verbsfile = @fopen("game/verbs.txt", "r");

                      if($namefile) {
                          $names = explode("\n", rtrim(fread($namefile, filesize("game/names.txt"))));
                      }
                      if($nametitle) {
                          $titles = explode("\n", rtrim(fread($nametitle, filesize("game/nametitle.txt"))));
                      }
                      if($verbsfile) {
                          $verbs = explode("\n", rtrim(fread($verbsfile, filesize("game/verbs.txt"))));
                      }
                      shuffle($names);
                      shuffle($titles);
                      shuffle($verbs);
                      $this->send_message($names[0]." ".$titles[0]." ".$verbs[0]." ".$names[1]." ".$titles[1]."\n");
                      break;

             //------------------------------------------------------------------------------------------------------------------
                    case ':$joke':
                        if($this->is_banned() == true) { // if the user is banned
                            break;
                        }
                        $input = NULL;
                        for($i=4; $i <= (count($this->ex)); $i++) { // grabbing the message
                            $input .= $this->ex[$i]." "; // storing the message in input
                        }
                        $input = trim($input);
                        $insultfile = @fopen("insults.txt", "r");
                        $rand = rand(0,47);
                        if($insultfile) {
                            $insult = explode("\n", rtrim(fread($insultfile, filesize("insults.txt"))));
                        }
                        shuffle($insult);
                        $this->send_message($input.", ".$insult[$rand]);
                        break;

            //-------------------------------------------------------------------------------------------------------------------

                  /*
                                           $say
                           Will force the bot to repeat text following ex[3]
                   */

                case ':$say':
                    if($this->is_banned() == true) { // if the user is banned
                        break;
                    }
                    $start = 4; // getting a initializer to count from
                    $message = $this->get_message($start); // grabbing our message
                    $this->send_message($message); // echoing the message
                    break;

             //-------------------------------------------------------------------------------------------------------------------

                  /*
                                            $ban
                            Add a user to the ban file (banned_users.txt)
                                Check if user running command is Admin
                                If the user is admin allow execution
                                otherwise, do not and print error
                   */

                case ':$ban':
                    if($this->is_banned() == true) { // if the user is already banned
                        break;
                    }
                    if($this->is_admin() != true) { // if the user is not the admin
                        $this->send_message('Only Ninjex may use the $ban command!');
                        break;
                    }
                    else{ // if the user is not banned
                        $this->ban_user(); // calling ban function (takes ex[4] by default)
                        $this->send_message("Successfully banned the user!");
                    }
                      break;

             //-------------------------------------------------------------------------------------------------------------------

                  /*
                                                $mod
                            Add a user to the moderator file list (mods.txt)
                                Check if user running command is Admin
                                If the user is admin, allow execution
                                Otherwise, do not, and print error
                   */

			    case ':$mod':
                    if($this->is_banned() == true) { // if the user is banned
                        break;
                    }
                    $user = $this->ex[4]; // grabbing the user from input
                    if($this->is_admin() != true) { // if the user is not the admin
                        $this->send_message('Only Ninjex may use the $mod command!');
                        break;
                    }
                    elseif($this->check_mod($user) == true) { // if the user is already a mod
                        $this->send_message("The user is already a moderator!");
                        break;
                    }
                    else { // if the user is not a mod
                        $person = $this->ex[4]; // getting the user from the command
				        $this->make_mod(); // using make_mod() function to mod the user
                        $this->send_message("Successfully added the user to the mod list!");
                    }
                    break;

            //-------------------------------------------------------------------------------------------------------------------

                  /*
                                                $rmod
                              Remove the given user from the mod file (mods.txt)
                                Check if the user running the command is Admin
                                If the user is Admin, run the command
                                Otherwise do not, and print error
                   */

                case ':$rmod':
                    if($this->is_banned() == true) { // if the user is banned
                        break;
                    }
                    if($this->is_admin() != true) { // if the user is not the admin
                        $this->send_message("Only Ninjex may use this command!");
                    }
                    else {
                        $person = $this->ex[4]; // grabbing the specified user
                        if(strlen($person) < 1) { // if the specified user is null or no user given
                            $this->send_message("You did not specify a user to revoke mod from, breaking for your convenience!");
                            break;
                        }
                        else {
                            exec("sed '/'$person'/d' mods.txt >> tempt.txt"); // using sed to remove the mod from the file
                            exec("mv tempt.txt mods.txt");
                            exec("rm tempt.txt");
                            $this->send_message("Successfully revoked privileges from the desired mod!");
                        }
                    }
                      break;

            //-------------------------------------------------------------------------------------------------------------------

                  /*
                                                $who
                                Display the user's short name and extended name to them
                   */

                case ':$who':
                    if($this->is_banned() == true) { // if the user is banned
                        break;
                    }
                    $this->who_is(); // calling the who_is function
                    if($this->is_mod() xor $this->is_admin() == false){ // if the user is not a mod or an admin
				        $this->send_message('You are a standard user with the name of: ' . $GLOBALS['user'] . ' and the extended name of: ' . $GLOBALS['fullUser']);
                    }
                    elseif($this->is_mod() xor $this->is_admin() == true) { // if the user is a mod or admin
                        $this->send_message('You are a privileged user with the name of: ' . $GLOBALS['user'] . ' and the extended name of: ' . $GLOBALS['fullUser']);
                    }
				break;

            //-------------------------------------------------------------------------------------------------------------------

                  case ':$mod?':
                      if($this->is_banned() == true) { // if the user is banned
                          break;
                      }
                      $user = $this->ex[4]; // grabbing the user
                      if($this->check_mod($user) == true) { // if the user is a moderator
                          $this->send_message("The user is a moderator!");
                      }
                      else { // if the user is not a moderator
                          $this->send_message("The user is not a moderator!");
                      }
                  break;

            //-------------------------------------------------------------------------------------------------------------------

                  /*
                                                $join
                                Force the bot to join a given channel (ex[4])
                                Check if the user running command is Admin
                                If the user is Admin execute the command
                                Otherwise, do not and print out an error
                   */

                case ':$join':
                    if($this->is_banned() == true) { // if the user is banned
                        break;
                    }
                    if($this->is_admin() == true) { // if the user is the admin
                        $this->join_channel($this->ex[4]);
				    }
                    else { // if the user is not the admin
                        $this->send_message('Sorry, only Ninjex can use the $join command!');
                    }
                break;

            //---------------------------------------------------------------------------------------------------------------------

                  /*
                                                $gtfo
                                Force the bot client to quit out from IRC and exit
                                Check if the user running the command is Admin
                                If the user is Admin, execute the command
                                Otherwise, do not and display an error
                   */

                case ':$gtfo':
                    if($this->is_banned() == true) { // if the user is banned
                        break;
                    }
                    if($this->is_admin() != true) { // if the user is not the admin
                        $this->send_message('Sorry, only Ninjex can use the $gtfo command!');
                    }
				    else { // if the user is the admin
                        $this->send_data('QUIT', 'Yolo');
                        exit();
				    }
				    break;

            //----------------------------------------------------------------------------------------------------------------------


                  /*
                                                $rand
                             The bot will display a random number from the given input
                                grab a min number ex[4] and a max number ex[5]
                                use rand_num($min, $max) to get a random number
                                echo the random number back to the user
                   */

			    case ':$rand':
                    if($this->is_banned() == true) { // if the user is banned
                        break;
                    }
                    $min = $this->ex[4]; // grabbing minimum number
                    $max = $this->ex[5]; // grabbing maximum number
                    if($min > 20000000 ||  $min < -20000000|| $max > 20000000 || $max < -20000000) { // if the numbers are too high or too low
                        $this->send_message('The number you entered is either too large or too small!');
                        $this->send_message('The number can not exceed 20000000 nor can it be lower than -20000000');
                    }
                    elseif(is_numeric($max) != true || is_numeric($min) != true) { // if the specified input are not numbers
                        $this->send_message('You must enter a number, not a character value!');
                    }
                    else { // if everything looks good
                        $this->random_num($min, $max); // call our random_num function
                    }
                    break;

            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                         $eunix
                            Hash a string using a salt with unix crypt
                                grab a salt value with ex[4]
                                grab a string to encrypt ex[5]+
                                  remove whitespace and crypt
                                    crypt($string,$sallt);
                                     echo the data back
                   */

			   case ':$eunix':
                   if($this->is_banned() == true) { // if the user is banned
                       break;
                   }
				   $salt = $this->ex[4]; // grabbing the salt
				   for($i=5; $i <= (count($this->ex)); $i++) { // grabbing the string to hash
                       $string .= $this->ex[$i]." "; // still grabbing the string
                   }
                   $string = rtrim($string); // removing trailing whitespace from the string
			       $crypt = crypt($string,$salt); // using crypt function to encrypt our string with the given salt
	    		   $this->send_message('The encrypted unix value of: ' . $string . ' with salt: ' . substr($salt,0,2) . ' is: ' . $crypt);
				   break;

            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                        $dunix
                        Run a dictionary attack on a unix crypt hash with salt
                            grab the salt value using substr($hash,0,2)
                            Take each word in the dictionary and hash it with the salt
                            compare the result of the hashed dictionary word to the hash
                            if a match is found, end the loop and echo the word originally
                            hashed that found a correct compare.
                   */

			 /* case ':$dunix':
                $encryption = NULL;
                $hash = rtrim($this->ex[4]);
                $salt = substr($hash,0,2);
                $file = fopen("rockyou.txt", "a+");
                $this->send_message('Running a dictionary attack on hash: ' . $hash);
                while(!feof($file) && $encryption != $hash) {
                    $word = rtrim(fgets($file));
                    $encryption = crypt($word,$salt);
                }
                if($encryption == $hash) {
                    if(preg_match("/pony/", $word)) {
                        $this->send_message('p0nieZ are evil, and so are you!');
                    }
                    else {
                        $this->send_message('The value for: ' . $hash . ' is: ' . $word);
                    }
                }
                if($encryption != $hash) {
                    $this->send_message('The hash value was not located in the dictionary!');
                }
                fclose($file);
				break; /*

            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                    $rmfile
                            Remove the hash file (md5hashes.txt)
                                This file is used to decrypt multiple hashes at once
                                Check if the user running the command is the Admin
                                If the user is in fact the Admin, the command executes
                                Use exec to execute the command to remove the file
                   */

              case ':$rmfile':
                  if($this->is_banned() == true) { // if the user is banned
                      break;
                  }
                  if($this->is_admin() xor $this->is_mod() != true) { // if the user is not a mod or admin
                      $this->send_message("You are not authorized for this command.");
                  }
                  else { // if the user is a mod or admin
                    exec("rm md5hashes.txt"); // remove the md5hash file
                    $this->send_message("The md5 hash file was removed...");
                  }
                  break;

            //-----------------------------------------------------------------------------------------------------------------------

              case ':$rmtemp':
                  if($this->is_banned() == true) { // if the user is banned
                      break;
                  }
                  if($this->is_admin() xor $this->is_mod() != true) { // if the user is not a mod or admin
                      $this->send_message("You are not authorized for this command.");
                  }
                  else { // if the user is a mod or admin
                      exec("rm tempt.txt"); // remove the tempt file
                      $this->send_message("The temporary file was removed...");
                  }
                      break;

            //--------------------------------------------------------------------------------------------------------------------------
                  /*
                                    $emd5file
                            Check if the user executing the command is Admin or mod
                            Take a list of words (ex[4]++) and convert each to md5
                            Store the value of each hash inside of md5hashes.txt
                            We can use $dmd5file to decrypt all the hashes at on time
                   */

              case ':$emd5file':
                  if($this->is_banned() == true) { // if the user is banned
                      break;
                  }
                  if($this->is_admin() xor $this->is_mod() != true ) { // if the user is not a mod or admin
                      $this->send_message("You are not authorized to use this command.");
                  }
                  else { // if the user is a mod or admin
                  $md5file = fopen("md5hashes.txt", "a+"); // opening the md5hash file
                  for($i=4; $i <= (count($this->ex)); $i++) { // grabbing the string to hash into md5
                      if($i == 14) {
                          $this->send_message("You can only insert 10 words to be hashed and inserted into the file, only 10 hashes entered!");
                          break;
                      }
                      $word = rtrim($this->ex[$i]); // removing trailing whitespace from the word
                      $hash = md5($word); // hashing the word into md5
                      fwrite($md5file, $hash."\n");
                  }
                  $this->send_message("Done writing hashes to file...");
                  }
                  break;

            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                    $dmd5file
                            Use a lookup table to find the hash values inside of md5hashes.txt
                                You may store hashes in the file with $hashfile or $emd5file
                                Check if the user executing the command is admin or mod
                                Check if the word is not over 20 characters in length
                                Echo the value for each hash if found otherwise echo nohting
                   */

              case ':$md5file':
                  if($this->is_banned() == true) { // if the user is banned
                      break;
                  }
                  if($this->is_admin() xor $this->is_mod() != true){ // if the user is not a mod or admin
                      $this->send_message("You are not authorized to use this command.");
                  }
                  else { // if the user is a mod or admin
                  $this->send_message("Checking the lookup table for the hash, please check your pm feed for the discovered hashes!");
                  $md5file = fopen("md5hashes.txt", "a+");  // opening the md5hash file - the hashes that need to be found
                  $option = rtrim($this->ex[4]); // setting an option to use either small or big for the dictionary
                  while(!feof($md5file)) { // while not at the end of the md5hash file
                      $hashinmd5file = rtrim(fgets($md5file)); // get the hash on the current line of the file
                      if(strlen($hashinmd5file) >=1 ) { // if the hash is greater than or equal to 1 in length
                          //$starttime = time();
                        $this->decrypt_md5($hashinmd5file, $option); // call the hash to our decrypt_md5 function
                          //$endtime = time();
                         // $time = $endtime-$starttime;
                          //$this->send_message("Time elapsed in second(s): ".$time);
                      }
                  }
                  $this->send_message("Done looking up hashes... Remember to check your pm feed for the discovered hash values");
                  }
                  break;
            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                         $emd5
                            Convert a string (ex[4]++) into md5
                        Echo the data of the hash back into the IRC channel
                   */

			  case ':$emd5':
                  if($this->is_banned() == true) { // if the user is banned
                      break;
                  }
                  $plainText = $this->get_message(); // grabbing the string
				  $plainText = rtrim($plainText); // remove trailing whitespace from our string
				  $encString = md5($plainText); // hash our string into md5
				  $this->send_message($plainText." hashed is: ".$encString); // echo out the md5 value
				  break;

            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                             $hashfile
                        Add a list of hash values inside of md5hashes.txt to be decrypted
                            Check if the user is Admin or mod, if so execute the command
                            grab each of the hash values with ex[4]++
                            use fwrite to write the hash to the file
                   */

               case ':$hashfile':
                   if($this->is_banned() == true) { // if the user is banned
                       break;
                   }
                   if($this->is_admin() xor $this->is_mod() != true) { // if the user is not a mod or admin
                       $this->send_message("You do not have access to this command, sorry...");
                   }
                   else { // if the user is a mod or admin
                       $md5file = fopen("md5hashes.txt", "a+"); // opening our md5hash file
                       for($i=4; $i <= (count($this->ex)); $i++) { // grabbing our string
                           $word = rtrim($this->ex[$i]); // removing whitespace from string
                           if($i == 14) {
                               $this->send_message("You can only insert 10 hashes at a time, only 10 hashes entered!");
                               break;
                           }
                           fwrite($md5file, $word."\n"); // writing the word to our file
                       }
                   $this->send_message("Done writing hashes to file...");
                   }
                   break;

            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                                $bdmd5
                         Check a lookup table for the given md5 hash value and echo's the value
                            grab the hash to lookup with ex[4]
                            use grep to search the file for the hash value
                            return the value of grep into tempt.txt
                            echo the values of tempt.txt into IRC if the length is >= 1
                            remove the tempt.txt file using a exec command
                   */

                case ':$md5':
                    if($this->is_banned() == true) { // if the user is banned
                        break;
                    }
                    $hash = trim($this->ex[4]); // grabbing the hash
                    $this->send_message("Searching the lookup table for: ".$hash); // let them know we are about to do the lookup
                    $file = fopen("tempt.txt", "a+"); // opening temp file for found hashes
                    $start = substr($hash, 0, 3); // grabbing first character of the hash
                    $md5_file = "dic/".$start.".txt"; // the file is the first character of the hash .txt (i.e, a.txt)
                    exec("grep -m1 '$hash' $md5_file >> tempt.txt"); // getting values from the file and storing them into tempt
                    $count = 0; // setting our count initializer
                    while(!feof($file)) { // while not at the end of our tempt file
                        $word = fgets($file); // grab the word on the current line
                        $word = substr($word, 33); // remove the hash and colon from the tempt file
                        if(strlen($word) >= 1) { // if the word is greater than or equal to 1 in length
                            $this->send_message("The value of the hash is: ".$word."\n"); // echo the value for the hash
                        }
                        $count++; // add to our count
                    }
                    if($count <= 1) { // if the count is less than or equal to 1
                        $this->send_message("Done looking up hashes... If a word was not displayed, it was not found...");
                    }
                    exec("rm tempt.txt"); // remove the tempt file
                break;

            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                                $word
                            Check the dictionary for a word and add it if != exist
                                use grep to search for an exact match of the hash
                                store the output of grep into tempt.txt
                                use a while loop to check if tempt.txt line count is >= 2
                                if it is, the hash exists, so echo it's already in the file
                                if it is not, the hash !exist, so convert the word into $hash:$word
                                write the word to the file using fwrite
                   */

			  /*case ':$word':
			     if($this->is_banned() == true) {
                    break;
                 }
				$word = $this->get_message();
				$this->send_message('Please wait, while I check the dictionary for: ' . $word);
				if(preg_match("/pony/", $word)) {
				    $this->send_message('p0nieZ are evil, and so are you!');
				}
				  elseif(strlen($word) > 20) {
				    $this->send_message('The word can not be over 20 characters in length!');
				  }
				    else {
				        $word = rtrim($word);
                        if(substr($word, -1) != "$") {
                            exec("grep -E ^.................................'$word'$ newrockyou.txt >> tempt.txt"); // if last letter != $
                        }
                        if(substr($word, -1) == "$") {
                            exec("grep ^.................................'$word'$ newrockyou.txt >> tempt.txt"); // last letter is $
                        }
                        $filename = fopen("/home/ninjex/bot/tempt.txt", "a+");
                        $count = 0;
                        while(!feof($filename)) {
                            $line = rtrim(fgets($filename));
                            $count++;
                        }
                        if($count <= 1) {
                            $hash = md5($word);
                            $dictionary = fopen("/home/ninjex/bot/newrockyou.txt", "a+");
                            fwrite($dictionary, "$hash:$word\n");
                            fclose($dictionary);
                            $this->send_message("Successfully added the word to the dictionary!");
                            exec("rm tempt.txt");
                        }
                        else {
                            $this->send_message("The word already exists!");
                            exec("rm tempt.txt");
                        }
                        fclose($filename);
                    }
				break;*/

            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                        $math
                            The bot will do math calculations
                                grab first number ex[4]
                                grab operator ex[5]
                                grab second number ex[6]
                                send to math function
                   */

			  case ':$math':
                  if($this->is_banned() == true) { // if the user is banned
                      break;
                  }
                  $input = rtrim($this->get_message()); // grabbing the user input
                  $input = preg_replace('/[^0-9+*%.\/-]/', '', $input);
                  $sum = $this->do_math($input); // store the return of our input passed through the do_math function into $sum
                  $this->send_message("The value is: ".$sum); // echo the value*/
				  break;

            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                        $command
                              Displays all of the commands the bot hash
                   */

			  case ':$commands':
                  if($this->is_banned() == true) { // if the user is banned
                      break;
                  }
                  $this->send_message('* is an indicator for mod/owner commands only'); // command reference
				  $this->send_message('$join*, $gtfo*, $say, $rand, $eunix, $dunix, $emd5, $dmd5, $word, $math, $help command'); // show commands
				  break;

            //-----------------------------------------------------------------------------------------------------------------------

                  /*
                                        $help
                            Displays additional information about a command
                   */

			  case ':$help':
                  if($this->is_banned() == true) { // if the user is banned
                      break;
                  }
                  $option = $this->ex[4]; // grab the command the user needs more details on
                  $this->help_options($option); // pass the command into our help_options function
		          break;

              //-----------------------------------------------------------------------------------------------------

        }
                        }
            $this->main($config);
            }
          }

//--------------------------------------------*********************------------------------------------------------------\\
//---------------------------------------.....______________________.....------------------------------------------------\\
//---------------------------------------     INITIALIZING FUNCTIONS     ------------------------------------------------\\
//---------------------------------------.....______________________.....------------------------------------------------\\
//--------------------------------------------**********************-----------------------------------------------------\\


        function decrypt_md5($hash) {
            $hash = rtrim($hash); // remove whitespace from the word to lookup
            $start = substr($hash, 0, 3); // grabbing first character of the hash
            $md5_file = "dic/".$start.".txt"; // the file is the first character of the hash in bigdic
                exec("grep -m1 '$hash' $md5_file >> tempt.txt"); // grep the word and store it in tempt.txt
            $file = fopen("/home/ninjex/bot/tempt.txt", "a+"); // opening tempt.txt
            while(!feof($file)) { // while not at the end of tempt.txt
                $word = fgets($file); // grab the word shold be similar to (hash:value)
                $word = substr($word, 33); // remove the hash and colon from the word (left with plain text password)
                if($hash == "d41d8cd98f00b204e9800998ecf8427e") {
                    break;
                }
                if(strlen($word) >= 1) { // if the word is not null such as a carriage return
                    $this->who_is();
                    $person = $GLOBALS['user'];
                    $this->send_data('NOTICE '.$person." :The value for hash: ".$hash." is: ".$word."\n");
                }
            }
            exec("rm tempt.txt");
        }

//-------------------------------------------------------------------------------------------------------------------

        function attack_user($user) {
            // invalid accounts retaliate
            // loot still gets searched on invalid account attacks

            $checkifuserexists = mysql_query("SELECT name FROM players WHERE name='".$user."'");
            $row = mysql_fetch_array($checkifuserexists);
            if($row['name'] != $user) {
                $this->send_message("The user you are trying to attack is an invalid account!");
                break; // may help stop invalid account from retliation
            }
            $this->who_is();
            $attacker = $GLOBALS['user'];
            $user = mysql_real_escape_string($user);
            $damage = rand(1,10);
            $this->con_mysql();
            $query = mysql_query("SELECT health FROM players WHERE name='".$user."'"); // Grab the victims health
            $que = mysql_query("SELECT health FROM players WHERE name='".$attacker."'"); // Grab the attackers health
            $damageb = rand(1,10);

            while($row = mysql_fetch_array($query)) {
                $oldHealth = $row['health']; // Storing their current health in $oldHealth
                if($row['health'] <= 0) { // Seeing if the user is already dead
                    $this->send_message("What is wrong with you? You are trying to attack the dead!"); // error if dead
                }
                else { // if not dead
                    $newHealth = $oldHealth-$damage; // subtract the damage from their health
                    if($newHealth <= 0) { // if the user's new health is <= 0
                        $this->send_message($attacker." has killed ".$user." congratulations, you gained a level and exp point!"); // killed the user and leveled up
                        $getexp = mysql_query("SELECT exp FROM players WHERE name='".$attacker."'"); // query to grab current exp points
                        $getlevel = mysql_query("SELECT level FROM players WHERE name='".$attacker."'"); // query to grab current level
                        while($updatelevel = mysql_fetch_array($getlevel)) { // running through the rows as $updatelevel
                            $level = $updatelevel['level']+1; // updating the level
                        }
                        while($row = mysql_fetch_array($getexp)) { // running through the rows as $getexp
                            $exp = $row['exp']+1; // updating the exp
                        }
                        mysql_query("UPDATE players SET exp='".$exp."' WHERE name='".$attacker."'"); // updating the exp
                        mysql_query("UPDATE players SET level='".$level."' WHERE name='".$attacker."'"); // updating the level
                        $newHealth = 0; // setting health to zero, in case it's below
                    }
                    mysql_query("UPDATE players SET health='".$newHealth."' WHERE name='".$user."'"); // updating the health
                    $this->send_message($user." was attacked for ".$damage." damage!"); // show the damage
                    $this->send_data('PRIVMSG '.$user . ' :You have been attacked for '.$damage." damage and have ".$this->get_health($user)." health remaining!"); // tell the user
                }
            }

            while($row = mysql_fetch_array($que)) {
                $oldHealth = $row['health']; // Storing their current health in $oldHealth
                if($row['health'] <= 0) { // Seeing if the user is already dead
                    $this->send_message("What is wrong with you? You are trying to attack the dead!"); // error if dead
                }
                else { // if not dead
                    $newHealth = $oldHealth-$damageb; // subtract the damage from their health
                    if($newHealth <= 0) { // if the user's new health is <= 0
                        $this->send_message($user." has killed ".$attacker." congratulations, you gained a level and exp point!"); // killed the user and leveled up
                        $getexp = mysql_query("SELECT exp FROM players WHERE name='".$user."'"); // query to grab current exp points
                        $getlevel = mysql_query("SELECT level FROM players WHERE name='".$user."'"); // query to grab current level
                        while($updatelevel = mysql_fetch_array($getlevel)) { // running through the rows as $updatelevel
                            $level = $updatelevel['level']+1; // updating the level
                        }
                        while($row = mysql_fetch_array($getexp)) { // running through the rows as $getexp
                            $exp = $row['exp']+1; // updating the exp
                        }
                        mysql_query("UPDATE players SET exp='".$exp."' WHERE name='".$user."'"); // updating the exp
                        mysql_query("UPDATE players SET level='".$level."' WHERE name='".$user."'"); // updating the level
                        $newHealth = 0; // setting health to zero, in case it's below
                    }
                    mysql_query("UPDATE players SET health='".$newHealth."' WHERE name='".$attacker."'"); // updating the health
                    $this->send_message($user." has retaliated for ".$damageb." damage!"); // show the damage
                    $this->send_data('PRIVMSG '.$attacker . ' :'.$user.' has retaliated for '.$damageb." damage! You have ".$this->get_health($attacker)." health remaining!"); // tell the user
                }
            }

        }

//-------------------------------------------------------------------------------------------------------------------


        function check_login() {

            $this->who_is();
            $user = $GLOBALS['user'];
            $query = mysql_query("SELECT logged_in FROM ninja_login WHERE login_name='".$user."'"); // see if user is logged in or not
            while($row = mysql_fetch_array($query)) {
                if($row['logged_in'] == "True") {
                    return true;
                }
                else {
                    return false;
                }
            }
        }

//-------------------------------------------------------------------------------------------------------------------

        function get_health($user) {
            $this->con_mysql();
            $query = mysql_query("SELECT health FROM players WHERE name='".$user."'");
            while($row = mysql_fetch_array($query)) {
                if($row['health'] <= 0) {
                    $this->send_message($user." has been killed!");
                    return "dead";
                }
                else {
                    return $row['health'];
                }
            }
        }

//-------------------------------------------------------------------------------------------------------------------
        function con_mysql() {
            mysql_connect('server usually localhost', 'username maybe root?', 'password to username') or die(mysql_error());
            mysql_select_db("ninja") or die(mysql_error);

        }
//-------------------------------------------------------------------------------------------------------------------

         /* function template() {
         //
        }*/

//-------------------------------------------------------------------------------------------------------------------

        function send_data($cmd, $msg = null) {
                if($msg == null) { // if the message is null
                    fputs($this->socket, $cmd."\r\n"); // pass the command through the socket
                    echo $cmd;
                }
                  else { // if the message is not null
                        fputs($this->socket, $cmd.' '.$msg."\r\n"); // pass the command and message through the socket
                        echo $cmd.' '.$msg;
                  }
        }

//-------------------------------------------------------------------------------------------------------------------

        function get_message() {
            $input = NULL;
            for($i=4; $i <= (count($this->ex)); $i++) { // grabbing the message
                $input .= $this->ex[$i]." "; // storing the message in input
            }
            if(preg_match("/pony/", $input)) { // if pony is found
                $input = "p0nieZ are evil, and so are you";
            }
            return trim($input); // return our message
        }

//-------------------------------------------------------------------------------------------------------------------

        function send_message($x) {
            $chan = $this->ex[2]; // grabbing the channel name
            $message = $this->get_message($x); // storing the return of get_message in $message
            for($i=3; $i <= (count($this->ex)); $i++) { // grabbing  new message starting from ex[3] (shows the command)
                $message_two .= $this->ex[$i]." "; // storing the new message in mesage_two
            }
            $message_two = trim(substr($message_two, 1)); // removing the colon from message_two

            if($chan == "NinjX") { // if the channel the message is being sent to is the bot
               $this->who_is(); // get the details of the current user
               $this->send_data('PRIVMSG Ninjex :Private Message detected from: ' . $GLOBALS['fullUser'] . " the message is: " . $message_two); // let admin know someone is in pm with the bot
               exec("echo Private message detected from: $GLOBALS[fullUser] the message is: '$message_two' >> bot.log"); // log the pm in bot.log
               return $this->send_data('PRIVMSG '. $GLOBALS['user'] . ' :> ' . $x); // return the message to the user's name instead of the bot's name
            }

            if(preg_match("/pony/", $message) || preg_match("/pony/", $x)) { // if the message contains pony
                return $this->send_data('PRIVMSG ' . $chan . ' :> p0niez are evil, and so are you!');
            }
            else { // if the message does not contain pony
                $this->who_is();
               // $this->send_data("PRIVMSG Ninjex :> Command initiated by: " . $GLOBALS['fullUser'] . " the command is: " . $message_two); // let the admin know someone is commanding the bot
                exec("echo Command initiated by: $GLOBALS[fullUser] the command is: '$message_two' >> bot.log"); // log the command in bot.log
                return $this->send_data('PRIVMSG ' . $chan . ' :> ' . $x); // return the message to the channel
            }
        }

//-------------------------------------------------------------------------------------------------------------------

        function join_channel($channel) {
          if(is_array($channel)) { // if we have multiple channels, store it in a array
            foreach($channel as $chan) { // for each channel
              $this->send_data('JOIN', $chan);
            }
          }
            else { // if we have one channel to join
              $this->send_data('JOIN', $channel);
            }
        }

//-------------------------------------------------------------------------------------------------------------------

    function who_is() { // Getting username and fullname from current user
      $who = $this->ex[0]; // storing full user in $who
	  $GLOBALS['fullUser'] = $who; // setting fullUser to the full username of the user
	  $who = explode("!",$who); // removing everything up to ! and storing them into $who
	  $user = $who['0']; // $user equals the first half of the explode (ie. explode("!", "this!is") would = this
	  $GLOBALS['user'] = substr($user, 1); // setting the global variable user equal to the first half of the explode minus the colon
	}

//-------------------------------------------------------------------------------------------------------------------

    function is_admin() { // checking if user is admin
        $user = $this->ex[0]; // user = current user's full name
        if($user != $GLOBALS['owner']) { // if the user does not equal the owner variable
            return false;
        }
          else { // if the user does equal the owner variable
              return true;
          }
    }

//-------------------------------------------------------------------------------------------------------------------

    function is_mod() { //
        $user = $this->ex[0]; // grabbing the current user's full name
        $modfile = fopen("mods.txt", "r"); // opening the mod file
        $modinfile = NULL; // initializing a variable to determine if the while loop should break
        while(!feof($modfile) && $modinfile != $user) { // while not at the end of file and no mods in the file are = to the user
            $modinfile = trim(fgets($modfile)); // the mod in the file = the mod on the current line
        }
        if($modinfile == $user) { // if there was a match
            return true;
        }
    }

//-------------------------------------------------------------------------------------------------------------------

    function check_mod($user) { // Check if the user is a mod
        $modfile = fopen("mods.txt", "r"); // opening the mod file
        $modinfile = NULL; // initializing a variable to determine if the while loop should break
        while(!feof($modfile) && $modinfile != $user) { // while not at the end of file and no mods in the file are = to the user
            $modinfile = rtrim(fgets($modfile)); // the mod in the file = the mod on the current line
        }
        if($modinfile == $user) { // if there was a match
            return true;
        }
        else { // if there was not  match
            return false;
        }
    }

//-------------------------------------------------------------------------------------------------------------------

    function make_mod() { // add a user to the mod list
            $user = $this->ex[4]; // grabbing username
            $modfile = fopen("mods.txt", "a+"); // opening the mod file
            fwrite($modfile, $user."\n"); // writing the user to the file
        }
//-------------------------------------------------------------------------------------------------------------------

    function is_banned() {
/*
        $value = "10-20-30-40-50-87";
        $pieces = explode("-", $value);
        echo $pieces[0]; // 10
        echo $pieces[1]; // 20
 */
        $this->who_is(); // grabbing the user's details
        $person = rtrim($GLOBALS['fullUser']); // $person = the user's full name
        $splitperson = explode("@", $person); // splitting $person at the @ - splitperson[1] == host value
        $banfile = fopen("baned_users.txt", "a+"); // opening the baned_users file
        $baneduser = NULL; // setting our initializer to break the while loop if true
        while(!feof($banfile) && trim($splitperson[1]) != $baneduser)  { // while not at the end of the file, and the host name is not found in the baned file
            $baneduser = trim(fgets($banfile)); // the baned user = the current host on the baned file
        }
        if(trim($splitperson[1]) == $baneduser) { // if a host in the baned file belongs to the host of the user
            $this->send_message("Sorry, I don't listen to idiots.");
            $this->send_data('PRIVMSG Ninjex :A banned user [' . $GLOBALS['user'] . '] aka [' . $GLOBALS['fullUser'] . '] attempted to use the bot!'); // tell the admin a baned user attempted to use the bot
            exec("echo A banned user [$GLOBALS[user]] aka [$GLOBALS[fullUser]] attempted to use the bot! >> bot.log"); // log the attempt of using the bot in bot.log
            return true;
            }

    }

//-------------------------------------------------------------------------------------------------------------------

    function check_ban() {
        if($this->is_banned() == true) { // if the user being banned = true
            $this->who_is(); // grab the details of the user
            $this->send_message("Sorry, I don't listen to idiots."); // give error
            $this->send_data('PRIVMSG Ninjex :A banned user [' . $GLOBALS['user'] . '] aka [' . $GLOBALS['fullUser'] . '] attempted to use the bot'); // let the admin know a baned user attempted to use the bot
            return true;
        }
    }

//-------------------------------------------------------------------------------------------------------------------

    function ban_user() {
        $user = $this->ex[4]; // grabbing specified user
        $banfile = fopen("baned_users.txt", "a+"); // opening the ban file
        fwrite($banfile, $user."\n"); // write the specified user to the ban file
    }

//-------------------------------------------------------------------------------------------------------------------

    function random_num($x, $y) {
        $sum = rand($x,$y); // generating a random number with the given inputs
        $x = $sum;
        $this->send_message($x); // send the sum
    }

//-------------------------------------------------------------------------------------------------------------------

    function do_math($input) {
        $result=eval("return ($input);"); // using eval to preform math on the specified input
        return $result; // return the sum
    }

//-------------------------------------------------------------------------------------------------------------------

    function help_options($option) {
        switch($option) {
          //---
            case 'join':
                $this->send_message('Description: Forces NinjX to join the specified channel name.');
                $this->send_message('Syntax: $join #channelName');
                $this->send_message('Example: $join #hackthissite');
                break;
          //---
            case 'say':
                $this->send_message('Description: Forces NinjX to repeat said text.');
                $this->send_message('Syntax: $say anything');
                $this->send_message('Example: $say Hello, my name is NinjX!');
                break;
          //---
            case 'gtfo':
                $this->send_message('Description: Forces NinjX to quit.');
                $this->send_message('Syntax: $gtfo');
                break;
          //---
            case 'rand':
                $this->send_message('Description: NinjX will display a random number between two given digits.');
                $this->send_message('Syntax: $rand Number1 Number2');
                $this->send_message('Example: $rand 100 150');
                break;
          //---
            case 'eunix':
                $this->send_message('Description: Encrypts said string using Unix crypt with the salt (2 chars) value of your choice');
                $this->send_message('Syntax: $eunix salt string');
                $this->send_message('Example: $eunix 0x MySuperSecretPassword');
                break;
          //---
            case 'emd5':
                $this->send_message('Description: NinjX will convert the given text into md5 format.');
                $this->send_message('Syntax: $emd5 text');
                $this->send_message('Example: $emd5 myStrongPassword');
                break;
          //---
            case 'dmd5':
                $this->send_message('Description: NinjX will run a dictionary attack on said md5 hash.');
                $this->send_message('Syntax: $dmd5 md5Hash');
                $this->send_message('Example: $dmd5 5f4dcc3b5aa765d61d8327deb882cf99');
                break;
          //---
            case 'word':
                $this->send_message('Description: NinjX will add the said string to the dictionary for dictionary attacks.');
                $this->send_message('Syntax: $word text');
                $this->send_message('Example: $word BiiG->B4ngTh3[0]Ry');
                break;
          //---
            case 'help':
                $this->send_message('Description: Shows you how to use the commands with correct syntax.');
                $this->send_message('Syntax: $help commandName');
                $this->send_message('Example $help say');
                break;
          //---
            case 'math':
                $this->send_message('Description: NinjX will do math with two numbers (only 1 process at a time and must of spaces)');
                $this->send_message('Syntax: $math number1 operator number2');
                $this->send_message('Example: $math 20 * 40');
                break;
          //---
            default:
                $this->send_message('The command you specified was not found, check your syntax.');
                $this->send_message('It should look similar to:');
                $this->send_message('$help emd5 (You may also use $commands to see the list of commands)');
                break;

            /*case '':
            // storing empty help option for easy copy pasta for future commands
                $this->send_message('');
                $this->send_message('');
                $this->send_message('');
                break;
            */

        }
    }

//-------------------------------------------------------------------------------------------------------------------


      } // end of class IRCBot
$bot = new IRCBot($config);
?>
