<?php
class Livevox
{
    public $curlUrl = ''; //API URL
    public $sessionId = '';
    public $responseType = 'array';
    public $accessToken = ''; //Default Access Token can be set
    public $config = [];
    private $clientName = '';
    private $userName = '';
    private $password = '';

    public function __construct($clientName = '', $userName = '', $password = ''){

        if($this->sessionId != ''){
            $isSessionValid = $this->isSessionValid($this->sessionId);
            if(!$isSessionValid){
                $this->fetchConfig();
            }
        }else{
            $this->clientName = $clientName;
            $this->userName = $userName;
            $this->password = $password;
            $this->fetchConfig();
        }
        
    }

    public function fetchConfig(){
        /* 
            Description: Checking if session is valid.
            Case 1: If config set in config.json file then we fetch and check if session is valid.
            Case 2: If config is not set in config.json then we call Login method to login and store the session ID in the config.json file.
        */
        $file_content = file_get_contents('config.json');
        if($file_content && $file_content != ''){
            $this->config = json_decode($file_content, true);
            $this->sessionId = $this->config['sessionId'];

            $isSessionValid = $this->isSessionValid($this->sessionId);
            if(!$isSessionValid){
                $status = $this->login();
            }
        }else{
            $status = $this->login();
            if($status){
                file_put_contents('config.json', ''); //creating an empty config.json file.
                $file_content = file_get_contents('config.json');
                if($file_content){
                    $this->config = json_decode($file_content, true);
                    $this->sessionId = $this->config['sessionId'];
                }
            }else{
                return $status;
            }
            
        }
        return true;
    }

    public function login(){
        $endPoint = 'session/login';
        $method = 'POST';
        $fieldsParams = [
            'clientName' => $this->clientName,
            'userName' => $this->userName,
            'password' => $this->password
        ];

        $headers = [
            "LV-Access: " . $this->accessToken,
        ];

        $others = [
            'responseType' => '',
            'queryParamsAppend' => false,
            // 'printHeaders' => true,
        ];

        $response = $this->sendRequest($method, $endPoint, [], $fieldsParams, $headers, $others);
        // print_r($response);
        if(!empty($response) && !isset($response['code'])){
            file_put_contents('config.json', json_encode($response));
            $this->sessionId = $response['sessionId'];
            return true;
        }else{
            return $response;
        }
    }

    public function isSessionValid($sessionId){
        $endPoint = 'session/validate/' . $sessionId;
        $method = 'GET';
        $other = [
            'responseType' => '',
            'queryParamsAppend' => false,
            'checkHttpCode' => true
        ];

        $headers = [
            'LV-Access:'  . $this->accessToken,
        ];

        $response = $this->sendRequest($method, $endPoint, [], [], $headers, $other);
        if($response == '404'){
            return false;
        }else{
            return true;
        }
    }

    public function getCallRecordings($startDate, $endDate, $filter = []){
        $endPoint = 'reporting/standard/callRecording';
        $method = 'POST';

        $fieldsParams = [
            'startDate' => $this->formatDateTime($startDate),
            'endDate' => $this->formatDateTime($endDate),
            // 'sortBy' => 'AGENT',
        ];

        if(isset($filter)){
            if(isset($filter['sortBy']) && $filter['sortBy'] != ''){
                $fieldsParams['sortBy'] = $filter['sortBy'];
            }

            if(isset($filter['agent']) && $filter['agent'] != ''){
                $fieldsParams['filter']['agent'] = $filter['agent'];
            }

            if(isset($filter['account']) && $filter['account'] != ''){
                $fieldsParams['filter']['account'] = $filter['account'];
            }

            if(isset($filter['phoneDialed']) && $filter['phoneDialed'] != ''){
                $fieldsParams['filter']['phoneDialed'] = $filter['phoneDialed'];
            }

            if(isset($filter['originalAccountNumber']) && $filter['originalAccountNumber'] != ''){
                $fieldsParams['filter']['originalAccountNumber'] = $filter['originalAccountNumber'];
            }

            if(isset($filter['callCenter']) && is_array($filter['callCenter'])){
                $fieldsParams['filter']['callCenter'] = $filter['callCenter'];
            }

            if(isset($filter['service']) && is_array($filter['service'])){
                $fieldsParams['filter']['service'] = $filter['service'];
            }
        }

        $others = [
            'responseType' => '',
            'queryParamsAppend' => false,
        ];

        $headers = [
            "LV-Session: " . $this->sessionId,
        ];

        $response = $this->sendRequest($method, $endPoint, [], $fieldsParams, $headers, $others);
        return $response;
    }

