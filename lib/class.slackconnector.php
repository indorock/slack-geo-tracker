<?php

require_once('./lib/class.oauth2connector.php');
require_once('./lib/class.curl.php');

class SlackConnector extends OAuth2Connector{

    private $allowed_channels;
    private $verification_token;

    public function __construct(){

        parent::__construct('slack');

        $this->allowed_channels = $this->xpath_query->get_value('//settings/group[@type="slack"]/item[@name="allowed_channel_ids"]');
        $this->verification_token = $this->xpath_query->get_value('//settings/group[@type="slack"]/item[@name="verification_token"]');

        $this->authorize_url = "https://slack.com/oauth/authorize";
        $this->accesstoken_url = "https://slack.com/oauth/authorize";
        $this->scope = "identity.basic";
        $this->state = "redditauth123456789";
    }

    public function getToken(){
        return $this->verification_token;
    }

    public function getTeamUsers(){
        $ret = $this->client->fetch('https://slack.com/api/users.list?token='.$this->access_token);
        if(!$ret || $ret['result']['error']) {
            self::logerror('cannot fetch slack team users!', false);
            return false;
        }
        return $ret['members'];
    }

    public function getTeamLogins($token = null){
//        if(!$token)
//            $token = $this->access_token;

        $mem = new Memcached();
        $mem->addServer("127.0.0.1", 11211);

        if($token){
            $out = $mem->get("logins");
            if(!$out){
                $res = $this->client->fetch('https://slack.com/api/team.accessLogs?count=500&token=' . $token);

                if($res !== false){
                    $data = json_decode($res);
                    $out = $data['logins'];
                    if($data && !@$data['error']){
                        $mem->set("logins", $out, time() + 3600);
                    }else{
                        if($data === null)
                            var_dump(json_last_error_msg());
                        self::logerror('cannot fetch slack team logins!', false);
                        return ['error'=>true];
                    }
                }
            }
        }else{
            $curl = new Curl();
            $res = $curl->call('http://apps.offcentric.com/slack-geo-tracker/test.json');
            $data = json_decode($res, true);
            $out = $data['logins'];
        }

        return $out;
    }

    public function getLastUserLogin($user_id){
        $logins = $this->getTeamLogins();
        foreach($logins as $login){
            if($login['user_id'] == $user_id)
                return $login;
            }
    }

    public function getAllUsersInfo(){
        $users = $this->getTeamUsers();
        $members = [];
        foreach($users as $user)
            $members[] = ['name' => $user['real_name'], 'avatar' => $user['profile']['image_32']];

        return $members;

    }
    public function getUserInfo($user_id){
        self::log('get username for '.$user_id);
        $ret = $this->client->fetch('https://slack.com/api/users.info?token='.$this->access_token.'&user='.$user_id);
        if(!$ret || @$ret['result']['error']) {
            self::logerror('cannot fetch slack user info!', false);
            return $user_id;
        }
        return $ret['result']['user'];
    }


}
