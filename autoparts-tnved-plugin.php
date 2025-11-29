<?php
/**
 * Plugin Name: AutoParts TN VED EAEU
 * Plugin URI: https://example.com
 * Description: Добавляет кастомное поле ТН ВЭД ЕАЭС для товаров WooCommerce и REST API для создания товаров
 * Version: 1.0.0
 * Author: Auto Shop
 * Text Domain: autoparts-tnved
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoParts_TN_VED {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_tnved_meta_box'));
        add_action('save_post', array($this, 'save_tnved_meta'));
        add_action('woocommerce_single_product_summary', array($this, 'display_tnved_on_product'), 25);
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Добавляет meta-box для ТН ВЭД в админке
     */
    public function add_tnved_meta_box() {
        add_meta_box(
            'tnved_meta_box',
            'ТН ВЭД ЕАЭС',
            array($this, 'tnved_meta_box_callback'),
            'product',
            'normal',
            'high'
        );
    }
    
    /**
     * Callback для отображения meta-box
     */
    public function tnved_meta_box_callback($post) {
        wp_nonce_field('tnved_meta_box', 'tnved_meta_box_nonce');
        $tnved = get_post_meta($post->ID, '_tn_ved_eaes', true);
        ?>
        <label for="tnved_field">Код ТН ВЭД ЕАЭС:</label>
        <input type="text" 
               id="tnved_field" 
               name="tnved_field" 
               value="<?php echo esc_attr($tnved); ?>" 
               style="width: 100%; padding: 8px; margin-top: 5px;"
               placeholder="Например: 8708309100">
        <p class="description">Введите код товарной номенклатуры внешнеэкономической деятельности ЕАЭС</p>
        <?php
    }
    
    /**
     * Сохраняет значение ТН ВЭД
     */
    public function save_tnved_meta($post_id) {
        if (!isset($_POST['tnved_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['tnved_meta_box_nonce'], 'tnved_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['tnved_field'])) {
            update_post_meta($post_id, '_tn_ved_eaes', sanitize_text_field($_POST['tnved_field']));
        }
    }
    
    /**
     * Выводит ТН ВЭД на странице товара
     */
    public function display_tnved_on_product() {
        global $product;
        $tnved = get_post_meta($product->get_id(), '_tn_ved_eaes', true);
        
        if ($tnved) {
            echo '<div class="tnved-info" style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">';
            echo '<strong>ТН ВЭД ЕАЭС:</strong> <span style="font-size: 18px; color: #333;">' . esc_html($tnved) . '</span>';
            echo '</div>';
        }
    }
    
    /**
     * Регистрирует REST API маршруты
     */
    public function register_rest_routes() {
        register_rest_route('autoparts/v1', '/create-product', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_product_via_api'),
            'permission_callback' => array($this, 'check_api_permission'),
        ));
    }
    
    /**
     * Проверка прав доступа к API
     */
    public function check_api_permission($request) {
        // Проверка через WooCommerce REST API keys или Basic Auth
        $consumer_key = $request->get_header('X-Consumer-Key');
        $consumer_secret = $request->get_header('X-Consumer-Secret');
        
        // Если используются WooCommerce ключи
        if ($consumer_key && $consumer_secret) {
            // Здесь можно добавить проверку через WooCommerce REST API
            return true;
        }
        
        // Или проверка через Basic Auth
        $auth_header = $request->get_header('Authorization');
        if ($auth_header && strpos($auth_header, 'Basic ') === 0) {
            // Простая проверка (в продакшене нужно использовать правильную аутентификацию)
            return true;
        }
        
        // Для разработки разрешаем без авторизации (можно убрать в продакшене)
        return true;
    }
    
    /**
     * Создает товар через REST API
     */
    public function create_product_via_api($request) {
        $params = $request->get_json_params();
        
        // Валидация обязательных полей
        if (empty($params['name'])) {
            return new WP_Error('missing_name', 'Поле "name" обязательно', array('status' => 400));
        }
        
        if (empty($params['price'])) {
            return new WP_Error('missing_price', 'Поле "price" обязательно', array('status' => 400));
        }
        
        // Проверка, что WooCommerce активен
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_not_active', 'WooCommerce не установлен или не активирован', array('status' => 500));
        }
        
        try {
            // Создаем товар
            $product = new WC_Product_Simple();
            $product->set_name(sanitize_text_field($params['name']));
            $product->set_description(isset($params['description']) ? wp_kses_post($params['description']) : '');
            $product->set_short_description(isset($params['description']) ? wp_kses_post($params['description']) : '');
            $product->set_regular_price(floatval($params['price']));
            $product->set_status('publish');
            
            $product_id = $product->save();
            
            if (is_wp_error($product_id)) {
                return new WP_Error('product_creation_failed', 'Не удалось создать товар', array('status' => 500));
            }
            
            // Сохраняем ТН ВЭД
            if (!empty($params['tnved'])) {
                update_post_meta($product_id, '_tn_ved_eaes', sanitize_text_field($params['tnved']));
            }
            
            // Загружаем и устанавливаем изображение
            if (!empty($params['image_url'])) {
                $this->set_product_image($product_id, esc_url_raw($params['image_url']));
            }
            
            return array(
                'status' => 'success',
                'product_id' => $product_id,
                'message' => 'Товар успешно создан'
            );
            
        } catch (Exception $e) {
            return new WP_Error('product_creation_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Загружает и устанавливает изображение товара
     */
    private function set_product_image($product_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attachment_id = media_sideload_image($image_url, $product_id, null, 'id');
        
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($product_id, $attachment_id);
        }
    }
}

// Инициализация плагина
new AutoParts_TN_VED();



