<?php

namespace Laravel\LaravelInstaller\Service;

class EnvatoService
{
    private $apiUrl = '';
    private $headerKey = '';

    public function __construct()
    {
        $this->apiUrl = config('installer.api_url') ? config('installer.api_url') : 'https://api.envato.com';
        $this->headerKey = config('installer.headerkeys') ? config('installer.headerkeys') : 'Bearer sLAEuLH83WuGmg8iJGDSxQiavZ2TF1ba';
    }

    public function apiCall($endPoint, $requestData = null, $method = 'GET')
    {
        try {
            $header = array();
            $header[] = 'Accept: application/json';
            $header[] = 'Authorization:' . $this->headerKey;

            $api_url = $this->apiUrl . $endPoint;

            // If the method is POST and requestData is provided, prepare the POST request
            if (strtoupper($method) === 'POST' && !empty($requestData)) {
                $postData = json_encode($requestData);
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            } else {
                // If the method is GET or no requestData is provided, make a GET request
                if (!empty($requestData)) {
                    // If you want to include GET parameters in the URL, you can add them here
                    $api_url .= '?' . http_build_query($requestData);
                }
                $ch = curl_init($api_url);
            }

            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            $responseData = curl_exec($ch);
            // return ['success' => true, 'message' => __('Api request failed'), 'data' => []];
            if ($responseData !== false) {
                $result = json_decode($responseData, true); // Decode JSON into an associative array
                $item_id = $result['item']['id'] ?? 0;
                $expectedItemId = function_exists('expectedItemId') ? expectedItemId() : null;

                if ($expectedItemId == $item_id) {
                    $response = ['success' => true, 'message' => __('Purchase code verified.'), 'data' => $result['data'] ?? []];
                } else {
                    $response = ['success' => false, 'message' => __('Invalid purchase codes.'), 'data' => []];
                }
            } else {
                $response = ['success' => false, 'message' => __('Api request failed'), 'data' => []];
            }

            curl_close($ch);
        } catch (\Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }

        return $response;
    }

    // check envato purchase code
    public function checkEnvatoPurchaseCode($code)
    {
        if (!empty($code)) {
            $response = $this->apiCall('/v3/market/author/sale?code=' . $code, [], 'GET');
            return $response;
        } else {
            return ['success' => false, 'message' => __('Code is missing')];
        }
    }

    // get version list
    public function getProductVersion($code)
    {
        if (!empty($code)) {
            $response = $this->apiCall('/api/get-versions?code=' . $code, [], 'GET');
            return $response;
        } else {
            return ['success' => false, 'message' => __('Code is missing')];
        }
    }

    // download updated code
    public function downloadUpdate($code, $version)
    {
        if (!empty($code)) {
            $response = $this->apiCall('/api/download-version?code=' . $code . '&version=' . $version, [], 'GET');
            return $response;
        } else {
            return ['success' => false, 'message' => __('Code is missing')];
        }
    }

    // check client
    public function checkExistClient($code)
    {
        if (!empty($code)) {
            $response = $this->apiCall('/api/check-client?code=' . $code, [], 'GET');
            return $response;
        } else {
            return ['success' => false, 'message' => __('Code is missing')];
        }
    }
}
