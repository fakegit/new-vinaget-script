<?php

class dl_upstore_net extends Download
{
    public function CheckAcc($cookie)
    {
        $data = $this->lib->curl("https://upstore.net/", $cookie, "");

        if (stristr($data, '<a href="/account/">Dashboard</a>')) {
            if (stristr($data, 'premium till')) {
                $expireDate = trim($this->lib->cut_str($data, 'premium till', '<a href="/premium/">'));
                return array(true, "Until " . $expireDate);
            }
            return array(false, "accfree");
        }

        return array(false, "accinvalid");
    }

    public function Login($user, $pass)
    {
        $post = "url=" . urlencode("https://upstore.net/account/login/") . "&email={$user}&password={$pass}&send=Login";
        $data = $this->lib->curl("https://upstore.net/account/login/", "", $post);
        $cookie = $this->lib->GetCookies($data);

        if (!$cookie) {
            return false;
        }

        return array(true, $cookie);
    }

    public function Leech($url)
    {
        $data = $this->lib->curl($url, $this->lib->cookie, "");

        if (stristr($data, "File not found") || stristr($data, "File was deleted by owner or due to a violation of service rules.")) {
            $this->error("dead", true, false, 2);
        }

        if (preg_match('/<input type="hidden" name="hash" value="(.*?)">/', $data, $matches)) {
            $post = array(
                'hash' => $matches[1],
                'antispam' => 'spam'
            );

            $data = $this->lib->curl("https://upstore.net/load/premium/", $this->lib->cookie, $post);

            if (stristr($data, "you have reached a download limit for today") || stristr($data, "You are not premium user")) {
                $this->error("LimitAcc", true, false);
            }

            if (preg_match('/href="(.*?)">Click/i', $data, $link)) {
                return trim($link[1]);
            }
        }

        return false;
    }
}

/*
 * Open Source Project
 * New Vinaget by LTT
 * Version: 3.3 LTS
 * Upstore.net Download Plugin
 * Date: 27.09.2026
 */