<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    Sirv Limited <support@sirv.com>
 *  @copyright Copyright (c) 2017-2025 Sirv Limited. All rights reserved
 *  @license   https://www.magictoolbox.com/license/
 */

require_once(SIRV_PLUGIN_SUBDIR_PATH . 'includes/classes/utils.class.php');

class SirvAPIClient
{
    private $clientId = '';
    private $clientSecret = '';
    private $clientId_default = 'CCvbv8cbDcgijrSOrLd4sQ80jiN';
    private $clientSecret_default = '02gC7DoQ/wyKUliskFeQnjaYIZtMEFzJu7/TH3ayyNahkKfd4Nmaxw871FikWeRG2W9KEKB0JOelKibQw6QbeA==';
    private $token = '';
    private $tokenExpireTime = 0;
    private $connected = false;
    private $lastResponse;
    private $mute_endpoint_expired_at = 0;
    private $userAgent;
    private $baseURL = "https://api.sirv.com/";

    public function __construct(
        $clientId,
        $clientSecret,
        $token,
        $tokenExpireTime,
        $userAgent
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->token = $token;
        $this->tokenExpireTime = $tokenExpireTime;
        $this->userAgent = $userAgent;
    }


    public function fetchImage($imgs)
    {
        $endpoint_name = 'v2/files/fetch';
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/fetch',
            $imgs,
            'POST'
        );

        //if ($res && $res->http_code == 200) {
        if ($res) {
            $this->connected = true;
            return $res;
        } else {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
    }


    public function uploadImage($fs_path, $sirv_path)
    {
        $endpoint_name = 'v2/files/upload';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return array("upload_status" => 'failded', "error" => "Something went wrong during preoperation check");
        }

        if( ! Utils::startsWith('/', $sirv_path) ){
            $sirv_path = '/' . $sirv_path;
        }


        $content_type = '';
        if (function_exists('mime_content_type')) {
            $content_type = mime_content_type($fs_path) !== false ? mime_content_type($fs_path) : 'application/octet-stream';
        } else {
            $content_type = "image/" . pathinfo($sirv_path, PATHINFO_EXTENSION);
        }

