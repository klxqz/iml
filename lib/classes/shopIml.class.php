<?php

class shopIml {

    protected $login;
    protected $password;
    protected $host;

    public function __construct($host, $login, $password) {
        $this->host = rtrim($host, '/');
        $this->login = $login;
        $this->password = $password;
    }

    public function sendFile($file) {
        $filename = basename($file);
        $url = $this->host . '/Inbox/' . $filename;
        $content = file_get_contents($file);
        return $this->sendRequest($url, $content, $this->login, $this->password);
    }

    public function getFilesList() {
        $url = $this->host . '/Outbox';
        $response = $this->sendRequest($url, null, $this->login, $this->password, 'get');
        $FileList = new SimpleXMLElement($response);
        $files = array();
        foreach ($FileList->fileName as $fileName) {
            $files[] = (string) $fileName;
        }
        return $files;
    }

    public function xmlToArray($xml) {
        $res = array();

        if ($xml->children()) {
            foreach ($xml->children() as $child) {
                $filed = $child->getName();
                if ($child->children()) {
                    $val = $this->xmlToArray($child);
                } else {
                    $val = (string) $child;
                }
                if (!isset($res[$filed])) {
                    $res[$filed] = $val;
                } elseif (isset($res[$filed][0])) {
                    $res[$filed][] = $val;
                } else {
                    $temp = $res[$filed];
                    $res[$filed] = array();
                    $res[$filed][] = $temp;
                    $res[$filed][] = $val;
                }
            }
        } else {
            $filed = $xml->getName();
            $res[$filed] = (string) $xml;
        }
        return $res;
    }

    public function getFile($filename) {
        $url = $this->host . '/Outbox/' . $filename;
        $response = $this->sendRequest($url, null, $this->login, $this->password, 'get');
        $xml = new SimpleXMLElement($response);
        $response_request = $this->xmlToArray($xml);
        return $response_request;
    }

    public function deleteFile($filename) {
        $url = $this->host . '/Outbox/' . $filename;
        return $this->sendRequest($url, null, $this->login, $this->password, 'get', 'DELETE');
    }

    private function sendRequest($url, $request = null, $login = null, $pass = null, $method = 'POST', $custom_request = null) {
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            throw new waException('PHP расширение cURL не доступно');
        }

        if (!($ch = curl_init())) {
            throw new waException('curl init error');
        }

        if (curl_errno($ch) != 0) {
            throw new waException('Ошибка инициализации curl: ' . curl_errno($ch));
        }

        $headers = array('Expect:', 'Content-type: text/xml', 'Content-length: ' . strlen($request));

        @curl_setopt($ch, CURLOPT_URL, $url);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($custom_request) {
            @curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $custom_request);
        }


        if ($login && $pass) {
            @curl_setopt($ch, CURLOPT_USERPWD, $login . ":" . $pass);
        }

        if ($method == 'POST') {
            @curl_setopt($ch, CURLOPT_POST, true);
            @curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        }


        $response = @curl_exec($ch);
        $app_error = null;
        if (curl_errno($ch) != 0) {
            $app_error = 'Ошибка curl: ' . curl_error($ch);
        }
        $info = curl_getinfo($ch);

        curl_close($ch);
        if ($app_error) {
            throw new waException($app_error);
        }

        switch ($info['http_code']) {
            case 200:
                //ok
                break;
            case 401:
                throw new waException('401 - Логин или пароль не верны');
                break;
            case 403:
                throw new waException('403 - Доступ запрещен');
                break;
            case 404:
                throw new waException('404 - Файл или папка не найдены');
                break;
            case 409:
                throw new waException('409 - Файл уже существует');
                break;
            case 413:
                throw new waException('413 - Слишком большой запрос');
                break;
            case 422:
                throw new waException('422 - Пустой файл(данные не найдены)');
                break;
            case 423:
                throw new waException('423 - Ошибка создания или удаления файла');
                break;
        }

        if (empty($response)) {
            throw new waException('Пустой ответ от сервера');
        }

        return $response;
    }

}
