<?php
/**
 * OpenRouter API Client Library (Basic Implementation)
 */
class OpenRouter {
    private $api_key;
    private $api_base_url = 'https://openrouter.ai/api/v1'; // Default API base URL

    /**
     * Constructor.
     *
     * @param string $api_key Your OpenRouter API key.
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Generates text content using a specified model.
     *
     * @param string $prompt The prompt to send to the model.
     * @param string $model The model to use (e.g., "openai/gpt-3.5-turbo").
     * @param array $params Additional parameters for the request (e.g., max_tokens, temperature).
     * @return array Decoded JSON response from the API or error array.
     */
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
            // 'max_tokens' => 1024, // Example: Max tokens can be adjusted
            // 'temperature' => 0.7, // Example: Temperature can be adjusted
        ];

        $request_data = array_merge($default_params, $params);

        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            // 'HTTP-Referer: YOUR_SITE_URL', // Recommended by OpenRouter
            // 'X-Title: YOUR_APP_NAME',    // Recommended by OpenRouter
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // It's good practice to set a timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 seconds to connect
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 seconds for the entire request


        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            // Log cURL error
            // error_log('OpenRouter API cURL Error: ' . $curl_error);
            return ['error' => 'cURL Error: ' . $curl_error];
        }

        $decoded_response = json_decode($response, true);

        if ($http_code >= 400 || isset($decoded_response['error'])) {
            // Log API error
            // error_log('OpenRouter API Error: HTTP ' . $http_code . ' - Response: ' . $response);
             $error_message = 'API Request Failed with HTTP status ' . $http_code;
            if (isset($decoded_response['error']['message'])) {
                $error_message .= ': ' . $decoded_response['error']['message'];
            } elseif ($response) {
                $error_message .= '. Response: ' . $response;
            }
            return ['error' => $error_message, 'status_code' => $http_code, 'response_body' => $decoded_response];
        }

        // Successfully got a response, extract the content
        // The structure might vary based on the model or if using streaming.
        // For chat completions, it's typically $decoded_response['choices'][0]['message']['content']
        if (isset($decoded_response['choices'][0]['message']['content'])) {
             // Return the whole response for now, so the controller can decide what to pick
            return $decoded_response;
        } else {
            // Unexpected response structure
            // error_log('OpenRouter API Error: Unexpected response structure - ' . $response);
            return ['error' => 'Unexpected API response structure.', 'response_body' => $decoded_response];
        }
    }

    /**
     * Parses the API response to extract specific fields for product update.
     * This is a placeholder and needs to be adapted based on the actual AI response format
     * and how you structure your prompts.
     *
     * @param array $api_response The decoded JSON response from the API.
     * @param array $fields_to_update The list of fields that were requested to be updated.
     * @return array Parsed data for product update.
     */
    public function parseProductUpdateResponse($api_response_content, $fields_to_update) {
        $parsed_data = [];
        // Log the raw content received for parsing
        // error_log('OpenRouter Library: Attempting to parse AI content: ' . $api_response_content);

        // Attempt to decode if the content is a JSON string
        // Sometimes AI might wrap JSON in ```json ... ```, so try to strip that.
        $cleaned_content = $api_response_content;
        if (strpos(trim($cleaned_content), '```json') === 0) {
            $cleaned_content = preg_replace('/^```json\s*/', '', $cleaned_content);
            $cleaned_content = preg_replace('/\s*```$/', '', $cleaned_content);
        }

        $potential_json = json_decode($cleaned_content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($potential_json)) {
            // It's a JSON object, directly use it
            // error_log('OpenRouter Library: Successfully parsed JSON response.');
            foreach ($fields_to_update as $field) {
                if (array_key_exists($field, $potential_json)) { // Use array_key_exists to handle null values correctly
                    $parsed_data[$field] = $potential_json[$field];
                } else {
                    // Log if a requested field is missing in the JSON response
                    // error_log("OpenRouter Library: Requested field '{$field}' not found in AI JSON response.");
                }
            }
            if (empty($parsed_data)) {
                // Log if JSON was valid but no requested fields were found
                // error_log('OpenRouter Library: JSON parsed, but no requested fields found in the JSON object. AI Response: ' . $cleaned_content);
                $parsed_data['parsing_error'] = 'AI returned valid JSON, but it did not contain any of the requested fields: ' . implode(', ', $fields_to_update) . '. Check AI prompt and response format.';
            }
        } else {
            // It's not a JSON string, or JSON is invalid.
            $json_error_message = json_last_error_msg();
            // error_log('OpenRouter Library: Failed to parse AI response as JSON. Error: ' . $json_error_message . '. Raw content: ' . $api_response_content);

            // Provide a more specific error message
            $parsed_data['parsing_error'] = 'AI response was not valid JSON or could not be parsed. JSON Error: ' . $json_error_message . '. Please ensure the AI is configured to return a valid JSON object as instructed in the prompt. Received: ' . substr($api_response_content, 0, 200) . '...';

            // Fallback (optional, and generally not recommended if strict JSON is expected)
            // if (count($fields_to_update) == 1 && in_array('description', $fields_to_update)) {
            //     $parsed_data['description'] = $api_response_content; // Assign full content to description as a last resort
            //     $parsed_data['parsing_warning'] = 'Assigned full AI response to description due to parsing failure.';
            //     unset($parsed_data['parsing_error']); // Remove error if we have a fallback
            // }
        }

        // Log the final parsed data or parsing error
        // if(isset($parsed_data['parsing_error'])){
        //     error_log('OpenRouter Library: Final parsing result: ERROR - ' . $parsed_data['parsing_error']);
        // } else {
        //     error_log('OpenRouter Library: Final parsing result: SUCCESS - ' . json_encode($parsed_data));
        // }

        return $parsed_data;
    }

    // Basic slugify function (can be improved)
    // private function slugify($text) {
    //     $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    //     $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    //     $text = preg_replace('~[^-\w]+~', '', $text);
    //     $text = trim($text, '-');
    //     $text = preg_replace('~-+~', '-', $text);
    //     $text = strtolower($text);
    //     if (empty($text)) {
    //         return 'n-a';
    //     }
    //     return $text;
    // }
}
