<?php

header('Content-Type:application/json');

require("./vendor/autoload.php");
require('./lib/xpath.query.php');
require("./lib/class.slackconnector.php");

use GeoIp2\Database\Reader;

class Markers{

    static protected $positions = [];

    static function get_markers($token = null){

        $sc = new SlackConnector();
        $reader = new Reader('/usr/local/share/geoip/geolite2-city.mmdb');

        $members = [];
        $logins = $sc->getTeamLogins($token);
        if($logins == false || @$logins['error'])
            return $logins;

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
            self::$positions[$ip] = $lat . '|' . $long;
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

$token = null;
if(isset($_POST['token']))
    $token = $_POST['token'];

$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);
$data = $mem->get("markers");

if(!$data || $token){
    $ret = Markers::get_markers($token);
    if($ret !== false){
        $data = json_encode($ret);
        if(!@$ret['error']){
            $mem->set("markers", $data, time() + 3600);
            if($token)
                header('location:map.html');
        }
    }
}
echo $data;
