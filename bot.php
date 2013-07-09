<?php
set_time_limit(0);
ini_set('display_errors', 'on');
$config = array(
        'server' => 'ssl://irc.hackthissite.org', // server, install ssl, use ssl://irc.hackthissite.org (port 7000)
        'port'   => 7000, // port numbers regular = 6667, ssl = 6697, 7000
        'channel' => '#coffeesh0p',
        'name'   => 'NinjX', // name
        'nick'   => 'NinjX',  // nick
        'pass'   => '', // password
);


// Plan on breaking out of the use of these global variables soon

$owner = ":Ninjex!ninjex@HTS-C0484C46.lightspeed.nsvltn.sbcglobal.net";
$user = NULL;
$fullUser = NULL;
$message = NULL;
$filesize = NULL;
$playertotal = NULL;
$filter = NULL;
$host = NULL;

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
                            $this->join_channel('#bots');
                            $this->send_data('MODE NinjX +B');
                            $this->state++;
                        }

              //***************************************************************************************************
              //***************************************************************************************************
/*if($this->ex[0] == ':flurbbot!flurbbot@HTS-DE1BB303.hsd1.ma.comcast.net') {
    $this->send_message('Fuck you flurberty');
}*/ // cursing flurbot

                        $input = NULL;
                        for($i=1; $i </*=*/ (count($this->ex)); $i++) {
                            $input .= $this->ex[$i]." ";
                        }
                        $input = rtrim($input);
                        if(preg_match('#hackthissite.org/forums/viewtopic.php?[^\s]*f=([0-9a-zA-Z_\-&=]*)#i',$input,$matches)) {
                            $site = "http://www.".$matches[0]."&start=0";
                            $sitedata = $this->get_data($site);
                            $titlestart = '<title>';
                            $titleend = '</title>';
                            $explode_title = explode($titlestart, $sitedata);
                            $explode_titleb = explode($titleend, $explode_title[1]);
                            $title = $explode_titleb[0];
                            $title = substr($title, 41);

                            $opstart = 'by <strong><a href="';
                            $opend = '</a>';
                            $op_explode = explode($opstart, $sitedata);
                            $op_explodeb = explode($opend, $op_explode[1]);
                            $newsitedata = $op_explodeb[0];
                            $newend = '">';
                            $newexplode = explode($newend, $newsitedata);
                            $poster = $newexplode[1];

                            $this->send_message("Hackthissite Forum Detected, getting details!");
                            if($this->filter_text($poster) xor $this->filter_text($title) == true) {
                                $this->send_message("Sorry, that forum has information which is indicated as a gline or blacklist word, stopping for my convenience!");
                            }
                            else {
                                $this->send_message("Original Poster: ".$poster.", Topic Title: ".$title);
                            }
                        }
                        /*for($i=1; $i <= (count($this->ex)); $i++) { // youtube video finder disabled since wallbot will do this
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

                        $this->morselist = array(
                            'a'  =>  '.-', 'b'  =>  '-...', 'c'  =>  '-.-.', 'd'  =>  '-..', 'e'  =>  '.',
                            'f'  =>  '..-.', 'g'  =>  '--.', 'h'  =>  '....', 'i'  =>  '..', 'j'  =>  '.---',
                            'k'  =>  '-.-', 'l'  =>  '.-..', 'm'  =>  '--', 'n'  =>  '-.', 'o'  =>  '---',
                            'p'  =>  '.--.', 'q'  =>  '--.-', 'r'  =>  '.-.', 's'  =>  '...', 't'  =>  '-',
                            'u'  =>  '..-', 'v'  =>  '...-', 'w'  =>  '.--', 'x'  =>  '-..-', 'y'  =>  '-.--',
                            'z'  =>  '--..', '0'  =>  '-----', '1'  =>  '.----', '2'  =>  '..---', '3'  =>  '...--',
                            '4'  =>  '....-', '5'  =>  '.....', '6'  =>  '-....', '7'  =>  '--...', '8'  =>  '---..',
                            '9'  =>  '----.', '.'  =>  '.-.-.-', ','  =>  '--..--', '?'  =>  '..--..', '\''  =>  '.----.',
                            '!'  =>  '-.-.--', '/'  =>  '-..-.', '-'  =>  '-....-', '"'  =>  '.-..-.', '('  =>  '-.--.-',
                            ')'  =>  '-.--.-', ' '  =>  '/',
                        );

                        $this->filterlist = array(
                            'mIRC_Exploit'      =>   '#\x01DCC (SEND|RESUME)[ ]+\"(.+ ){20}#',
                            'mIRC_Exploit2'     =>   '#\x01DCC (SEND|RESUME).{225}#',
                            'Fyle_Trojan'       =>   '#Come watch me on my webcam and chat /w me :-\) http://.+:\d+/me\.mpg#',
                            'Mirseed_Trojan'    =>   '#Speed up your mIRC DCC Transfer by up to 75%.*www\.freewebs\.com/mircupdate/mircspeedup\.exe#',
                            'Fagot_Worm'        =>   '#^http://www\.angelfire\.com/[a-z0-9]+/[a-z0-9]+/[a-z_]+\.jpg <- .*!#',
                            'Aplore_Worm'       =>   '#^FREE PORN: http://free:porn@([0-9]{1,3}\.){3}[0-9]{1,3}:8180$#',
                            'Gbot_Login'        =>   '#^!login Wasszup!$#',
                            'Gbot_Login2'       =>   '#^!login grrrr yeah baby!$#',
                            'Gbot_Use'          =>   '#^!packet ([0-9]{1,3}\.){3}[0-9]{1,3} [0-9]{1,15}#',
                            'Gbot_Use2'         =>   '#^!icqpagebomb ([0-9]{1,15} ){2}.+#',
                            'Gbot_Use3'         =>   '#^!pfast [0-9]{1,15} ([0-9]{1,3}\.){3}[0-9]{1,3} [0-9]{1,5}$#',
                            'Gbot_Use4'         =>   '#^!portscan ([0-9]{1,3}\.){3}[0-9]{1,3} [0-9]{1,5} [0-9]{1,5}$#',
                            'SDBot_Use'         =>   '#^.u(dp)? ([0-9]{1,3}\.){3}[0-9]{1,3} [0-9]{1,15} [0-9]{1,15} [0-9]{1,15}( [0-9])*$#',
                            'SpyBot_Use'        =>   '#^.syn ((([0-9]{1,3}\.){3}[0-9]{1,3})|([a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_.-]+)) [0-9]{1,5} [0-9]{1,15} [0-9]{1,15}#',
                            'Soex_Trojan'       =>   '#^porn! porno! http://.+\/sexo\.exe#',
                            'Erotica_Trojan'    =>   '#(^wait a minute plz\. i am updating my site|.*my erotic video).*http://.+/erotic(a)?/myvideo\.exe$#',
                            'Nkie_Worm'         =>   '#^STOP SPAM, USE THIS COMMAND: //write nospam \$decode\(.+\) \| \.load -rs nospam \| //mode \$me \+R$#',
                            'Nkie_Worm2'        =>   '#^FOR MATRIX 2 DOWNLOAD, USE THIS COMMAND: //write Matrix2 \$decode\(.+=,m\) \| \.load -rs Matrix2 \| //mode \$me \+R$#',
                            'Nkie_Worm3'        =>   '#^hey .* to get OPs use this hack in the chan but SHH! //\$decode\(.*,m\) \| \$decode\(.*,m\)$#',
                            'LOI_Trojan'        =>   '#.*(http://jokes\.clubdepeche\.com|http://horny\.69sexy\.net|http://private\.a123sdsdssddddgfg\.com).*#',
                            'Gaggle_Worm'       =>   '#C:\\\\WINNT\\\\system32\\\\[][0-9a-z_-{|}`]+\.zip#',
                            'Gaggle_Worm2'      =>   '#C:\\\\WINNT\\\\system32\\\\(notes|videos|xxx|ManualSeduccion|postal|hechizos|images|sex|avril)\.zip#',
                            'Gaggle_Worm3'      =>   '#http://.+\.lycos\..+/[iy]server[0-9]/[a-z]{4,11}\.(gif|jpg|avi|txt)#',
                            'Virus_Backdoor'    =>   '#^Free porn pic.? and movies (www\.sexymovies\.da\.ru|www\.girlporn\.org)#',
                            'Decode_Exploit'    =>   '#^LOL! //echo -a \$\(\$decode\(.+,m\),[0-9]\)$#',
                            'Decode_Exploit2'   =>   '#//write \$decode\(.+\|.+load -rs#',
                            'mIRC_Trojan'       =>   '#^Want To Be An IRCOp\? Try This New Bug Type: //write \$decode\(.+=.?,m\) \| \.load -rs \$decode\(.+=.?,m\)$#',
                            'Adult_Spam'        =>   '#^Check this out.*http://www\.pornzapp\.com.*#',
                            'Blacklist_Word'    =>  '#pony#i',
                            'Blacklist_Word2'   =>  '#nigger#i'
                        );

//             if($this->is_banned() != true) {
                        $user_query_name = $this->ex[0]; // :flurbbot!flurbbot@HTS-DE1BB303.hsd1.ma.comcast.net
                        $user_array = explode('@', $user_query_name);
                        $user_host = $user_array[1];
                  if($this->check_ban($user_host) != true) {
                      switch($command) { // list of commands the bot will respond to from users
                          case ':$test':
                              $host = $this->ex[4];
                              $this->send_message("Debugging, the host being used does it match?... : ".$host);
                              if($this->check_ban($host) == true) {

                                  $this->send_message("The host is banned...");
                              }
                              else {
                                  $this->send_message("The host is not banned...");
                              }
                              break;

                          //----------------------------------------------------------------------------------------------------------------

                          case ':$morse':
                              $string = strtolower($this->get_message());
                              $len = strlen($string);
                              $final = NULL;
                              for($pos = 0; $pos < $len; $pos++) {
                                  $care = $string[$pos];
                                  if(array_key_exists($care, $this->morselist)) {
                                      $final .= $this->morselist[$care]." ";
                                  }
                              }
                              $this->send_message($string." converted to morse is: ".rtrim($final));
                              break;

                          //----------------------------------------------------------------------------------------------------------------

                          case ':$tell':
                              /* if($this->is_admin() != true) {
                                   $this->send_message("Blocked while testing security...");
                               }*/
                              $user = $this->ex[4];
                              if($user == 'NinjX') {
                                  $this->send_message("Why would one speak to thyself?");
                                  break;
                              }
                              $input = NULL;
                              for($i=5; $i <= (count($this->ex)); $i++) { // grabbing the message
                                  $input .= $this->ex[$i]." "; // storing the message in input
                              }
                              $message = rtrim($input);
                              if($this->filter_text($message) == true) {
                                  $this->insta_ban();
                                  break;
                              }
                              $this->send_data('PRIVMSG '.$user." :> ".$message);
                              break;

                          //------------------------------------------------------------------------------------------------------------------

                          case':$forum':
                              $this->who_is();
                              $user = $GLOBALS['user'];
                              $site = "https://www.hackthissite.org/forums/search.php?search_id=active_topics";
                              $pick = rtrim($this->ex[4]);
                              $addition = $this->ex[5];
                              if($pick >= 3 || $pick <= 0) {
                                  $this->send_message("Right now, I only search topics 1 and 2, please try again!");
                                  break;
                              }
                              $data = $this->get_data($site);
                              $lasttopicstart = '<li class="row bg'.$pick.'">';
                              $lasttopicend = '</li>';
                              $lasttopicexplode = explode($lasttopicstart, $data);
                              $lasttopicexplodeb = explode($lasttopicend, $lasttopicexplode[1]);

                              $newdata = $lasttopicexplodeb[0];
                              $titlestart = 'class="topictitle">';
                              $titleend = '  </a>';
                              $titleexplode = explode($titlestart, $newdata);
                              $titleexplodeb = explode($titleend, $titleexplode[1]);
                              $title = $titleexplodeb[0];

                              $urlstart = '<a href="';
                              $urlend = '"';
                              $urlexplode = explode($urlstart, $newdata);
                              $urlexplodeb = explode($urlend, $urlexplode[1]);
                              $url = substr($urlexplodeb[0], 1);
                              $url = htmlspecialchars_decode($url);
                              $urlb = explode('&sid', $url);
                              $urlc = "https://www.hackthissite.org/forums".$urlb[0];

                              $bystart = 'by <a href="';
                              $byend = '</a>';
                              $byextra = '">';
                              $byexplode = explode($bystart, $newdata);
                              $byexplodeb = explode($byend, $byexplode[1]);
                              $bytemp = $byexplodeb[0];
                              $by = explode($byextra, $bytemp);
                              $byb = $by[1];

                              $lastpoststart = '<dd class="lastpost"><span>';
                              $lastpostend = '</a>';
                              $lastpostexplode = explode($lastpoststart, $newdata);
                              $lastpostexplodeb = explode($lastpostend, $lastpostexplode[1]);
                              $lasttemp = $lastpostexplodeb[0];
                              $last = explode($byextra, $lasttemp);
                              $lastb = $last[1];

                              if($this->filter_text($title) xor $this->filter_text($urlb) xor $this->filter_text($byb) xor $this->filter_text($lastb) == true) {
                                  $this->send_message("Some of the content gathered had glined or blacklisted words/phrases, breaking for my safety!");
                                  break;
                              }
                              else {
                                  $this->send_message("The information gathered has been sent to your pm feed to mitigate spam!");
                                  $this->send_data('PRIVMSG '.$user." :Forum Topic: ".$title);
                                  $this->send_data('PRIVMSG '.$user.' :URL: '.$urlc);
                                  $this->send_data('PRIVMSG '.$user." :Original Poster: ".$byb);
                                  $this->send_data('PRIVMSG '.$user." :Last Post By: ".$lastb);
                              }

                              if($addition == 'describe') {
                                  $describestart = '<div class="content">'; // start of search
                                  $describeend = '</div>';                  // end of search
                                  $describedata = $this->get_data($urlc);   // grabbing data
                                  $describeexplode = explode($describestart, $describedata);
                                  $describeexplodeb = explode($describeend, $describeexplode[1]);
                                  $description = $describeexplodeb[0];
                                  if($this->filter_text($description) == true) {
                                      $this->send_message("The description contains glined or banned phrases/words, breaking for my safety!");
                                  }
                                  else {
                                      $pattern = '/<br \/>/i';
                                      $replacement = ' ';
                                      $this->send_data('PRIVMSG '.$user.' :Forum Description: '.preg_replace($pattern, $replacement, htmlspecialchars_decode($description)));
                                  }
                              }
                              break;

                          //----------------------------------------------------------------------------------------------------------------

                          case':$htsuser':
                              // need to break from the \r\n appended to the beginning.
                              $this->send_message("All information will be sent to your news feed to mitigate spam!");
                              $this->who_is();
                              $person = $GLOBALS['user'];
                              $user =        $this->ex[4];
                              $site =        'https://www.hackthissite.org/api/'.$user;
                              $data =        file_get_contents($site);
                              $value =       explode(":", $data);
                              $points =      trim($value[1]);
                              $basic =       trim($value[2]);
                              $realistic =   trim($value[3]);
                              $application = trim($value[4]);
                              $programming = trim($value[5]);
                              $javascript =  trim($value[6]);
                              $irc =         trim($value[7]);
                              $extbasic =    trim($value[8]);
                              $stego =       trim($value[9]);

                              $this->send_data('PRIVMSG '.$person.' :Points: '.$points);
                              $this->send_data('PRIVMSG '.$person.' :Basic: '.$basic);
                              $this->send_data('PRIVMSG '.$person.' :Realistic: '.$realistic);
                              $this->send_data('PRIVMSG '.$person.' :Application: '.$application);
                              $this->send_data('PRIVMSG '.$person.' :Programming: '.$programming);
                              $this->send_data('PRIVMSG '.$person.' :JavaScript: '.$javascript);
                              $this->send_data('PRIVMSG '.$person.' :IRC: '.$irc);
                              $this->send_data('PRIVMSG '.$person.' :Extbasic: '.$extbasic);
                              $this->send_data('PRIVMSG '.$person.' :Stego: '.$stego);

                              break;

                          //----------------------------------------------------------------------------------------------------------------

                          case':$hashit':
                              $word = rtrim($this->ex[5]);
                              $type = rtrim($this->ex[4]);
                              $md5 = md5($word);
                              $md5_2 = md5($md5);
                              $md5_3 = md5($md5_2);
                              $md5_4 = md5($md5_3);
                              $md5_5 = md5($md5_4);
                              $sha1 = hash('sha1', $word);
                              $sha1_2 = hash('sha1', $sha1);
                              $sha1_3 = hash('sha1', $sha1_2);
                              $sha256 = hash('sha256', $word);
                              $sha384 = hash('sha384', $word);
                              $sha512 = hash('sha512', $word);
                              $ripemd160 = hash('ripemd160', $word);
                              $md5_sha1 = md5(sha1($word));
                              $sha1_md5 = sha1(md5($word));
                              switch($type) {
                                  case'md5':
                                      $this->send_message($md5);
                                      break;
                                  //--
                                  case'md52':
                                      $this->send_message($md5_2);
                                      break;
                                  //--
                                  case'md53':
                                      $this->send_message($md5_3);
                                      break;
                                  //--
                                  case'md54':
                                      $this->send_message($md5_4);
                                      break;
                                  //--
                                  case'md55':
                                      $this->send_message($md5_5);
                                      break;
                                  //--
                                  case'sha1':
                                      $this->send_message($sha1);
                                      break;
                                  //--
                                  case'sha12':
                                      $this->send_message($sha1_2);
                                      break;
                                  //--
                                  case'sha13':
                                      $this->send_message($sha1_3);
                                      break;
                                  //--
                                  case'sha256':
                                      $this->send_message($sha256);
                                      break;
                                  //--
                                  case'sha384':
                                      $this->send_message($sha384);
                                      break;
                                  //--
                                  case'sha512':
                                      $this->send_message($sha512);
                                      break;
                                  //--
                                  case'ripe':
                                      $this->send_message($ripemd160);
                                      break;
                                  //--
                                  case'md5sha':
                                      $this->send_message($md5_sha1);
                                      break;
                                  //--
                                  case'shamd5':
                                      $this->send_message($sha1_md5);
                                      break;
                                  //--
                                  case'all':
                                      $this->send_message("Sent hashes to your pm feed in, to mitigate spam!");
                                      $this->who_is();
                                      $person = $GLOBALS['user'];
                                      if($this->filter_text($word) == true) {
                                          $this->insta_ban();
                                          break;
                                      }
                                      $this->send_data('PRIVMSG '.$person." :Hash values for: ".$word);
                                      $this->send_data('PRIVMSG '.$person." :MD5: ".$md5);
                                      $this->send_data('PRIVMSG '.$person." :MD5x2: ".$md5_2);
                                      $this->send_data('PRIVMSG '.$person." :MD5x3: ".$md5_3);
                                      $this->send_data('PRIVMSG '.$person." :MD5x4: ".$md5_4);
                                      $this->send_data('PRIVMSG '.$person." :MD5x5: ".$md5_5);
                                      $this->send_data('PRIVMSG '.$person." :SHA1: ".$sha1);
                                      $this->send_data('PRIVMSG '.$person." :SHA1x2: ".$sha1_2);
                                      $this->send_data('PRIVMSG '.$person." :SHA1x3: ".$sha1_3);
                                      $this->send_data('PRIVMSG '.$person." :SHA256: ".$sha256);
                                      $this->send_data('PRIVMSG '.$person." :SHA384: ".$sha384);
                                      $this->send_data('PRIVMSG '.$person." :SHA512: ".$sha512);
                                      $this->send_data('PRIVMSG '.$person." :RIPEMD160: ".$ripemd160);
                                      $this->send_data('PRIVMSG '.$person." :MD5(SHA1): ".$md5.$md5_sha1);
                                      $this->send_data('PRIVMSG '.$person." :SHA1(MD5): ".$sha1_md5);
                                      break;
                              }
                              break;

                          //-----------------------------------------------------------------------------------------------------------------

                          case ':$len':
                              $word = $this->get_message();
                              $length = strlen($word);
                              $this->send_message("The input is: ".$length." characters in length!");
                              break;

                          //----------------------------------------------------------------------------------------------------------------

                          case ':$b2hex':
                              $word = $this->get_message();
                              $hex = bin2hex($word);
                              $this->send_message($word." converted to hex is: 0x".$hex);
                              break;

                          //-----------------------------------------------------------------------------------------------------------------

                          case ':$h2bin':
                              $binary = rtrim($this->ex[4]);
                              $binary = pack("H*" , $binary);
                              $this->send_message("The value is: ".$binary);
                              break;
                          //-----------------------------------------------------------------------------------------------------------------

                          case ':$youtube':
                              $site = $this->ex[4];
                              $content = file_get_contents($site);

                              $search = '<meta name="twitter:title" content="';
                              $searchb = '<meta name="twitter:description" content="';

                              $pieces = explode($search, $content);
                              $piece = explode ('">', $pieces[1]);

                              $piecesb = explode($searchb, $content);
                              $pieceb = explode('">', $piecesb[1]);

                              $this->send_message("Youtube Title: ".htmlspecialchars_decode($piece[0])); //."   Description: [[".htmlspecialchars_decode($pieceb[0])."]]\n");
                              break;

                          //-------------------------------------------------------------------------------------------------------------------

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
                                          $attacked = $this->attack_user($user); // send attack request
                                          if($attacked == "INVALID") {
                                              $this->send_message("The user you are trying to attack is an invalid account!");
                                              break;
                                          }
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

                          case ':$rmban':
                              $person = $this->ex[4];
                              if($this->is_admin() != true) {
                                  $this->send_message("Only the administrator can use this command!");
                              }
                              else {
                                  if(strlen($person) < 1) {
                                      $this->send_message("You did not specify a user to unban, breaking for your convenience!");
                                      break;
                                  }
                                  else {
                                      exec("sed '/'$person'/d' baned_users.txt >> temp.txt");
                                      exec("mv temp.txt baned_users.txt");
                                      exec("rm temp.txt");
                                      $this->send_message("Successfully removed the ban from the specified host!");
                                  }
                              }
                              break;
                          //-------------------------------------------------------------------------------------------------------------------

                          /*
                                                        $who
                                        Display the user's short name and extended name to them
                           */

                          case ':$who':
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
                              if($this->is_admin() == true) { // if the user is the admin
                                  $this->send_message("Joining the channel...");
                                  $this->join_channel($this->ex[4]);
                              }
                              else { // if the user is not the admin
                                  $this->send_message('Sorry, only Ninjex can use the $join command!');
                              }
                              break;

                          //---------------------------------------------------------------------------------------------------------------------

                          case ':$leave':
                              $channel_to_leave = $this->ex[4];
                              if($this->is_admin() != true) {
                                  $this->send_message("You are not authorized to use this command!");
                                  break;
                              }
                              else {
                                  $this->send_data('PART', $channel_to_leave);
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
                              $salt = $this->ex[4]; // grabbing the salt
                              for($i=5; $i <= (count($this->ex)); $i++) { // grabbing the string to hash
                                  $string .= $this->ex[$i]." "; // still grabbing the string
                              }
                              $string = rtrim($string); // removing trailing whitespace from the string
                              $crypt = crypt($string,$salt); // using crypt function to encrypt our string with the given salt
                              $this->send_message('The encrypted unix value of: ' . $string . ' with salt: ' . substr($salt,0,2) . ' is: ' . $crypt);
                              break;

                          //-----------------------------------------------------------------------------------------------------------------------



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
                              if($this->is_admin() xor $this->is_mod() != true ) { // if the user is not a mod or admin
                                  $this->send_message("You are not authorized to use this command.");
                              }
                              else { // if the user is a mod or admin
                                  $md5file = fopen("md5hashes.txt", "a+"); // opening the md5hash file
                                  for($i=4; $i <= (count($this->ex)); $i++) { // grabbing the string to hash into md5
                                      if($i >= 14) {
                                          $this->send_message("You can only insert 10 words to be hashed and inserted into the file, only 10 hashes entered!");
                                          break;
                                      }
                                      $word = rtrim($this->ex[$i]); // removing trailing whitespace from the word
                                      if($this->filter_text($word) == true) {
                                          $this->insta_ban();
                                          break;
                                      }
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
                              }
                              break;
                          //-----------------------------------------------------------------------------------------------------------------------

                          /*
                                                 $emd5
                                    Convert a string (ex[4]++) into md5
                                Echo the data of the hash back into the IRC channel
                           */

                          case ':$emd5':
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
                              if($this->is_admin() xor $this->is_mod() != true) { // if the user is not a mod or admin
                                  $this->send_message("You do not have access to this command, sorry...");
                              }
                              else { // if the user is a mod or admin
                                  $md5file = fopen("md5hashes.txt", "a+"); // opening our md5hash file
                                  for($i=4; $i <= (count($this->ex)); $i++) { // grabbing our string
                                      $word = rtrim($this->ex[$i]); // removing whitespace from string
                                      if($i >= 14) {
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
                                                        $md5
                                 Check a lookup table for the given md5 hash value and echo's the value
                                    grab the hash to lookup with ex[4]
                                    use grep to search the file for the hash value
                                    return the value of grep into tempt.txt
                                    echo the values of tempt.txt into IRC if the length is >= 1
                                    remove the tempt.txt file using a exec command
                           */

                          case ':$md5':
                              $hash = trim($this->ex[4]); // grabbing the hash
                              if(!preg_match("/[a-fA-F0-9]{32}/", $hash)) {
                                  $this->send_message("The hash was invalid, it should be hex and 32 characters in length.");
                                  break;
                              }
                              $this->send_message("Searching the lookup table for: ".$hash); // let them know we are about to do the lookup
                              $file = fopen("tempt.txt", "a+"); // opening temp file for found hashes
                              $start = substr($hash, 0, 3); // grabbing first 3 characters of the hash
                              $md5_file = "dic/".$start.".txt"; // the file is the first 3 characters of the hash .txt (i.e, ab4.txt)
                              exec("grep -m1 '$hash' $md5_file >> tempt.txt"); // getting values from the file and storing them into tempt
                              $count = 0; // setting our count initializer
                              while(!feof($file)) { // while not at the end of our tempt file
                                  $line = rtrim(fgets($file)); // grab the word on the current line
                                  $word = substr($line, 33); // remove the hash and colon from the tempt file
                                  $piece = explode(":", $line);
                                  $search = $piece[0];
                                  /* if(!preg_match("/$hash/", $line)) {
                                      if(strlen($piece[1] <= 0)) {
                                          break;
                                      }
                                       else {
                                           $this->send_message("The value for hash: ".$hash." was not located!");
                                       }
                                   }*/
                                  //}

                                  if(strlen($word) >= 1) { // if the word is greater than or equal to 1 in length
                                      if($this->filter_text($word) == true) {
                                          $this->send_message("The value of the hash is a glined phrase or a blacklisted word, breaking for my safety!");
                                          break;
                                      }

                                      else {
                                          $this->send_message("The value of the hash is: ".$word."\n"); // echo the value for the hash
                                      }
                                  }
                                  $count++; // add to our count
                              }
                              if($count <= 1) { // if the count is less than or equal to 1
                                  $this->send_message("Done looking up the hash... If a value for: ".$hash." was not displayed, it was not found!");
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
                              $input = rtrim($this->get_message()); // grabbing the user input
                              //$input = preg_replace('/[0-9+*%.\/-(\*\*)]/', '', $input);
                              $input = preg_replace('/([0-9.]+)\*\*([0-9.]+)/', 'pow($1, $2)', $input);
                              $sum = $this->do_math($input);/*
                  $input = rtrim($this->get_message()); // grabbing the user input
                  $input = preg_replace('/[0-9+*%.\/-(\*\*)]/', '', $input);
                  $sum = $this->do_math($input); // store the return of our input passed through the do_math function into $sum*/
                              if($sum == "NULL") {
                                  break;
                              }
                              else {
                                  $this->send_message("The value is: ".$sum); // echo the value*/
                              }
                              break;

                          //-----------------------------------------------------------------------------------------------------------------------

                          /*
                                                $command
                                      Displays all of the commands the bot hash
                           */

                          case ':$commands':
                              $this->send_message('* is an indicator for mod/owner commands only'); // command reference
                              // admin commands
                              // $join*, $gtfo*, $leave*, $ban*, $rmban*, $mod*, $rmod*, $rmtemp*, $rmfile*, $addplayer*, $hashfile*, $emd5file*,
                              // other commands
                              // $say, $rand, $eunix, $emd5, $math, $md5file, $hashit, $help, $who, $joke, $lulz, $ninja, $getplayer, $register, $account, $tell, $htsuser, $forum, $b2hex, $len, $youtube
                              $this->send_message('$join*, $gtfo*, $leave*, $ban*, $rmban*, $mod*, $rmod*, $rmtemp*, $rmfile*, $addplayer*, $hashfile*, $emd5file*, $htsuser, $forum, $b2hex, $len,'); // show commands
                              $this->send_message('$say, $rand, $eunix, $emd5, $md5, $math, $md5file, $hashit, $help, $who, $joke, $lulz, $ninja, $getplayer, $register, $account, $tell, $youtube');
                              break;

                          //-----------------------------------------------------------------------------------------------------------------------

                          /*
                                                $help
                                    Displays additional information about a command
                           */

                          case ':$help':
                             /* if($this->is_banned() == true) { // if the user is banned
                                  break;
                              }*/
                              $option = $this->ex[4]; // grab the command the user needs more details on
                              $this->help_options($option); // pass the command into our help_options function
                              break;

                          //-----------------------------------------------------------------------------------------------------

                }
            }
        }
        $this->main($config);
    }
}
    //    } // end of if($this->is_bannned() before switch statement...
//--------------------------------------------*********************------------------------------------------------------\\
//---------------------------------------.....______________________.....------------------------------------------------\\
//---------------------------------------     INITIALIZING FUNCTIONS     ------------------------------------------------\\
//---------------------------------------.....______________________.....------------------------------------------------\\
//--------------------------------------------**********************-----------------------------------------------------\\


        function decrypt_md5($hash) {
            $this->who_is();
            $person = $GLOBALS['user'];
            $hash = rtrim($hash); // remove whitespace from the word to lookup
            $start = substr($hash, 0, 3); // grabbing first character of the hash
            $md5_file = "dic/".$start.".txt"; // the file is the first character of the hash in bigdic
                exec("grep -m1 '$hash' $md5_file >> tempt.txt"); // grep the word and store it in tempt.txt
            $file = fopen("tempt.txt", "a+"); // opening tempt.txt
            while(!feof($file)) { // while not at the end of tempt.txt
                $line = rtrim(fgets($file)); // grab the word shold be similar to (hash:value)
                $word = substr($line, 33); // remove the hash and colon from the word (left with plain text password)
                if($hash == "d41d8cd98f00b204e9800998ecf8427e") {
                    break;
                }
                if(strlen($line) <= 0) {
                    if(!preg_match("/$hash/", $line)) {
                        $this->send_data('NOTICE '.$person." :The value for ".$hash." was not found!");
                    }
                }
                if(strlen($word) >= 1) { // if the word is not null such as a carriage return
                    if($this->filter_text($word) == true) {
                        $this->send_data('NOTICE '.$person." :Glined or blacklisted words/phrases were detected, breaking for my safety!");
                    }
                    else {
                        $this->send_data('NOTICE '.$person." :The value for hash: ".$hash." is: ".$word);

                    }
                }
            }
            exec("rm tempt.txt");
        }

//-------------------------------------------------------------------------------------------------------------------

        function db_query($query) {
            $this->con_mysql();
            while($row = mysql_fetch_row($query)) {
                foreach($row as $field) {
                    $this->send_message(stripslashes($field));
                }
            }
        }

//-------------------------------------------------------------------------------------------------------------------

        function get_data($url) {
            return file_get_contents($url);
        }

//-------------------------------------------------------------------------------------------------------------------

        function attack_user($user) {
            // invalid accounts retaliate
            // loot still gets searched on invalid account attacks

            $checkifuserexists = mysql_query("SELECT name FROM players WHERE name='".$user."'");
            $row = mysql_fetch_array($checkifuserexists);
            if($row['name'] != $user) {
                return "INVALID";
            }
            else {
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
            mysql_connect('hostname', 'username', 'password') or die(mysql_error());
            mysql_select_db("database") or die(mysql_error);

        }

//--------------------------------------------------------------------------------------------------------------------

        function send_data($cmd, $msg = null) {
            if($this->filter_text($msg) == true) {
                $this->who_is();
                echo $cmd.'>> NOTICE >> You attempted to manipulate the bot, you will be banned!';
                $person_b = $GLOBALS['fullUser'];
                $explodeit = explode('@', $person_b);
                $host = $explodeit[1];
                exec("echo '$host' >> baned_users.txt");
            }
            else {
            if($msg == null) { // if the message is null
                fputs($this->socket, $cmd."\r\n"); // pass the command through the socket
                echo $cmd;
            }
            else { // if the message is not null
                fputs($this->socket, $cmd.' '.$msg."\r\n\r\n"); // pass the command and message through the socket
                echo $cmd.' '.$msg."\r\n\r\n";
            }

        }
        }

//-------------------------------------------------------------------------------------------------------------------
        function filter_text($text) {
            foreach($this->filterlist as $filter) {
                if(is_array($text)) {
                    if(preg_match($filter, $text[0])) {
                        return true;
                    }
                }
                else {
                    if(preg_match($filter, $text)) {
                        return true;
                    }
                }
        }
        }

//-------------------------------------------------------------------------------------------------------------------

        function get_message() {
            $input = NULL;
            for($i=4; $i < (count($this->ex)); $i++) { // grabbing the message
                $input .= $this->ex[$i]." "; // storing the message in input
            }
            if($this->filter_text($input) == true) {
                $this->insta_ban();
            }
            else {
                return trim($input); // return our message
            }
        }

//-------------------------------------------------------------------------------------------------------------------

        function send_message($x) {
            $chan = $this->ex[2]; // grabbing the channel name
            $x = rtrim($x);
            $this->who_is();
            $person = $GLOBALS['user'];
            if($this->filter_text($x) == true) {
                $this->insta_ban();
            }
            else {

            $message_two = NULL;
            $message = $this->get_message($x); // storing the return of get_message in $message
            for($i=3; $i < (count($this->ex)); $i++) { // grabbing  new message starting from ex[3] (shows the command)
                $message_two .= $this->ex[$i]." "; // storing the new message in mesage_two
            }
            $message_two = trim(substr($message_two, 1)); // removing the colon from message_two

                    if($chan == "NinjX") { // if the channel the message is being sent to is the bot
                        $this->who_is(); // get the details of the current user
                        $this->send_data('PRIVMSG Ninjex :Private Message detected from: ' . $GLOBALS['fullUser'] . " the message is: " . $message_two."\r\n\r\n"); // let admin know someone is in pm with the bot
                        exec("echo Private message detected from: $GLOBALS[fullUser] the message is: '$message_two' >> bot.log"); // log the pm in bot.log
                        return $this->send_data('PRIVMSG '. $GLOBALS['user'] . ' :> ' . $x."\r\n\r\n"); // return the message to the user's name instead of the bot's name
                    }

                        $this->who_is();
                        // $this->send_data("PRIVMSG Ninjex :> Command initiated by: " . $GLOBALS['fullUser'] . " the command is: " . $message_two); // let the admin know someone is commanding the bot
                        exec("echo Command initiated by: $GLOBALS[fullUser] the command is: '$message_two' >> bot.log"); // log the command in bot.log
                        return $this->send_data('PRIVMSG ' . $chan . ' :> ' . $x."\r\n\r\n"); // return the message to the channel
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
      $piece = explode('@', $who);
      $GLOBALS['host'] = $piece[1];
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

    function check_ban($host) {
        $banfile = fopen("baned_users.txt", "a+");
        $hostinfile = NULL;
        while(!feof($banfile) && $hostinfile != $host) {
            $hostinfile = rtrim(fgets($banfile));
        }
        if($hostinfile == $host) {
            return true;
            $this->send_data('PRIVMSG Ninjex :A banned user [' . $GLOBALS['user'] . '] aka [' . $GLOBALS['fullUser'] . '] attempted to use the bot'); // let the admin know a baned user attempted to use the bot
        }
        else {
            return false;
        }
    }

//-------------------------------------------------------------------------------------------------------------------

    function ban_user() {
        $user = $this->ex[4]; // grabbing specified user
        $banfile = fopen("baned_users.txt", "a+"); // opening the ban file
        fwrite($banfile, $user."\n"); // write the specified user to the ban file
        fclose($banfile);
    }

//-------------------------------------------------------------------------------------------------------------------


    function insta_ban($user) {
        $this->who_is();
        $host = $GLOBALS['host'];
        $banfile = fopen("baned_users.txt", "a+");
        fwrite($banfile, $host."\n");
        fclose($banfile);
        if($this->ex[2] == 'NinjX') {
            $this->send_data('PRIVMSG '.$GLOBALS['user'].' :NOTICE >> '.$GLOBALS['user'].', you have been banned for attempting to manipulate me!');
        }
        else {
            $this->send_message("NOTICE >> ".$GLOBALS['user']." you have banned for attempting to manipulate me!");
        }
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
        if($result == NULL) {
            $this->send_message("Invalid characters were assigned in the math function!");
            return "NULL";
            break;
        }
        else {
            return $result; // return the sum
        }
    }

//-------------------------------------------------------------------------------------------------------------------

// create an about statement, that describes the bot

    function help_options($option) {
        $additionalopt = $this->ex[5];
        switch($option) {
          //---            // $len, $youtube
            case 'leave':
                $this->send_message('Description: Forces NinjX to leave the desired channel.');
                $this->send_message('Syntax: $leave ChannelName');
                $this->send_message('Example: $leave #coffeesh0p');
                break;
            //---
            case 'ban':
                $this->send_message('Description: Adds the specified user to the ban list.');
                $this->send_message('Syntax: $ban userHost');
                $this->send_message('Example: $ban HTS-803BD2F2.blah.something.net');
                break;
            //---
            case 'rmban':
                $this->send_message('Description: Removes a specified host from the ban list.');
                $this->send_message('Syntax: $rmban hostName');
                $this->send_message('Example: $rmban HTS-803BD2F2.blah.something.net');
                break;
            //---
            case 'mod':
                $this->send_message('Description: Adds a user to the mod list.');
                $this->send_message('Syntax: $mod fullUsernme');
                $this->send_message('Example: $mod :Ninjex!ninjex@HTS-C0484C46.lightspeed.nsvltn.sbcglobal.net');
                break;
            //---
            case 'rmod':
                $this->send_message('Description: Removes a host from the mod list.');
                $this->send_message('Syntax: $rmod hostName');
                $this->send_message('Example: $rmod :Ninjex!ninjex@HTS-C0484C46.lightspeed.nsvltn.sbcglobal.net');
                break;
            //---
            case 'rmtemp':
                $this->send_message('Description: Removes the temp file, used for multiple writes.');
                $this->send_message('Syntax: $rmtemp');
                break;
            //---
            case 'rmfile':
                $this->send_message('Description: Removes the file, (md5hashes)  that contains hashes for lookup.');
                $this->send_message('Syntax: $rmfile');
                break;
            //---
            case 'addplayer':
                $this->send_message('Description: Adds a player to the ninja database, so that they may play.');
                $this->send_message('Syntax: $addplayer userName classType (Assassin - speed | Warrior - strength | Elf - health | Archer - defense)');
                $this->send_message('Example: $addplayer Ninjex Assassin');
                break;
            //---
            case 'hashfile':
                $this->send_message('Description: Adds a list of hashes to the hashfile, to be looked up via $md5file.');
                $this->send_message('Syntax: $hashfile hash1 hash2 hash3, etc');
                $this->send_message('Example: $hashfile 81c3b1024948e425ff92359fde1eef1c 5416d7cd6ef195a0f7622a9c56b55e84 0cc175b9c0f1b6a831c399e269772661');
                break;
            //---
            case 'emd5file':
                $this->send_message('Description: Takes plaintext words, converts them into md5 and stores them inside the hashfile to be looked up via $md5file.');
                $this->send_message('Syntax: $emd5file word1 word2 word3');
                $this->send_message('Example: $emd5file PaSsWord something yolo dude');
                break;
            //---
            case 'md5file':
                $this->send_message('Description: Looks up all the hashes inside of the hashfile.');
                $this->send_message('Syntax: $md5file');
                break;
            //---
            case 'hashit':
                $this->send_message('Description: Converts a word into a hash type, or all the available hash types.');
                $this->send_message('Syntax 1: $hashit word hashType | Syntax 2: $hashit word all');
                $this->send_message('Example 1: $hashit dude sha1 | Example 2: $hashit dude all');
                break;
            //---
            case 'who':
                $this->send_message('Description: Displays your current short and long name, and your privilege type.');
                $this->send_message('Syntax: $who');
                break;
            //---
            case 'joke':
                $this->send_message('Description: Says a joke to the specified user.');
                $this->send_message('Syntax: $joke user');
                break;
            //---
            case 'lulz':
                $this->send_message('Description: Takes two random users, and makes a funny statement with their names.');
                $this->send_message('Syntax: $lulz');
                break;
            //---
            case 'ninja':
                $this->send_message('Make sure you specify this help further using $help ninja choice');
                $this->send_message('Choices: attack, start');
                switch($additionalopt) {
                    case 'attack':
                        $this->send_message('Description: Attacks another specified player.');
                        $this->send_message('Syntax: $ninja attack userName');
                        $this->send_message('Example: $ninja attack Ninjex');
                        break;
                }
                break;
            //---
            case 'getplayer':
                $this->send_message('Description: Gets the details of a specified user account.');
                $this->send_message('Syntax: $getplayer playerName');
                $this->send_message('Example: $getplayer Ninjex');
                break;
            //---
            case 'register':
                $this->send_message('Description: Allows a user to register an account to play Ninja with.');
                $this->send_message('Syntax: $register password');
                $this->send_message('Example: $register ap0coL_Ipz!0!');
                break;
            //---
            case 'account':
                $this->send_message('Make sure you specify this help further using $help account choice');
                $this->send_message('Choices: info, status, login, logout');
                switch($additionalopt) { // info, status, login, logout
                    case 'info':
                        $this->send_message('Description: Displays your Ninja account information (must be logged in)');
                        $this->send_message('Syntax: $account info');
                        break;
                    case 'status':
                        $this->send_message('Description: Tells you if you are logged into your Ninja account or not.');
                        $this->send_message('Syntax: $account status');
                        break;
                    case 'login':
                        $this->send_message('Description: Logs you into your Ninja account.');
                        $this->send_message('Syntax: $account login password');
                        $this->send_message('Example: $account login ap0coL_Ipz!0!');
                        break;
                    case 'logout':
                        $this->send_message('Description: Logs you out of your Ninja account (must be logged in)');
                        $this->send_message('Syntax: $account logout');
                        break;
                }
                break;
            //---
            case 'tell':
                $this->send_message('Description: Sends a specific message to a specific user/channel.');
                $this->send_message('Syntax: $tell channel/nick message');
                $this->send_message('Example: $tell Ninjex how are you doing?');
                break;
            //---
            case 'htsuser':
                $this->send_message('Description: Sends all the accomplishments of a user to your pm.');
                $this->send_message('Syntax: $htsuser userName');
                $this->send_message('Example: $htsuser -Ninjex-');
                break;
            //---
            case 'forum':
                $this->send_message('Description: Grabs the most recent forum information, use describe after the command for a description.');
                $this->send_message('Syntax 1: $forum 1 describe | Syntax 2: $forum 1 | (1 can be switched out for 2; 1 being the most recent post, and 2 being the second most recent post.');
                break;
            //---
            case 'b2hex':
                $this->send_message('Description: Converts text/binary into hex');
                $this->send_message('Syntax: $b2hex stringsAndCharacters');
                $this->send_message('Example: $b2hex convert this to hex');
                break;
            //---
            case 'len':
                $this->send_message('Description: Gets the word count of the specified input');
                $this->send_message('Syntax: $len stringHere | (would return 10)');
                $this->send_message('Example: $len Ninjex');
                break;
            //---
            case 'youtube':
                $this->send_message('Description: Shows the youtube title URL with the link');
                $this->send_message('Syntax: $youtube link');
                $this->send_message('Example: $youtube https://www.youtube.com/watch?v=BDW0xWXzuCs');
                break;
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
            case 'md5':
                $this->send_message('Description: NinjX will run a lookup attack on said md5 hash.');
                $this->send_message('Syntax: $md5 md5HashValue');
                $this->send_message('Example: $md5 5f4dcc3b5aa765d61d8327deb882cf99');
                break;
          //---
         /*   case 'word': // broken for now
                $this->send_message('Description: NinjX will add the said string to the dictionary for dictionary attacks.');
                $this->send_message('Syntax: $word text');
                $this->send_message('Example: $word BiiG->B4ngTh3[0]Ry');
                break;*/
          //---
            case 'help':
                $this->send_message('Description: Shows you how to use the commands with correct syntax.');
                $this->send_message('Syntax: $help commandName');
                $this->send_message('Example $help say');
                break;
          //---
            case 'math':
                $this->send_message('Description: NinjX will do math operations (Uses high precedence values) (uses eval)');
                $this->send_message('Syntax: $math mathOperations');
                $this->send_message('Example: $math 205*40/3');
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
