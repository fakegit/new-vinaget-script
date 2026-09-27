<?php

class dl_1fichier_com extends Download
{
    public function CheckAcc($cookie)
    {
        $data = $this->lib->curl("https://1fichier.com/console/abo.pl", $cookie, "");
        if (preg_match('/class="tier current".*?class="tier-name">\s*([^<]+).*?class="tier-badge">[^<]*until\s+([^<]+)/is', $data, $match)) {
            return array(true, trim($match[1]) . " Plan - Until " . trim($match[2]));
        } elseif (stristr($data, "<div class=\"tier\">")) {
            return array(false, "accfree");
        }

        return array(false, "accinvalid");
    }

    public function Login($user, $pass)
    {
        $data = $this->lib->curl("https://1fichier.com/login.pl", "", "mail={$user}&pass={$pass}&lt=on&Login=Login");
        $cookie = $this->lib->GetCookies($data);

        return array(true, $cookie);
    }

    public function Leech($url)
    {
        $data = $this->lib->curl($url, $this->lib->cookie, "");
        if (stristr($data, 'Premium status must not be used on professional services')) {
            $this->error("blockIP", true, false);
        } elseif (stristr($data, "The requested file could not be found")) {
            $this->error("dead", true, false, 2);
        } elseif ($this->isRedirect($data)) {
            return trim($this->redirect);
        } elseif (preg_match('/<form[^>]+action="([^"]+)"/i', $data, $matches)) {
            $urlDownload = trim($matches[1]);
            $data = $this->lib->curl($urlDownload, $this->lib->cookie, "did=0");
            if ($this->isRedirect($data)) {

                return trim($this->redirect);
            }
        }

        return false;
    }
}

/*
 * Open Source Project
 * New Vinaget by LTT
 * Version: 3.3 LTS
 * 1fichier.com Download Plugin
 * Date: 12.09.2026
 */