        $headers = array(
            'Content-Type'   => $content_type,
            'Content-Length' => filesize($fs_path),
        );

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/upload?filename=' . $sirv_path,
            file_get_contents($fs_path),
            'POST',
            '',
            $headers,
            true);

        if ($res && $res->http_code == 200) {
            $this->connected = true;

            return array('upload_status' => 'uploaded');
        } else {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();

            $error = isset($res->error) ? $res->error : "Unknown uploading error";

            return array('upload_status' => 'failed', "error" => $error);
        }
    }


    private function clean_symbols($str)
    {
        $str = str_replace('%40', '@', $str);
        $str = str_replace('%5D', '[', $str);
        $str = str_replace('%5B', ']', $str);
        $str = str_replace('%7B', '{', $str);
        $str = str_replace('%7D', '}', $str);
        $str = str_replace('%2A', '*', $str);
        $str = str_replace('%3E', '>', $str);
        $str = str_replace('%3C', '<', $str);
        $str = str_replace('%24', '$', $str);
        $str = str_replace('%3D', '=', $str);
        $str = str_replace('%2B', '+', $str);
        $str = str_replace('%27', "'", $str);
        $str = str_replace('%28', '(', $str);
        $str = str_replace('%29', ')', $str);

        return $str;
    }


    public function search($query, $from)
    {
        $endpoint_name = 'v2/files/search';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $data = array(
            'query' => $query,
            'from' => $from,
            'size' => 50,
            'sort' => array("basename.raw" => "asc")
        );

        $res = $this->sendRequest($endpoint_name, 'v2/files/search', $data, 'POST');

        if ($res){
            $this->connected = true;

            if($res->http_code == 200){
                if ($res->result->total > $from + 50) {
                    $res->result->isContinuation = true;
                    $res->result->from = $from + 50;
                } else {
                    $res->result->isContinuation = false;
                }
            }

            if ($res->http_code == 400) {
                //some code here
                $res->result->total = 0;
            }

            return $res->result;

        } else {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
    }


    public function copyFile($filePath, $copyFilePath)
    {
        $endpoint_name = 'v2/files/copy';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            $endpoint_name,
            "v2/files/copy?from=$filePath&to=$copyFilePath",
            array(),
            'POST'
        );

        return ($res && $res->http_code == 200);
    }


    public function deleteFile($filename, $isPreOperationCheck = true)
    {
        $endpoint_name = 'v2/files/delete';

        if( $isPreOperationCheck ){
            $preCheck = $this->preOperationCheck();
            if (!$preCheck) {
                return false;
            }
        }

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/delete?filename=/'. rawurlencode(rawurldecode($filename)),
            array(),
            'POST'
        );

        return ($res && $res->http_code == 200);
    }


    public function deleteFiles($files)
    {
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $delete_count = 0;
        $undelete_count = 0;

        for( $i=0; $i < count($files); $i++ ){
            $result = $this->deleteFile(stripslashes($files[$i]), false);

            if( $result ){
                $delete_count++;
            }else{
                $undelete_count++;
            }
        }

        return array("delete" => $delete_count, "undelete" => $undelete_count);
    }


    public function runRemoteJobToDeleteItems($items = array()){
        $endpoint_name = 'v2/files/batch/delete';
        $response = array();

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            $response['error'] = "Something went wrong during preoperation check";

            return $response;
        }

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/batch/delete',
            array(
                'filenames' => $items
            ),
            'POST'
        );

        if ($res && $res->http_code == 200) {
            $response['job_id'] = $res->result->id;
        } else {
            $response['error']  = isset($result->error) ? $result->error : "Unknown error during Sirv API request to endpoint $endpoint_name";
        }

        return $response;
    }


    public function checkStatusOfRemoteJobToDeleteItems($job_id){
        $endpoint_name = 'v2/files/batch/delete';
        $response = array();

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            $response['error'] = "Something went wrong during preoperation check";

            return $response;
        }

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/batch/delete?id=' . $job_id,
            array(),
            'GET'
        );

        if ($res && $res->http_code == 200) {
            $response = $res->result;
        } else {
            $response['error'] = isset($result->error) ? $result->error : "Unknown error during Sirv API request to endpoint v2/files/batch/delete?id=$job_id";
        }

        return $response;
    }


    public function createFolder($folderPath)
    {
        $endpoint_name = 'v2/files/mkdir';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/mkdir?dirname=/' . rawurlencode(rawurldecode(stripcslashes($folderPath))),
            array(),
            'POST'
        );

        return ($res && $res->http_code == 200);
    }


    public function renameFile($oldFilePath, $newFilePath)
    {
        $endpoint_name = 'v2/files/rename';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $oldFilePath = rawurlencode(rawurldecode(stripcslashes($oldFilePath)));
        $newFilePath = rawurlencode(rawurldecode(stripcslashes($newFilePath)));

        $res = $this->sendRequest(
            $endpoint_name,
            "v2/files/rename?from=$oldFilePath&to=$newFilePath",
            array(),
            'POST'
        );

        return ($res && $res->http_code == 200);
    }


    public function setMetaTitle($filename, $title)
    {
        $endpoint_name = 'v2/files/meta/title';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/meta/title?filename=' . $filename,
            array(
                'title' => $title
            ),
            'POST');

        return ($res && $res->http_code == 200);

    }


    public function setMetaDescription($filename, $description)
    {
        $endpoint_name = 'v2/files/meta/description';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/meta/description?filename=' . $filename,
            array(
                'description' => $description
            ),
            'POST');

        return ($res && $res->http_code == 200);
    }


    public function configFetching($url, $status, $minify)
    {
        $endpoint_name = 'v2/account';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $data = array();

        if ($status) {
            $data = array(
                'minify' => array(
                    "enabled" => $minify
                ),
                'fetching' => array(
                    "enabled" => true,
                    "type" => "http",
                    "http" => array(
                        "url" => $url,
                    ),
                )
            );
        } else {
            $data = array(
                'minify' => array(
                    "enabled" => false
                ),
                'fetching' => array(
                    "enabled" => false
                )
            );
        }

        $res = $this->sendRequest($endpoint_name, 'v2/account', $data, 'POST');

        if ($res) {
            $this->connected = true;
            return true;
        } else {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
    }


    public function configCDN($status, $alias)
    {
        $endpoint_name = 'v2/account';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $data = array(
            'aliases' => array(
                $alias => array(
                    "cdn" => $status
                )
            )
        );

        $res = $this->sendRequest($endpoint_name, 'v2/account', $data, 'POST');

        if ($res) {
            $this->connected = true;
            return true;
        } else {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
    }


    public function preOperationCheck()
    {
        if ($this->connected) {
            return true;
        }

        if (empty($this->token) || $this->isTokenExpired()) {
            if ( !$this->getNewToken() ) {
                return false;
            }
        }

        return true;
    }


    public function isConnected()
    {
        return $this->connected;
    }


    public function getNewToken()
    {
        $endpoint_name = 'v2/token';

        if (empty($this->clientId) || empty($this->clientSecret)) {
            $this->nullClientLogin();
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
        $res = $this->sendRequest($endpoint_name, 'v2/token', array(
            "clientId" => $this->clientId,
            "clientSecret" => $this->clientSecret,
        ));

        if ($res && $res->http_code == 200 && !empty($res->result->token) && !empty($res->result->expiresIn)) {
            $this->connected = true;
            $this->token = $res->result->token;
            $this->tokenExpireTime = time() + $res->result->expiresIn;
            $this->updateParentClassSettings();
            return $this->token;
        } else {
            $this->connected = false;
            if (!empty($res->http_code) && $res->http_code == 401) {
                $this->nullClientLogin();
            }
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
    }


    protected static function usersSortFunc($a, $b)
    {
        if ($a->alias == $b->alias) {
            return 0;
        }
        return ($a->alias < $b->alias) ? -1 : 1;
    }


    protected static function alphabeticallSortFunc($a, $b){
        return strcasecmp($a->alias, $b->alias);
    }


    public function getUsersList($email, $password, $otpToken)
    {
        $endpoint_name = 'v2/user/accounts';

        $res = $this->sendRequest('v2/token', 'v2/token', array(
            "clientId" => $this->clientId_default,
            "clientSecret" => $this->clientSecret_default,
        ));

        if ($res && $res->http_code == 200 && !empty($res->result->token) && !empty($res->result->expiresIn)) {
            $requestOptions = array(
                "email" => $email,
                "password" => $password
            );
            if (!empty($otpToken)) {
                $requestOptions['otpToken'] = $otpToken;
            }

            $res = $this->sendRequest($endpoint_name, 'v2/user/accounts', $requestOptions, 'POST', $res->result->token);
            if($res){
                if($res->http_code == 417){
                    return array(
                        "isOtpToken" => true
                    );
                }else if($res->http_code == 200){
                    if(!empty($res->result) && is_array($res->result)){

                        uasort($res->result, array('SirvAPIClient', 'usersSortFunc'));
                        $res->result = array_values($res->result);
                        return $res->result;
                    }
                }else{
                    //return http code issue and error message
                }
            }
        }

        return false;
    }


    public function getFolderOptions($filename)
    {
        $endpoint_name = 'v2/files/options';

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/options?filename=/'.rawurlencode($filename).'&withInherited=true',
            array(),
            'GET'
        );
        if ($res && $res->http_code == 200) {
            return $res->result;
        } else {
            return false;
        }
    }


    public function getFileStat($filename)
    {
        $endpoint_name = 'v2/files/stat';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/stat?filename=/'. $filename,
            array(),
            'GET'
        );

        if ($res && $res->http_code == 200) {
            return $res->result;
        } else {
            return false;
        }
    }


    public function getProfiles()
    {
        $endpoint_name = 'v2/files/readdir';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/readdir?dirname=/Profiles',
            array(),
            'GET'
        );

        if ($res && $res->http_code == 200) {
            return $res->result;
        } else {
            return false;
        }
    }


    public function setFolderOptions($filename, $options)
    {
        $endpoint_name = 'v2/files/options';

        $res = $this->sendRequest(
            $endpoint_name,
            'v2/files/options?filename=/'.rawurlencode($filename),
            $options,
            'POST'
        );
        return ($res && $res->http_code == 200);
    }


    public function registerAccount($email, $password, $firstName, $lastName, $alias)
    {
        $endpoint_name = 'v2/account';

        $res = $this->sendRequest('v2/token', 'v2/token', array(
            "clientId" => $this->clientId_default,
            "clientSecret" => $this->clientSecret_default,
        ));

        if ($res && $res->http_code == 200 && !empty($res->result->token) && !empty($res->result->expiresIn)) {
            $res = $this->sendRequest(
                $endpoint_name,
                'v2/account', array(
                    "email" => $email,
                    "password" => $password,
                    "firstName" => $firstName,
                    "lastName" => $lastName,
                    "alias" => $alias,
                ),
                'PUT', $res->result->token);

            if ($res && $res->http_code == 200) {
                return true;
            } else {
                return false;
            }
        }
    }


    public function setupClientCredentials($token)
    {
        $endpoint_name = 'v2/rest/credentials';
        $response = array("status" => false);

        $endpoint_response = $this->sendRequest($endpoint_name, 'v2/rest/credentials', array(), 'GET', $token);
        if (
            $endpoint_response && $endpoint_response->http_code == 200
            && !empty($endpoint_response->result->clientId)
            && !empty($endpoint_response->result->clientSecret))
        {
            $this->clientId = $endpoint_response->result->clientId;
            $this->clientSecret = $endpoint_response->result->clientSecret;
            $this->getNewToken();
            $this->updateParentClassSettings();

            $response['status'] = true;

        } else {
            $response['status'] = false;
            $response['error'] = isset($endpoint_response->error) ? $endpoint_response->error : "Error during request to Sirv API: $endpoint_name";
        }

        return $response;
    }


    public function setupS3Credentials($email = '')
    {
        $endpoint_name = 'v2/account/users';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest($endpoint_name, 'v2/account/users', array(), 'GET');

        if ($res && $res->http_code == 200 && !empty($res->result) && is_array($res->result) && count($res->result)) {
            $res_user = false;

            foreach ($res->result as $user) {
                $tmp_res = $this->sendRequest('v2/user', 'v2/user?userId=' . $user->userId, array(), 'GET');
                if ($tmp_res && $tmp_res->http_code == 200 && strtolower($tmp_res->result->email) == strtolower($email)) {
                    $res_user = $tmp_res;
                    break;
                }
            }

            if ($res_user && $res_user->http_code == 200 &&
                !empty($res_user->result->s3Secret) && !empty($res_user->result->email)) {
                $res_alias = $this->sendRequest('v2/account', 'v2/account', array(), 'GET');

                if ($res_alias && $res_alias->http_code == 200 &&
                    !empty($res_alias->result) && !empty($res_alias->result->alias)) {
                    $this->updateParentClassSettings(array(
                        'SIRV_ACCOUNT_NAME' => $res_alias->result->alias,
                    ));
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
            return true;
        } else {
            $this->updateParentClassSettings(array(
                'SIRV_ACCOUNT_NAME' => '',
            ));
            return false;
        }
    }


    public function updateParentClassSettings($extra_options = array())
    {
        if(function_exists('update_option')){
            update_option('SIRV_CLIENT_ID', $this->clientId);
            update_option('SIRV_CLIENT_SECRET', $this->clientSecret);
            update_option('SIRV_TOKEN', $this->token);
            update_option('SIRV_TOKEN_EXPIRE_TIME', $this->tokenExpireTime);
            if (count($extra_options)){
                foreach ($extra_options as $option => $value) {
                    update_option($option, $value);
                }
            }
        }
        return true;
    }


    public function nullClientLogin()
    {
        $this->clientId = '';
        $this->clientSecret = '';
        $this->updateParentClassSettings(array(
            'SIRV_ACCOUNT_NAME' => '',
        ));
    }


    public function nullToken()
    {
        $this->token = '';
        $this->tokenExpireTime = 0;
    }


    public function isTokenExpired()
    {
        return $this->tokenExpireTime < time();
    }


    public function getAccountInfo()
    {
        $endpoint_name = 'v2/account';

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $result = $this->sendRequest($endpoint_name, 'v2/account', array(), 'GET');

        if (!$result || empty($result->result) || $result->http_code != 200 || empty($result->result)) {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }

        return $result->result;
    }


    public function getStorageInfo()
    {
        $storageInfo = array(
            'storage'   => array(),
            'plan'      => array(),
            'traffic'   => array(),
            'limits'    => array(),
        );

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            //TODO? add error message
            return $storageInfo;
        }

        $storage = $this->sendRequest('v2/account/storage', 'v2/account/storage', array(), 'GET');
        $billing = $this->sendRequest('v2/billing/plan', 'v2/billing/plan', array(), 'GET');
        $limits = $this->sendRequest('v2/account/limits', 'v2/account/limits', array(), 'GET');

        if ( $storage->http_code == 200 && ! empty($storage->result) ) {
            $storage->result->plan = (int) $this->getPlanValue($storage->result->plan) + (int) $this->getPlanValue($storage->result->extra);
            $storage->result->used = (int) $this->getPlanValue($storage->result->used);

            $storageInfo['storage'] = array(
                'allowance' => $storage->result->plan,
                'allowance_text' => Utils::getFormatedFileSize($storage->result->plan),
                'used' => $storage->result->used,
                'available' => $storage->result->plan - $storage->result->used,
                'available_text' => Utils::getFormatedFileSize($storage->result->plan - $storage->result->used),
                'available_percent' => number_format(($storage->result->plan - $storage->result->used) / $storage->result->plan * 100, 2, '.', ''),
                'used_text' => Utils::getFormatedFileSize($storage->result->used),
                'used_percent' => number_format($storage->result->used / $storage->result->plan * 100, 2, '.', ''),
                'files' => $this->getPlanValue($storage->result->files),
            );
        }

        if ( $billing->http_code == 200 && ! empty($billing->result) ) {
            $billing->result->dateActive = preg_replace(
                '/.*([0-9]{4}\-[0-9]{2}\-[0-9]{2}).*/ims',
                '$1',
                $billing->result->dateActive
            );

            $planEnd = strtotime('+30 days', strtotime($billing->result->dateActive));
            $now = time();

            $datediff = (int) round(($planEnd - $now) / (60 * 60 * 24));

            $until = ($planEnd > $now) ? ' (' . $datediff . ' day' . ($datediff > 1 ? 's' : '') . ' left)' : '';

            if ($planEnd < $now) {
                $until = '';
            }

            $storageInfo['plan'] = array(
                'name' => $billing->result->name,
                'trial_ends' => preg_match('/trial/ims', $billing->result->name)
                    ? 'until ' . date("j F", strtotime('+30 days', strtotime($billing->result->dateActive))) . $until
                    : '',
                'storage' => $billing->result->storage,
                'storage_text' => Utils::getFormatedFileSize($billing->result->storage),
                'dataTransferLimit' => isset($billing->result->dataTransferLimit) ? $billing->result->dataTransferLimit : '',
                'dataTransferLimit_text' => isset($billing->result->dataTransferLimit) ? Utils::getFormatedFileSize($billing->result->dataTransferLimit) : '&#8734',
            );

            $storageInfo['traffic'] = array(
                'allowance' => isset($billing->result->dataTransferLimit) ? $billing->result->dataTransferLimit : '',
                'allowance_text' => isset($billing->result->dataTransferLimit) ? Utils::getFormatedFileSize($billing->result->dataTransferLimit) : '&#8734',
            );

            $dates = array(
                'This month' => array(
                    date("Y-m-01"),
                    date("Y-m-t"),
                ),
                date("F Y", strtotime("first day of -1 month")) => array(
                    date("Y-m-01", strtotime("first day of -1 month")),
                    date("Y-m-t", strtotime("last day of -1 month")),
                ),
                date("F Y", strtotime("first day of -2 month")) => array(
                    date("Y-m-01", strtotime("first day of -2 month")),
                    date("Y-m-t", strtotime("last day of -2 month")),
                ),
                date("F Y", strtotime("first day of -3 month")) => array(
                    date("Y-m-01", strtotime("first day of -3 month")),
                    date("Y-m-t", strtotime("last day of -3 month")),
                ),
            );

            $dataTransferLimit = isset($billing->result->dataTransferLimit) ? $billing->result->dataTransferLimit : PHP_INT_MAX;

            $count = 0;
            foreach ($dates as $label => $date) {
                $traffic = $this->sendRequest('v2/stats/http', 'v2/stats/http?from=' . $date[0] . '&to=' . $date[1], array(), 'GET');

                if ( $traffic->http_code == 200 && ! empty($traffic->result) ) {
                    $traffic = (array) $traffic->result;

                    $storageInfo['traffic']['traffic'][$label]['size'] = 0;
                    $storageInfo['traffic']['traffic'][$label]['order'] = $count++;

                    if (count($traffic)) {
                        foreach ($traffic as $v) {
                            $storageInfo['traffic']['traffic'][$label]['size'] += (isset($v->total->size)) ? $v->total->size : 0;
                        }
                    }

                    $storageInfo['traffic']['traffic'][$label]['percent'] = number_format( $storageInfo['traffic']['traffic'][$label]['size'] / $dataTransferLimit * 100, 2, '.', '' );
                    $storageInfo['traffic']['traffic'][$label]['percent_reverse'] = number_format( 100 - $storageInfo['traffic']['traffic'][$label]['size'] / $dataTransferLimit * 100, 2, '.', '' );
                    $storageInfo['traffic']['traffic'][$label]['size_text'] = Utils::getFormatedFileSize($storageInfo['traffic']['traffic'][$label]['size']);
                }
            }
        }

        if ( $limits->http_code == 200 && ! empty($limits->result) ) {
            $storageInfo['limits'] = $limits->result;
            $storageInfo['limits'] = (array) $storageInfo['limits'];
            foreach ($storageInfo['limits'] as $type => $value) {
                $storageInfo['limits'][$type] = (array) $value;
                $value = (array) $value;
                $storageInfo['limits'][$type]['reset_timestamp'] = (int)$value['reset'];
                $storageInfo['limits'][$type]['reset_str'] = date('H:i:s e', $value['reset']);
                $storageInfo['limits'][$type]['count_reset_str'] = $this->calcTime((int) $value['reset']);
                $storageInfo['limits'][$type]['used'] = $value['count'] == 0 || $value['limit'] == 0 ? 0 : (round($value['count'] / $value['limit'] * 10000) / 100) . '%';
                $storageInfo['limits'][$type]['type'] = $type;
            }
        }

        return $storageInfo;
    }


    protected function getPlanValue($planKey){
        return isset($planKey) ? $planKey : 1;
    }


    public function calcTime($end){
        $mins = round(($end - time())/60);

        return "$mins minutes";
    }


    public function getContent($path='/', $continuation='')
    {
        $endpoint_name = 'v2/files/readdir';

        $preCheck = $this->preOperationCheck();
            if (!$preCheck) {
                return false;
            }

            $params = $continuation !== ''
                ? 'dirname='.$path.'&continuation='.$continuation
                : 'dirname='.$path;

        $content = $this->sendRequest($endpoint_name, 'v2/files/readdir?' . $params, array(), 'GET');
        if (!$content || $content->http_code != 200) {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }

        return $content->result;
    }


    public function getLastResponse()
    {
        return $this->lastResponse;
    }


    protected function format_muted_data($muted_data)
    {
        $muted = array();

        if ( !is_array($muted_data) || empty($muted_data)) return $muted;

        foreach ($muted_data as $mute_endpoint) {
            $endpoint_name = str_replace('_transient_sirv_api_', '', $mute_endpoint['endpoint']);
            $mute_expired_at = $mute_endpoint['expired_at'];
            $muted[$endpoint_name] = (int) $mute_expired_at;
        }

        return $muted;
    }


    public function setMuteRequest($endpoint, $expired_at_timestamp, $expired_at_in_seconds)
    {
        set_transient("sirv_api_$endpoint", $expired_at_timestamp, $expired_at_in_seconds);
    }


    public function is_muted($endpoint){
        $expired_at = get_transient("sirv_api_$endpoint");

        if ( !isset($expired_at) || false === $expired_at ) return false;

        $status =  (int) $expired_at > time();

        if ( $status ) $this->mute_endpoint_expired_at = (int) $expired_at;

        return $status;
    }


    public function getAllMuted()
    {
        global $wpdb;

        $res = $wpdb->get_results("SELECT option_name as endpoint, option_value as expired_at FROM $wpdb->options WHERE option_name LIKE '_transient_sirv_api_%'", ARRAY_A);

        return $this->format_muted_data($res);
    }


    private function sendRequest($endpoint_name, $url, $data, $method = 'POST', $token = '', $headers = null, $isFile = false)
    {
        $error = NULL;
        $response = (object) array();

        if ( $this->is_muted($endpoint_name) ) {
            $response->error = 'API usage limit reached';
            $response->endpoint_name = $endpoint_name;
            $response->mute_expired_at = $this->mute_endpoint_expired_at;
            $response->http_code = 429;
            $response->http_code_text = $this->get_http_code_text(429);
            $response->result = array();

            $this->lastResponse = $response;

            return $response;
        }

        if ( is_null($headers) ) $headers = array();

        if ( ! empty($token) ) {
            $headers['Authorization'] = "Bearer " . ((!empty($token)) ? $token : $this->token);
        } else {
            $headers['Authorization'] = "Bearer " . $this->token;
        }
        if ( ! array_key_exists('Content-Type', $headers) ) $headers['Content-Type'] = "application/json";

        foreach ($headers as $k => $v){
            $headers[$k] = "$k: $v";
        }

        $referer = Utils::get_site_referer();
        $current_page_url = Utils::get_current_page_url();

        $headers["Referer"] = "Referer: $referer";
        $headers["X-SIRV-CURRENT-PAGE-URL"] = "X-SIRV-CURRENT-PAGE-URL: $current_page_url";
        $headers["X-SIRV-INITIATOR"] = "X-SIRV-INITIATOR: api sendRequest";

        //$fp = fopen(dirname(__FILE__) . '/curl_errorlog.txt', 'w');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseURL . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_ACCEPT_ENCODING => "",
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => (!$isFile) ? json_encode($data) : $data,
            CURLOPT_HTTPHEADER => $headers,
            //CURLOPT_MAXREDIRS => 10,
            //CURLOPT_CONNECTTIMEOUT => 2,
            //CURLOPT_TIMEOUT => 30,
            //CURLOPT_SSL_VERIFYPEER => false,
            //CURLINFO_HEADER_OUT => IS_DEBUG ? true : false,
            //CURLOPT_VERBOSE => true,
            //CURLOPT_STDERR => $fp,
        ));

        $result = curl_exec($curl);
        $info = curl_getinfo($curl);
        $error = curl_error($curl);

        if( $error ){
            global $sirv_gbl_sirv_logger;

            $sirv_gbl_sirv_logger->error($this->baseURL . $url, 'request url')->filename('network_errors.log')->write();
            $sirv_gbl_sirv_logger->error($error, 'error message')->filename('network_errors.log')->write();
            $sirv_gbl_sirv_logger->delimiter()->filename('network_errors.log')->write();
        }

        if(IS_DEBUG){
            global $sirv_gbl_sirv_logger;

            $sirv_gbl_sirv_logger->info($result, '$result')->filename('network.log')->write();
            $sirv_gbl_sirv_logger->info($info, '$info')->filename('network.log')->write();
            $sirv_gbl_sirv_logger->delimiter()->filename('network.log')->write();
        }

        $res_object = json_decode($result);

        if ($this->isLimitRequestReached($res_object, $info)) {
            $expired_at_timestamp = time() + HOUR_IN_SECONDS;
            $expired_at_in_seconds = HOUR_IN_SECONDS;

            $errorMessage = $this->getLimitRequestReachedMessage($res_object, $info);

            if(preg_match('/stop sending requests until ([0-9]{4}\-[0-9]{2}\-[0-9]{2}.*?\([a-z]{1,}\))/ims', $errorMessage, $m)) {
                $expired_at_timestamp = strtotime($m[1]);
                $expired_at_in_seconds = $expired_at_timestamp - time();
            }

            $this->setMuteRequest($endpoint_name, $expired_at_timestamp, $expired_at_in_seconds);
        }

        $info['http_code_text'] = $this->get_http_code_text($info['http_code']);
        $response = (object) $info;
        $response->result = $res_object;

        //TODO: if result html then return result_txt or empty
        $response->result_txt = trim($result);
        $response->error = $error;

        $this->lastResponse = $response;

        curl_close($curl);
        //fclose($fp);

        return $response;
    }


    protected function isLimitRequestReached($result, $info){
        if ( $info['http_code'] == 429 ) return true;

        if(! empty($result) ){

            if( is_object($result) && isset($result->message) && stripos($result->message, 'rate limit exceeded') !== false ) return true;

            if( is_array($result) && isset($result[0]->error) && stripos($result[0]->error, 'rate limit exceeded') !== false ) return true;
        }


        return false;
    }


    protected function getLimitRequestReachedMessage($result, $info){
        if (is_object($result) && isset($result->message) && stripos($result->message, 'rate limit exceeded') !== false) return $result->message;

        if( is_array($result) && isset($result[0]->error) && stripos($result[0]->error, 'rate limit exceeded') !== false ) return $result[0]->error;

        return "Error message did not receive.";
    }


    protected function get_http_code_text($code){
        $http_status_codes = array(
            100 => 'Informational: Continue',
            101 => 'Informational: Switching Protocols',
            102 => 'Informational: Processing',
            200 => 'Successful: OK',
            201 => 'Successful: Created',
            202 => 'Successful: Accepted',
            203 => 'Successful: Non-Authoritative Information',
            204 => 'Successful: No Content',
            205 => 'Successful: Reset Content',
            206 => 'Successful: Partial Content',
            207 => 'Successful: Multi-Status',
            208 => 'Successful: Already Reported',
            226 => 'Successful: IM Used',
            300 => 'Redirection: Multiple Choices',
            301 => 'Redirection: Moved Permanently',
            302 => 'Redirection: Found',
            303 => 'Redirection: See Other',
            304 => 'Redirection: Not Modified',
            305 => 'Redirection: Use Proxy',
            306 => 'Redirection: Switch Proxy',
            307 => 'Redirection: Temporary Redirect',
            308 => 'Redirection: Permanent Redirect',
            400 => 'Client Error: Bad Request',
            401 => 'Client Error: Unauthorized',
            402 => 'Client Error: Payment Required',
            403 => 'Client Error: Forbidden',
            404 => 'Client Error: Not Found',
            405 => 'Client Error: Method Not Allowed',
            406 => 'Client Error: Not Acceptable',
            407 => 'Client Error: Proxy Authentication Required',
            408 => 'Client Error: Request Timeout',
            409 => 'Client Error: Conflict',
            410 => 'Client Error: Gone',
            411 => 'Client Error: Length Required',
            412 => 'Client Error: Precondition Failed',
            413 => 'Client Error: Request Entity Too Large',
            414 => 'Client Error: Request-URI Too Long',
            415 => 'Client Error: Unsupported Media Type',
            416 => 'Client Error: Requested Range Not Satisfiable',
            417 => 'Client Error: Expectation Failed',
            418 => 'Client Error: I\'m a teapot',
            419 => 'Client Error: Authentication Timeout',
            422 => 'Client Error: Unprocessable Entity',
            423 => 'Client Error: Locked',
            424 => 'Client Error: Failed Dependency',
            425 => 'Client Error: Unordered Collection',
            426 => 'Client Error: Upgrade Required',
            428 => 'Client Error: Precondition Required',
            429 => 'Client Error: Too Many Requests',
            431 => 'Client Error: Request Header Fields Too Large',
            444 => 'Client Error: No Response',
            449 => 'Client Error: Retry With',
            450 => 'Client Error: Blocked by Windows Parental Controls',
            451 => 'Client Error: Unavailable For Legal Reasons',
            494 => 'Client Error: Request Header Too Large',
            495 => 'Client Error: Cert Error',
            496 => 'Client Error: No Cert',
            497 => 'Client Error: HTTP to HTTPS',
            499 => 'Client Error: Client Closed Request',
            500 => 'Server Error: Internal Server Error',
            501 => 'Server Error: Not Implemented',
            502 => 'Server Error: Bad Gateway',
            503 => 'Server Error: Service Unavailable',
            504 => 'Server Error: Gateway Timeout',
            505 => 'Server Error: HTTP Version Not Supported',
            506 => 'Server Error: Variant Also Negotiates',
            507 => 'Server Error: Insufficient Storage',
            508 => 'Server Error: Loop Detected',
            509 => 'Server Error: Bandwidth Limit Exceeded',
            510 => 'Server Error: Not Extended',
            511 => 'Server Error: Network Authentication Required',
            598 => 'Server Error: Network read timeout error',
            599 => 'Server Error: Network connect timeout error',
        );

        $code = (int) $code;

        if( ! in_array($code, array_keys($http_status_codes)) ){
            return "Unknown http code";
        }

        return $http_status_codes[$code];
    }
}
