<?php

class dl_daofile_com extends Download
{
    public function CheckAcc($cookie)
    {
        $data = $this->lib->curl("https://daofile.com/?op=my_account", "lang=english;{$cookie}", "");
        if (stristr($data, 'Premium account expire')) {
            $expire = trim($this->lib->cut_str($data, 'Premium account expire <b>', '</b>'));
            $days = trim($this->lib->cut_str($data, 'Traffic for ', ' days'));
            $trafficMb = trim($this->lib->cut_str($data, 'Available ', ' Mb'));
            $trafficBytes = (float)$trafficMb * 1024 * 1024;
            $trafficStr = $this->lib->convertmb($trafficBytes);
            return array(true, "Until {$expire}<br>Traffic Available: {$trafficStr} in {$days} days");
        } elseif (stristr($data, '<h2>Account balance')) {
            $days = trim($this->lib->cut_str($data, 'Traffic for ', ' days'));
            $trafficMb = trim($this->lib->cut_str($data, 'Available ', ' Mb'));
            $trafficBytes = (float)$trafficMb * 1024 * 1024;
            $trafficStr = $this->lib->convertmb($trafficBytes);
            return array(false, "accfree - Traffic Available: {$trafficStr} in {$days} days");
        }
        return array(false, "accinvalid");
    }

    public function Login($user, $pass)
    {
        $data = $this->lib->curl("https://daofile.com/?op=login&referer=homepage", "lang=english", "login={$user}&password={$pass}&redirect=");
        if (stristr($data, 'cloudflare.com/')) {
            $this->error("Cloudflare detected. Please login manually via browser and input cookie: xfss=XXXX", false, false);
            return false;
        }
        $cookie = "lang=english;{$this->lib->GetCookies($data)}";

        return array(true, $cookie);
    }

    public function Leech($url)
    {
        list($url, $pass) = $this->linkpassword($url);
        $data = $this->lib->curl($url, $this->lib->cookie, "");

        if ($pass) {
            $post = $this->parseForm($this->lib->cut_str($data, '<form', '</form>'));
            $post["password"] = $pass;
            $data = $this->lib->curl($url, $this->lib->cookie, $post);
            if (stristr($data, 'Wrong password')) {
                $this->error("wrongpass", true, false, 2);
            } elseif ($this->isRedirect($data)) {
                return trim($this->redirect);
            }
        }

        if (stristr($data, '<h2>File Not Found</h2>') || stristr($data, '<b>File Not Found</b>')) {
            $this->error("dead", true, false, 2);
        } elseif (!$this->isRedirect($data)) {
            $post = $this->parseForm($this->lib->cut_str($data, '<form name="F1"', '</form>'));
            $data = $this->lib->curl($url, $this->lib->cookie, $post);
            if ($this->isRedirect($data)) {
                return trim($this->redirect);
            }
        } else {
            return trim($this->redirect);
        }

        return false;
    }
}

/*
 * Open Source Project
 * New Vinaget by LTT
 * Version: 3.3 LTS
 * Daofile.com Download Plugin
 * Date: 16.03.2026
 */
