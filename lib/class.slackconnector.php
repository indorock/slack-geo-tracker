<?php

require_once('./lib/class.oauth2connector.php');

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
        $ret = $this->client->fetch('https://slack.com/api/team.accessLogs?token='.$this->access_token);
        if(!$ret || $ret['result']['error']) {
            self::logerror('cannot fetch slack team users!', false);
            return false;
        }
        return $ret['members']['user'];
    }

    public function getUserInfo($user_id){
        self::log('get username for '.$user_id);
        $ret = $this->client->fetch('https://slack.com/api/users.info?token='.$this->access_token.'&user='.$user_id);
        if(!$ret || $ret['result']['error']) {
            self::logerror('cannot fetch slack user info!', false);
            return $user_id;
        }
        return $ret['result']['user'];
    }


}
