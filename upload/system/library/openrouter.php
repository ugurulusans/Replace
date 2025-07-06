<?php
/**
 * OpenRouter API Client Library (Basic Implementation)
 * For OpenCart 3.x
 */
class OpenRouter {
    private $api_key;
    private $api_base_url = 'https://openrouter.ai/api/v1';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function generateText($prompt, $model = 'openai/gpt-3.5-turbo', $params = array()) {
        if (empty($this->api_key)) {
            return ['error' => 'API key is not set.'];
        }

        $endpoint = $this->api_base_url . '/chat/completions';

        $default_params = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
        ];

        $request_data = array_merge($default_params, $params);

        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return ['error' => 'cURL Error: ' . $curl_error];
        }

        $decoded_response = json_decode($response, true);

        if ($http_code >= 400 || isset($decoded_response['error'])) {
             $error_message = 'API Request Failed with HTTP status ' . $http_code;
            if (isset($decoded_response['error']['message'])) {
                $error_message .= ': ' . $decoded_response['error']['message'];
            } elseif ($response) {
                $error_message .= '. Response: ' . $response;
            }
            return ['error' => $error_message, 'status_code' => $http_code, 'response_body' => $decoded_response];
        }

        if (isset($decoded_response['choices'][0]['message']['content'])) {
            return $decoded_response;
        } else {
            return ['error' => 'Unexpected API response structure.', 'response_body' => $decoded_response];
        }
    }

    public function parseProductUpdateResponse($api_response_content, $fields_to_update) {
        $parsed_data = [];
        $cleaned_content = $api_response_content;
        if (strpos(trim($cleaned_content), '```json') === 0) {
            $cleaned_content = preg_replace('/^```json\s*/', '', $cleaned_content);
            $cleaned_content = preg_replace('/\s*```$/', '', $cleaned_content);
        }

        $potential_json = json_decode($cleaned_content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($potential_json)) {
            foreach ($fields_to_update as $field) {
                if (array_key_exists($field, $potential_json)) {
                    $parsed_data[$field] = $potential_json[$field];
                }
            }
            if (empty($parsed_data)) {
                $parsed_data['parsing_error'] = 'AI returned valid JSON, but it did not contain any of the requested fields: ' . implode(', ', $fields_to_update) . '. Check AI prompt and response format.';
            }
        } else {
            $json_error_message = json_last_error_msg();
            $parsed_data['parsing_error'] = 'AI response was not valid JSON or could not be parsed. JSON Error: ' . $json_error_message . '. Please ensure the AI is configured to return a valid JSON object as instructed in the prompt. Received: ' . substr($api_response_content, 0, 200) . '...';
        }
        return $parsed_data;
    }
}
