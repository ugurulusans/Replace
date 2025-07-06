<?php
class ModelExtensionModuleAiUpdater extends Model {

    public function updateProduct($product_id, $data) {
        if (isset($data['product_description'])) {
            foreach ($data['product_description'] as $language_id => $value) {
                $sql = "UPDATE " . DB_PREFIX . "product_description SET ";
                $update_fields = array();
                if (isset($value['name'])) {
                    $update_fields[] = "name = '" . $this->db->escape($value['name']) . "'";
                }
                if (isset($value['description'])) {
                    $update_fields[] = "description = '" . $this->db->escape($value['description']) . "'";
                }
                if (isset($value['meta_title'])) {
                    $update_fields[] = "meta_title = '" . $this->db->escape($value['meta_title']) . "'";
                }
                if (isset($value['meta_description'])) {
                    $update_fields[] = "meta_description = '" . $this->db->escape($value['meta_description']) . "'";
                }
                if (isset($value['meta_keyword'])) {
                    $update_fields[] = "meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'";
                }

                if (!empty($update_fields)) {
                    $sql .= implode(", ", $update_fields);
                    $sql .= " WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "'";
                    $this->db->query($sql);
                }
            }
        }

        $new_product_name_for_seo = '';
        if (isset($data['product_description'])) {
            $default_language_id = (int)$this->config->get('config_language_id');
            if (isset($data['product_description'][$default_language_id]['name'])) {
                $new_product_name_for_seo = $data['product_description'][$default_language_id]['name'];
            } else {
                $first_description = reset($data['product_description']);
                if (isset($first_description['name'])) {
                    $new_product_name_for_seo = $first_description['name'];
                }
            }
        }

        if (!empty($new_product_name_for_seo)) {
            $keyword = $this->generateSeoKeyword($new_product_name_for_seo);

            $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'"); // OC3 seo_url tablosu

            if ($keyword) {
                $unique_keyword = $this->makeKeywordUnique($keyword, (int)$product_id);
                // OC3'te seo_url tablosunda language_id yoktur, keyword tüm diller için aynıdır.
                // Eğer multi-language SEO URL'ler farklı bir eklentiyle yönetiliyorsa, bu kısım ona göre ayarlanmalı.
                // Standart OC3'te tek keyword olur.
                $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '0', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($unique_keyword) . "'");
            }
        }

        $this->cache->delete('product');
        // OC3'te seo.url cache'i olmayabilir, genellikle URL'ler dinamik oluşturulur veya farklı bir cache mekanizması vardır.
        // $this->cache->delete('seo.url');
    }

    private function generateSeoKeyword($text) {
        $text = str_replace(
            array('ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'),
            array('i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'),
            $text
        );
        if (function_exists('mb_strtolower')) { // mb_strtolower varsa kullan, yoksa normal strtolower
            $text = mb_strtolower($text, 'UTF-8');
        } else {
            $text = strtolower($text);
        }
        $text = preg_replace('/[^a-z0-9_]+/i', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }

    private function makeKeywordUnique($keyword, $product_id_to_exclude) {
        $suffix = '';
        $counter = 2;
        $test_keyword = $keyword;

        while (true) {
            // OC3'te seo_url tablosunda language_id yoktur.
            $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($test_keyword) . "' AND query != 'product_id=" . (int)$product_id_to_exclude . "'");
            if ($query->row['total'] == 0) {
                return $test_keyword;
            }
            $test_keyword = $keyword . '-' . $counter;
            $counter++;
        }
    }

    public function getProducts($data = array()) {
        $sql = "SELECT p.product_id, pd.name FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '" . $this->db->escape((string)$data['filter_name']) . "%'";
        }

        $sql .= " GROUP BY p.product_id";
        $sort_data = array( 'pd.name', 'p.model', 'p.product_id' );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name";
        }

        if (isset($data['order']) && (strtoupper($data['order']) == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if (!isset($data['start']) || $data['start'] < 0) {
                $data['start'] = 0;
            }
            if (!isset($data['limit']) || $data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalProducts($data = array()) {
        $sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '" . $this->db->escape((string)$data['filter_name']) . "%'";
        }

        $query = $this->db->query($sql);
        return (int)$query->row['total'];
    }

    public function backupDatabase() {
        $backup_file = DIR_STORAGE . 'backup/dump_' . date('Y-m-d_H-i-s') . '.sql'; // OC3 DIR_STORAGE

        $output = '';
        $tables = array();
        $query = $this->db->query("SHOW TABLES");
        foreach ($query->rows as $result) {
            $tables[] = $result['Tables_in_' . DB_DATABASE];
        }

        foreach ($tables as $table) {
            $query_table = $this->db->query("SELECT * FROM `" . $table . "`"); // Backtick eklendi
            $output .= 'DROP TABLE IF EXISTS `' . $table . '`;' . "\n\n"; // Backtick eklendi

            $query_create_table = $this->db->query("SHOW CREATE TABLE `" . $table . "`"); // Backtick eklendi
            if (isset($query_create_table->row['Create Table'])) {
                 $output .= $query_create_table->row['Create Table'] . ";\n\n";
            }

            foreach ($query_table->rows as $result) {
                $fields = '';
                foreach (array_keys($result) as $value) {
                    $fields .= '`' . $value . '`, ';
                }
                $output .= 'INSERT INTO `' . $table . '` (' . rtrim($fields, ', ') . ') VALUES ('; // Backtick eklendi

                $values = '';
                foreach (array_values($result) as $value) {
                    // OC3'te $this->db->escape() zaten null ve diğer tipleri ele alır.
                    // Özel null ve sayısal kontrolüne gerek olmayabilir.
                    if (is_null($value)) {
                        $values .= "NULL, ";
                    } else {
                         $values .= "'" . $this->db->escape($value) . "', ";
                    }
                }
                $output .= rtrim($values, ', ') . ");\n";
            }
            $output .= "\n\n";
        }

        $handle = fopen($backup_file, 'w');
        fwrite($handle, $output);
        fclose($handle);

        $this->log->write('AI Updater: Database backup created at ' . $backup_file);
        return $backup_file;
    }
}