    public function getCampaigns($count = 1000, $offset = 0){
        $endPoint = 'campaign/campaigns?count=' . $count . '&offset=' . $offset;
        $method = 'GET';

        $others = [
            'responseType' => '',
            'queryParamsAppend' => false,
        ];

        $headers = [
            "LV-Session: " . $this->sessionId,
        ];

        $response = $this->sendRequest($method, $endPoint, [], [], $headers, $others);
        return $response;
    }

    public function getCallCenters($client = '', $count = 1000, $offset = 0){
        $endPoint = 'configuration/callCenters?count=' . $count . '&offset=' . $offset;
        if($client != ''){
            $endPoint .= '&client=' . $client;
        }else{
            $endPoint .= '&client=' . $this->config['clientId'];
        }

        $method = 'GET';

        $others = [
            'responseType' => '',
            'queryParamsAppend' => false,
            'checkHttpCode' => true
        ];

        $headers = [
            "LV-Session: " . $this->sessionId,
        ];
        
        $response = $this->sendRequest($method, $endPoint, [], [], $headers, $others);
        // $response['endPoint'] = $endPoint;
        // $response['headers'] = $headers;
        // $response['config'] = $this->config;
        return $response;
    }

    public function getAgents($client = '', $count = 1000, $offset = 0){
        $endPoint = 'configuration/agents?count=' . $count . '&offset=' . $offset;
        if($client != ''){
            $endPoint .= '&client=' . $client;
        }

        $method = 'GET';

        $others = [
            'responseType' => '',
            'queryParamsAppend' => false,
        ];

        $headers = [
            "LV-Session: " . $this->sessionId,
        ];

        $response = $this->sendRequest($method, $endPoint, [], [], $headers, $others);
        return $response;
    }

    public function getServices($callCenter, $count = 1000, $offset = 0){
        $endPoint = 'configuration/services?count=' . $count . '&offset=' . $offset . '&callCenter=' . $callCenter;
        $method = 'GET';

        $others = [
            'responseType' => '',
            'queryParamsAppend' => false,
        ];

        $headers = [
            "LV-Session: " . $this->sessionId,
        ];

        $response = $this->sendRequest($method, $endPoint, [], [], $headers, $others);
        return $response;
    }

    public function formatDateTime($date){
        $time = new DateTime($date, new DateTimeZone('America/Los_Angeles'));
        return strtotime($time->format('m/d/Y H:i:s')) * 1000; //convert to  microseconds
    }

    public function getCallRecordingById($id, $location = '') {
        $endPoint = 'compliance/recording/' . $id;
        $method = 'GET';
        $file_name = uniqid('file_') . '.mp3';
        if($location != ''){
            $file = fopen($location . '/' . $file_name, 'wb');
            $file_name = $location . '/' . $file_name;
        }else{
            $file = fopen($file_name, 'wb');
        }

        $others = [
            'responseType' => '',
            'queryParamsAppend' => false,
            'file' => $file,
            'file_name' => $file_name
        ];

        $headers = [
            "LV-Session: " . $this->sessionId,
            'Content-Type: audio/mpeg'
        ];

        $response = $this->sendRequest($method, $endPoint, [], [], $headers, $others);
        return $response;
    }
 
    private function sendRequest($method, $endPoint, $queryParams = [], $fieldsParams = [], $customHeaders = [], $other = []){
        $method = strtoupper($method);

        $queryParamsAppend = false;
        if(isset($other['queryParamsAppend'])){
            $queryParamsAppend = $other['queryParamsAppend'];
        }

        if($method == 'GET' && $queryParamsAppend){
            $queryParams = http_build_query($queryParams);
            $url = $this->curlUrl . $endPoint . '?' . $queryParams;
        }else{
            $url = $this->curlUrl . $endPoint;
        }
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $headers = array_merge($headers, $customHeaders);
        
        if(isset($other['printHeaders']) && $other['printHeaders'] == true){
            print_r($headers);
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        if($method == 'POST'){
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fieldsParams));
            if($endPoint == 'session/login'){
                curl_setopt($curl, CURLOPT_ENCODING, '');
                curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                curl_setopt($curl, CURLOPT_TIMEOUT, 0);
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            }
        }
        if(in_array('Content-Type: audio/mpeg', $headers)){
            curl_setopt($curl, CURLOPT_FILE, $other['file']); 
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($curl);
        if(isset($other['checkHttpCode']) && $other['checkHttpCode']){
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        }
        curl_close($curl);

        if(isset($other['checkHttpCode']) && $other['checkHttpCode']){
            return $httpcode;
        }

        $responseType = $other['responseType'];
        if(!isset($responseType) || empty($responseType)){
            $responseType = $this->responseType; //setting default response type;
        }

        if(in_array('Content-Type: audio/mpeg', $headers)){
            fwrite($other['file'], $response); 
            return $response = $other['file_name'];
        }

        if($responseType == 'json'){
            return $response;
        }else if($responseType == 'array'){
            return json_decode($response, true);
        }
    }
}
