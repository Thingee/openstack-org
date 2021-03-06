<?php
/**
 * Copyright 2014 Openstack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

final class OpenStackIdCommon {

    public static function redirectToSSL($url){
        $dest = str_replace('http:', 'https:', Director::absoluteURL($url));
        // This coupling to SapphireTest is necessary to test the destination URL and to not interfere with tests
        if(!headers_sent()) header("Location: $dest");
        die("<h1>Your browser is not accepting header redirects</h1><p>Please <a href=\"$dest\">click here</a>");
    }

    /**
     * @return string
     */
    public static function getReturnTo()
    {
        $trust_root    = self::getTrustRoot();
        $return_to_url = "{$trust_root}/OpenStackIdAuthenticator?url=/OpenStackIdAuthenticator";
        // check first on session ...
        $back_url      = urlencode(self::getRedirectBackUrl());
        return "{$return_to_url}&BackURL={$back_url}";
    }

    /**
     * @return string
     */
    public static function getRedirectBackUrl(){
        // check first on session ...
        $back_url = Controller::curr()->getSession()->get("BackURL");
        if(empty($back_url))
            $back_url = Controller::curr()->getRequest()->requestVar('BackURL');
        $fragment = Controller::curr()->getRequest()->requestVar('fragment');

        if(empty($back_url))
            $back_url = Director::baseURL();
        if(!empty($fragment))
            $back_url .= $fragment;

        $back_url = Director::absoluteURL($back_url, true);

        return $back_url;
    }

    public static function getTrustRoot()
    {
        return Auth_OpenID_Realm;
    }

    public static function escape($thing) {
        return htmlentities($thing);
    }

    public static function loginMember($member, $back_url){

        $back_url = self::cleanBackUrl($back_url);

        if (!defined('OPENSTACKID_ENABLED') || OPENSTACKID_ENABLED == false){
            $member->login();
            return Controller::curr()->redirect($back_url);
        }

        return
            Controller::curr()->customise(
            [
                    'LoginUrl' => '/Security/login?BackURL='.$back_url,
                    'Member'   => $member
            ])->renderWith(['RegistrationPage_success', 'Page']);
    }

    public static function doLogin($back_url = ''){
        if(empty($back_url)) $back_url = Controller::curr()->getRequest()->getURL(true);
        $back_url = self::cleanBackUrl($back_url);
        return Controller::curr()->redirect('/Security/login/?BackURL='.$back_url);
    }

    public static function doLogout($back_url = ''){
        if(empty($back_url)) $back_url = Controller::curr()->getRequest()->getURL(true);
        $back_url = self::cleanBackUrl($back_url);
        return Controller::curr()->redirect('/Security/logout/?BackURL='.$back_url);
    }

    public static function cleanBackUrl($back_url){
        if(empty($back_url) || (!empty($back_url) && !Director::is_site_url($back_url))){
            $back_url = Director::baseURL();
        }

        if($back_url == Director::baseURL()."Security/")
            $back_url = Director::baseURL();
        return $back_url;
    }

    /**
     * @param string $message
     * @param string $back_url
     * @return SS_HTTPResponse
     */
    public static function error($message, $back_url){
        Session::set("Security.Message.message", $message);
        Session::set("Security.Message.type", "bad");
        return Controller::curr()->redirect("Security/error?BackURL={$back_url}");
    }
}