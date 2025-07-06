<?php
class ControllerExtensionModuleAiUpdater extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/ai_updater');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        // OC3'te ayarlar genellikle doğrudan POST ile gelir ve index'te işlenir.
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) { // validateForm ayar formu için
            $this->model_setting_setting->editSetting('module_ai_updater', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/ai_updater', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Ayar formu için action URL'i (kendisine post edecek)
        $data['action_settings'] = $this->url->link('extension/module/ai_updater', 'user_token=' . $this->session->data['user_token'], true);
        // Ürün güncelleme AJAX isteği için URL
        $data['action_update_products'] = $this->url->link('extension/module/ai_updater/updateProducts', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['user_token'] = $this->session->data['user_token'];


        if (isset($this->request->post['module_ai_updater_status'])) {
            $data['module_ai_updater_status'] = $this->request->post['module_ai_updater_status'];
        } else {
            $data['module_ai_updater_status'] = $this->config->get('module_ai_updater_status');
        }

        if (isset($this->request->post['module_ai_updater_api_key'])) {
            $data['module_ai_updater_api_key'] = $this->request->post['module_ai_updater_api_key'];
        } else {
            $data['module_ai_updater_api_key'] = $this->config->get('module_ai_updater_api_key');
        }

        // Errors
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        if (isset($this->error['api_key'])) {
            $data['error_api_key'] = $this->error['api_key'];
        } else {
            $data['error_api_key'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ai_updater', $data));
    }

    // OC3 için ayar formu validasyonu
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'extension/module/ai_updater')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['module_ai_updater_status']) && $this->request->post['module_ai_updater_status']) {
            if (empty(trim($this->request->post['module_ai_updater_api_key']))) {
                $this->error['api_key'] = $this->language->get('error_api_key_required');
            }
        }
        return !$this->error;
    }

    // Ürünleri AJAX ile listelemek için metod
    public function loadProducts() {
        $this->load->language('extension/module/ai_updater');
        $json = array('products' => array());

        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = null;
        }

        $this->load->model('extension/module/ai_updater'); // Modelimizi yüklüyoruz

        $filter_data = array(
            'filter_name' => $filter_name,
            'start'       => 0,
            'limit'       => 50 // Örnek bir limit, gerekirse artırılabilir veya ayarlanabilir yapılabilir
        );

        $results = $this->model_extension_module_ai_updater->getProducts($filter_data);

        foreach ($results as $result) {
            $json['products'][] = array(
                'product_id' => $result['product_id'],
                'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }


    public function updateProducts() {
        $this->load->language('extension/module/ai_updater');
        $this->load->model('extension/module/ai_updater');
        $this->load->model('catalog/product');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            if (!$this->user->hasPermission('modify', 'extension/module/ai_updater')) {
                $json['error'] = $this->language->get('error_permission');
            } else {
                $this->model_extension_module_ai_updater->backupDatabase();
                $this->log->write('AI Updater: Database backup created.');

                if (isset($this->request->post['selected_products']) && isset($this->request->post['fields_to_update'])) {
                    $fields_to_update = $this->request->post['fields_to_update'];
                    $manual_description = isset($this->request->post['manual_description']) ? $this->request->post['manual_description'] : '';
                    $openrouter_api_key = $this->config->get('module_ai_updater_api_key');

                    if (empty($openrouter_api_key)) {
                        $json['error'] = $this->language->get('error_api_key_missing');
                    } else {
                        $this->load->library('openrouter'); // Global kütüphane olarak yüklenecek
                        $openrouter = new OpenRouter($openrouter_api_key);

                        if (empty($this->request->post['selected_products']) || !is_array($this->request->post['selected_products']) || count($this->request->post['selected_products']) !== 1) {
                            $json['error'] = 'Invalid product selection for single update.';
                        } else {
                            $product_id = (int)$this->request->post['selected_products'][0];
                            $product_info = $this->model_catalog_product->getProduct($product_id);
                            $product_descriptions = $this->model_catalog_product->getProductDescriptions($product_id);

                            if ($product_info) {
                                $current_description = '';
                                $current_name = $product_info['name'];
                                $current_meta_title = '';
                                $current_meta_description = '';
                                $current_meta_keyword = '';

                                $default_lang_id = (int)$this->config->get('config_language_id');
                                if (isset($product_descriptions[$default_lang_id])) {
                                    $current_description = $product_descriptions[$default_lang_id]['description'];
                                    $current_meta_title = $product_descriptions[$default_lang_id]['meta_title'];
                                    $current_meta_description = $product_descriptions[$default_lang_id]['meta_description'];
                                    $current_meta_keyword = $product_descriptions[$default_lang_id]['meta_keyword'];
                                } else if (!empty($product_descriptions)) {
                                    $first_desc = reset($product_descriptions);
                                    $current_description = $first_desc['description'];
                                    $current_meta_title = $first_desc['meta_title'];
                                    $current_meta_description = $first_desc['meta_description'];
                                    $current_meta_keyword = $first_desc['meta_keyword'];
                                }

                                $fields_to_update_string = implode(', ', $fields_to_update);
                                $prompt = "You are an expert SEO and e-commerce copywriter. Update the product details for an OpenCart store.\n";
                                $prompt .= "The product is: \"{$current_name}\".\n";
                                $prompt .= "Current Description: \"{$current_description}\"\n";
                                $prompt .= "Current Meta Title: \"{$current_meta_title}\"\n";
                                $prompt .= "Current Meta Description: \"{$current_meta_description}\"\n";
                                $prompt .= "Current Meta Keywords: \"{$current_meta_keyword}\"\n\n";
                                if ($manual_description) {
                                    $prompt .= "Please follow these specific instructions: \"{$manual_description}\"\n\n";
                                }
                                $prompt .= "The fields to generate new content for are: {$fields_to_update_string}.\n";
                                $prompt .= "For each field, provide the new content.\n";
                                $prompt .= "IMPORTANT: Return your response as a single, valid JSON object with keys corresponding to the fields to update (e.g., {\"name\": \"New Name\", \"description\": \"New Description\", ...}).\n";
                                $prompt .= "If you are updating 'meta_keyword', provide a comma-separated string of keywords.\n";
                                $prompt .= "Ensure the product description is HTML formatted (e.g., using <p>, <ul>, <li> tags).";

                                $this->log->write('AI Updater: Sending prompt to OpenRouter for product ID ' . $product_id . ': ' . $prompt);
                                $api_response_raw = $openrouter->generateText($prompt);
                                $this->log->write('AI Updater: Raw API response for product ID ' . $product_id . ': ' . json_encode($api_response_raw));

                                if (isset($api_response_raw['error'])) {
                                    $json['error'] = 'API Error for product ID ' . $product_id . ': ' . $api_response_raw['error'];
                                    $this->log->write('AI Updater: ' . $json['error']);
                                } else {
                                    $ai_content = $api_response_raw['choices'][0]['message']['content'] ?? '';
                                    if (empty($ai_content)) {
                                         $json['error'] = 'API response for product ID ' . $product_id . ' does not contain expected content structure.';
                                        $this->log->write('AI Updater: ' . $json['error'] . ' Response: ' . json_encode($api_response_raw));
                                    }

                                    if (!isset($json['error'])) {
                                        $parsed_data = $openrouter->parseProductUpdateResponse($ai_content, $fields_to_update);
                                        $this->log->write('AI Updater: Parsed data for product ID ' . $product_id . ': ' . json_encode($parsed_data));

                                        if (isset($parsed_data['parsing_error'])) {
                                            $json['error'] = 'Parsing Error for product ID ' . $product_id . ': ' . $parsed_data['parsing_error'];
                                            $this->log->write('AI Updater: ' . $json['error']);
                                        } else if (!empty($parsed_data)) {
                                            $update_product_data = ['product_description' => []]; // Initialize

                                            foreach ($product_descriptions as $language_id => $description_data) {
                                                $update_product_description_data[$language_id] = $description_data; // Mevcut verileri koru
                                                if (in_array('name', $fields_to_update) && isset($parsed_data['name'])) {
                                                    $update_product_description_data[$language_id]['name'] = $parsed_data['name'];
                                                }
                                                if (in_array('description', $fields_to_update) && isset($parsed_data['description'])) {
                                                    $update_product_description_data[$language_id]['description'] = $parsed_data['description'];
                                                }
                                                if (in_array('meta_title', $fields_to_update) && isset($parsed_data['meta_title'])) {
                                                    $update_product_description_data[$language_id]['meta_title'] = $parsed_data['meta_title'];
                                                }
                                                if (in_array('meta_description', $fields_to_update) && isset($parsed_data['meta_description'])) {
                                                    $update_product_description_data[$language_id]['meta_description'] = $parsed_data['meta_description'];
                                                }
                                                if (in_array('meta_keyword', $fields_to_update) && isset($parsed_data['meta_keyword'])) {
                                                    $update_product_description_data[$language_id]['meta_keyword'] = $parsed_data['meta_keyword'];
                                                }
                                            }
                                            $update_product_data['product_description'] = $update_product_description_data;

                                            $this->model_extension_module_ai_updater->updateProduct($product_id, $update_product_data);
                                            $json['success'] = sprintf($this->language->get('text_product_updated_success_single'), $product_id);
                                        } else {
                                            $json['warning'] = 'AI did not return usable data for product ID ' . $product_id . '. Check logs.';
                                            $this->log->write('AI Updater: ' . $json['warning'] . ' AI Content: ' . $ai_content);
                                        }
                                    }
                                }
                            } else {
                                $json['error'] = 'Product with ID ' . $product_id . ' not found.';
                            }
                        }
                    }
                } else {
                    $json['error'] = $this->language->get('error_missing_data');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_ai_updater', ['module_ai_updater_status' => 0]);
    }

    public function uninstall() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_ai_updater');
    }

    // validate metodu validateForm olarak değiştirildi ve sadece ayar formu için kullanılacak.
    // updateProducts içindeki izin kontrolü orada kalacak.
}
