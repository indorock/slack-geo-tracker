<?php

header('Content-Type:application/json');

require("./vendor/autoload.php");
require('./lib/xpath.query.php');
require("./lib/class.slackconnector.php");

use GeoIp2\Database\Reader;

class Markers{

    static protected $positions = [];

    static function get_markers(){

        $sc = new SlackConnector();
        $reader = new Reader('/usr/local/share/geoip/geolite2-city.mmdb');

        $members = [];
        $logins = $sc->getTeamLogins();
        foreach($logins as $login){
            $user = $sc->getUserInfo($login['user_id']);
            $members[] = ['name' => $user['real_name'], 'ip' => $login['ip'], 'avatar' => $user['profile']['image_32']];
        }

        $i = 0;
        $out = [];

        foreach($members as $member){
            $i++;
            $ip = $member['ip'];
            if(!array_key_exists($ip, self::$positions)){
                $record = $reader->city($ip);
                $lat = $record->location->latitude;
                $long = $record->location->longitude;
            }else{
                $coords = explode('|',self::$positions[$ip]);
                $lat = $coords[0];
                $long = $coords[1];
            }
            self::setPos($ip, $lat, $long);
            $out[] = [
                'position' => ['lat' => $lat, 'lng' => $long],
                'icon' => $member['avatar'],
                'title' => $member['name'],
            ];
        }

        return $out;
    }

    static function setPos($ip, &$lat, &$long){
        if (!in_array($lat . '|' . $long, self::$positions)) {
            $positions[$ip] = $lat . '|' . $long;
            return;
        }
        $posneg = [1,-1];
        $latshift = (rand(100,200)/10000)*$posneg[rand(0,1)];
        $longshift = (rand(100,200)/10000)*$posneg[rand(0,1)];
        $lat = $lat+$latshift;
        $long = $long+$longshift;
        self::setPos($ip, $lat, $long);
    }
}



$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);
$out = $mem->get("markers");

if(!$out){
    $out = Markers::get_markers();
    $out = json_encode($out);
    $ret = $mem->set("markers",$out,time()+3600);
}
echo $out;



//// Replace "city" with the appropriate method for your database, e.g.,
//// "country".
//$members = [
//    ['name' => 'Atilla', 'ip' => '128.101.101.101', 'avatar' => 'https://avatars.slack-edge.com/2015-07-31/8445653365_5ecca53956099cad5354_32.jpg'],
//    ['name' => 'Mark', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2016-08-09/67564642033_fdd263db12ab2eb74762_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Marijn', 'ip' => '85.214.132.117', 'avatar' => 'https://avatars.slack-edge.com/2015-03-24/4159284123_750a67b29cbc175d02a4_32.jpg'],
//    ['name' => 'Frenk', 'ip' => '217.80.125.192', 'avatar' => 'https://avatars.slack-edge.com/2015-01-26/3518740219_594370ed614acd242b49_32.jpg'],
//    ['name' => 'Glenn', 'ip' => '134.100.7.231', 'avatar' => 'https://secure.gravatar.com/avatar/f9dee86804e007089cf5f363294a187f.jpg?s=32&d=https%3A%2F%2Fa.slack-edge.com%2F0180%2Fimg%2Favatars%2Fava_0013-32.png'],
//    ['name' => 'Jasper', 'ip' => '2.3.4.5', 'avatar' => 'https://avatars.slack-edge.com/2015-09-23/11220340258_9360c291ae00b0755fd0_32.jpg'],
//    ['name' => 'Ruben', 'ip' => '195.10.102.252', 'avatar' => 'https://avatars.slack-edge.com/2016-12-10/115324634005_d858105d6433a12ec087_32.png'],
//    ['name' => 'Wu', 'ip' => '5.62.12.118', 'avatar' => 'https://avatars.slack-edge.com/2015-01-29/3552338641_0676211c42623297839a_32.jpg'],
//    ['name' => 'Sheeld', 'ip' => '122.59.171.216', 'avatar' => 'https://avatars.slack-edge.com/2017-02-09/139196629940_2ec2cf565d372f7d68bd_32.png'],
//    ['name' => 'Michel', 'ip' => '5.3.4.5', 'avatar' => 'https://avatars.slack-edge.com/2015-07-29/8388012690_19361830f4c8b357c484_32.jpg'],
//    ['name' => 'Satish', 'ip' => '168.1.6.26', 'avatar' => 'https://avatars.slack-edge.com/2015-01-26/3519090799_4f85243ea22ad8342781_32.jpg']
//];

