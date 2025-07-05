<?php
class ModelExtensionModuleAiUpdater extends Model {

    public function updateProduct($product_id, $data) {
        // OpenCart'ın kendi ürün güncelleme mantığını kullanacağız
        // $this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "' WHERE product_id = '" . (int)$product_id . "'");
        // Bu kısım, $data içeriğine göre dinamik olarak oluşturulmalı.
        // Özellikle product_description gibi çoklu dil tabloları için dikkatli olunmalı.

        // Örnek: Ürün adı ve açıklamasını güncelleme (tüm diller için)
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
                // Diğer description alanları da buraya eklenebilir (tag vb.)


                if (!empty($update_fields)) {
                    $sql .= implode(", ", $update_fields);
                    $sql .= " WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "'";
                    $this->db->query($sql);
                }
            }
        }

        // SEO URL güncellemesi
        // Eğer ürün adı güncellendiyse, SEO URL'i de güncelle
        // Varsayılan dilin ürün adını baz alarak SEO URL oluştur.
        $new_product_name_for_seo = '';
        if (isset($data['product_description'])) {
            // Öncelikle mağazanın varsayılan dilindeki ismi kontrol et
            $default_language_id = (int)$this->config->get('config_language_id');
            if (isset($data['product_description'][$default_language_id]['name'])) {
                $new_product_name_for_seo = $data['product_description'][$default_language_id]['name'];
            } else {
                // Varsayılan dilde isim yoksa, gelen ilk ismi al
                $first_description = reset($data['product_description']);
                if (isset($first_description['name'])) {
                    $new_product_name_for_seo = $first_description['name'];
                }
            }
        }

        if (!empty($new_product_name_for_seo)) {
            $keyword = $this->generateSeoKeyword($new_product_name_for_seo);

            // OpenCart'ta url_alias tablosu store_id ve language_id bazlı çalışır.
            // Şimdilik varsayılan store (0) ve tüm diller için aynı keyword'ü atayacağız.
            // Daha gelişmiş bir senaryoda her dil için ayrı keyword üretilebilir.

            // Önce mevcut product_id için tüm SEO keyword kayıtlarını sil (tüm diller ve mağazalar için)
            // Bu, AI'nın her zaman yeni ve güncel bir SEO URL oluşturmasını sağlar.
            // Alternatif olarak, sadece belirli dil/mağaza için güncellenebilir.
            $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");

            // Yeni keyword'ü ekle. OpenCart 3.x'te seo_url tablosunda language_id yoktur,
            // bunun yerine `design_layout` ve `url_alias` birleşimiyle dil yönetimi dolaylı olabilir
            // veya `product_seo_url` gibi ayrı tablolar kullanılabilir.
            // Standart `seo_url` tablosu `store_id` içerir.
            // Tek bir keyword tüm diller için kullanılacaksa:
            if ($keyword) {
                 // Keyword'ün benzersiz olduğundan emin ol (mevcut product_id hariç)
                $unique_keyword = $this->makeKeywordUnique($keyword, $product_id);

                $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '0', language_id = '" . (int)$this->config->get('config_language_id') . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($unique_keyword) . "'");
                // Eğer birden fazla dil için ayrı keyword yönetimi isteniyorsa, burada bir döngü ve her dil için ayrı keyword üretimi/kaydı gerekir.
                // Örneğin, her $data['product_description'] içindeki $language_id için:
                // if(isset($value['name'])) { $lang_keyword = $this->generateSeoKeyword($value['name']); ... }
            }
        }

        // Önbelleği temizle (OpenCart 3.x için)
        $this->cache->delete('product');
        $this->cache->delete('seo.url'); // SEO URL önbelleğini de temizle

        // Log data for successful update
        // $this->log->write('AI Updater Data: Product ID ' . $product_id . ' data updated in DB.'); // Controller'da loglanıyor
    }

    private function generateSeoKeyword($text) {
        // Türkçe karakterleri dönüştür
        $text = str_replace(
            array('ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'),
            array('i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'),
            $text
        );
        // Küçük harfe çevir
        $text = strtolower($text);
        // Boşlukları ve özel karakterleri tire ile değiştir
        $text = preg_replace('/[^a-z0-9_]+/i', '-', $text);
        // Birden fazla tireyi tek tireye indirge
        $text = preg_replace('/-+/', '-', $text);
        // Başta ve sondaki tireleri kaldır
        $text = trim($text, '-');
        return $text;
    }

    private function makeKeywordUnique($keyword, $product_id_to_exclude) {
        $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($keyword) . "' AND query != 'product_id=" . (int)$product_id_to_exclude . "'");
        if (!$query->num_rows) {
            return $keyword;
        }

        $counter = 2;
        $original_keyword = $keyword;
        do {
            $keyword = $original_keyword . '-' . $counter;
            $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($keyword) . "' AND query != 'product_id=" . (int)$product_id_to_exclude . "'");
            $counter++;
        } while ($query->num_rows);

        return $keyword;
    }

    public function getProducts($data = array()) {
        // OpenCart'ın admin/model/catalog/product.php dosyasındaki getProducts fonksiyonuna benzer bir yapı
        $sql = "SELECT p.product_id, pd.name FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        // Diğer filtreler eklenebilir (model, price, quantity, status)

        $sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'pd.name',
            'p.model',
            // ...
            'p.product_id'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalProducts($data = array()) {
        // OpenCart'ın admin/model/catalog/product.php dosyasındaki getTotalProducts fonksiyonuna benzer bir yapı
        $sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }
        // Diğer filtreler...

        $query = $this->db->query($sql);
        return $query->row['total'];
    }


    public function backupDatabase() {
        // Veritabanı yedeği alma işlemi
        // Bu işlem sunucu kaynaklarını tüketebilir, dikkatli kullanılmalı
        // OpenCart'ın kendi backup/restore mekanizması incelenebilir veya basit bir SQL dump yapılabilir.

        $backup_file = DIR_STORAGE . 'backup/dump_' . date('Y-m-d_H-i-s') . '.sql'; // OpenCart 3.x storage dizini

        // Basit bir dump mekanizması:
        // Not: Bu çok büyük veritabanları için ideal olmayabilir.
        // Daha robust bir çözüm için mysqldump komutu veya OpenCart'ın kendi backup controller'ı kullanılabilir.

        $output = '';
        $tables = array();
        $query = $this->db->query("SHOW TABLES");
        foreach ($query->rows as $result) {
            $tables[] = $result['Tables_in_' . DB_DATABASE];
        }

        foreach ($tables as $table) {
            $query_table = $this->db->query("SELECT * FROM `" . $table . "`");
            $output .= 'DROP TABLE IF EXISTS `' . $table . '`;' . "\n\n";

            $query_create_table = $this->db->query("SHOW CREATE TABLE `" . $table . "`");
            $output .= $query_create_table->row['Create Table'] . ";\n\n";

            foreach ($query_table->rows as $result) {
                $fields = '';
                foreach (array_keys($result) as $value) {
                    $fields .= '`' . $value . '`, ';
                }
                $output .= 'INSERT INTO `' . $table . '` (' . rtrim($fields, ', ') . ') VALUES (';

                $values = '';
                foreach (array_values($result) as $value) {
                    $value = str_replace(array("\x00", "\x0a", "\x0d", "\x1a"), array('\0', '\n', '\r', '\Z'), $value);
                    $value = str_replace(array("'", '"'), array("\'", '\"'), $value); // Escape quotes
                    if (is_null($value)) {
                        $values .= "NULL, ";
                    } elseif (is_numeric($value) && !is_string($value)) { // Sayısal değerleri tırnaksız yaz
                        $values .= $value . ", ";
                    }
                    else {
                        $values .= '\'' . $this->db->escape($value) . '\', '; // db->escape kullanımı önemli
                    }
                }
                $output .= rtrim($values, ', ') . ");\n";
            }
            $output .= "\n\n";
        }

        $handle = fopen($backup_file, 'w');
        fwrite($handle, $output);
        fclose($handle);

        // Log backup information
        $this->log->write('AI Updater: Database backup created at ' . $backup_file);

        return $backup_file;
    }
}
