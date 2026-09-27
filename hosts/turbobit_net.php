<?php

class dl_turbobit_net extends Download
{

    public function CheckAcc($cookie)
    {
        $data = $this->lib->curl("https://app.turbobit.net/api/user/info", $cookie, "", 0, 1);
        $json = @json_decode($data, true);

        if (!is_array($json) || !isset($json['premium']['status'])) {
            return array(false, "accinvalid");
        }

        if ($json['premium']['status'] === 'active') {
            $expired_at = isset($json['premium']['expiredAt']) ? $json['premium']['expiredAt'] : '';
            return array(true, "Until " . $expired_at);
        } elseif ($json['premium']['status'] === 'inactive') {
            return array(false, "accfree");
        } else {
            return array(false, "accinvalid");
        }
    }

    public function Login($user, $pass)
    {
        $payload = json_encode([
            'email'           => $user,
            'password'        => $pass,
            'captcha'         => true,
            'captchaResponse' => '',
            'captchaIndex'    => 0,
        ]);

        $data = $this->lib->curl("https://app.turbobit.net/api/auth/login", "", $payload, 1, 1);
        $cookie = "user_lang=en; " . $this->lib->GetCookies($data);
        return array(true, $cookie);
    }

    public function Leech($url)
    {
        if (strpos($url, "/download/free/") !== false) {
            $gach = explode('/', $url);
            $url = "https://turbobit.net/{$gach[5]}.html";
        }

        // Extract file ID
        $gach = explode('?', $url);
        $clean_url = $gach[0];
        $parts = explode('/', $clean_url);
        $file_id = preg_replace('/\.html?$/i', '', end($parts));
        $file_id = str_replace('#', '', $file_id);

        $api_payload = json_encode([
            'fileId' => $file_id,
            'referrer' => null,
            'site' => null,
            'shortDomain' => '',
        ]);
        $api_data = $this->lib->curl('https://app.turbobit.net/api/download/info', $this->lib->cookie, $api_payload, 0, 1);
        $api_json = @json_decode($api_data, true);

        if (!$api_json || isset($api_json['error_name'])) {
            $error_name = isset($api_json['error_name']) ? $api_json['error_name'] : '';
            if (stristr($error_name, 'site is temporarily unavailable')) {
                $this->error("dead", true, false, 2);
            } elseif (stristr($error_name, 'limit of premium downloads')) {
                $this->error("LimitAcc");
            } elseif (stristr($error_name, 'denied')) {
                $this->error("blockAcc", true, false);
            } else {
                $this->error("dead", true, false, 2);
            }
            return false;
        }

        if (!empty($api_json['downloadUrls']) && is_array($api_json['downloadUrls'])) {
            $link = $api_json['downloadUrls'][0];
            $data = $this->lib->curl($link, $this->lib->cookie, "");
            if ($this->isRedirect($data)) {
                return trim($this->redirect);
            }
            return $link;
        }

        return false;
    }

}

/*
 * Open Source Project
 * New Vinaget by LTT
 * Version: 3.3 LTS
 * Turbobit.net Download Plugin
 * Date: 08.04.2026
 */
