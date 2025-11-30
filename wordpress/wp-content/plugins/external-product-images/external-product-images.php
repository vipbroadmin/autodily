<?php
/**
 * Plugin Name: External Product Images for WooCommerce
 * Description: Позволяет использовать внешние URL изображений для товаров WooCommerce
 * Version: 1.2.0
 * Author: Auto Shop
 */

if (!defined('ABSPATH')) {
    exit;
}

class External_Product_Images {
    
    public function __construct() {
        // Перехватываем получение ID изображения товара
        add_filter('woocommerce_product_get_image_id', array($this, 'get_external_image_id'), 10, 2);
        
        // Перехватываем метаданные attachment для виртуальных ID
        add_filter('wp_get_attachment_metadata', array($this, 'get_external_attachment_metadata'), 10, 2);
        
        // Перехватываем src изображения
        add_filter('wp_get_attachment_image_src', array($this, 'replace_image_src'), 10, 4);
        
        // Перехватываем srcset для внешних изображений
        add_filter('wp_get_attachment_image_srcset', array($this, 'replace_image_srcset'), 10, 5);
        
        // Перехватываем sizes для внешних изображений
        add_filter('wp_get_attachment_image_sizes', array($this, 'replace_image_sizes'), 10, 5);
        
        // Перехватываем HTML изображения через wp_get_attachment_image
        // Используем очень высокий приоритет, чтобы перехватить ДО того, как WordPress попытается получить пост
        add_filter('wp_get_attachment_image', array($this, 'replace_attachment_image'), 1, 5);
        
        // Перехватываем HTML изображения товара
        add_filter('woocommerce_product_get_image', array($this, 'replace_product_image'), 10, 5);
        
        // Перехватываем HTML галереи товара (для single product page)
        add_filter('woocommerce_single_product_image_thumbnail_html', array($this, 'replace_gallery_image'), 10, 2);
        
        // Перехватываем wc_get_gallery_image_html для галереи
        add_filter('woocommerce_product_get_gallery_image_ids', array($this, 'add_external_to_gallery'), 10, 2);
        
        // Перехватываем проверку существования attachment
        add_filter('wp_attachment_is_image', array($this, 'is_external_image'), 10, 2);
        
        // Перехватываем get_post_meta для виртуальных attachment
        add_filter('get_post_metadata', array($this, 'get_external_attachment_meta'), 10, 4);
    }
    
    /**
     * Получает ID внешнего изображения (виртуальный)
     */
    public function get_external_image_id($image_id, $product) {
        $external_url = get_post_meta($product->get_id(), '_product_image_url', true);
        
        if (!empty($external_url) && empty($image_id)) {
            // Возвращаем специальный ID для внешнего изображения
            return 'external_' . $product->get_id();
        }
        
        return $image_id;
    }
    
    /**
     * Возвращает метаданные для виртуальных внешних изображений
     */
    public function get_external_attachment_metadata($metadata, $attachment_id) {
        if (is_string($attachment_id) && strpos($attachment_id, 'external_') === 0) {
            $product_id = str_replace('external_', '', $attachment_id);
            $external_url = get_post_meta($product_id, '_product_image_url', true);
            
            if (!empty($external_url)) {
                // Возвращаем минимальные метаданные для внешнего изображения
                return array(
                    'width' => 600,
                    'height' => 600,
                    'file' => basename($external_url),
                    'sizes' => array()
                );
            }
        }
        
        return $metadata;
    }
    
    /**
     * Проверяет, является ли виртуальный ID изображением
     */
    public function is_external_image($result, $attachment_id) {
        if (is_string($attachment_id) && strpos($attachment_id, 'external_') === 0) {
            $product_id = str_replace('external_', '', $attachment_id);
            $external_url = get_post_meta($product_id, '_product_image_url', true);
            
            if (!empty($external_url)) {
                return true;
            }
        }
        
        return $result;
    }
    
    /**
     * Заменяет src изображения на внешний URL
     */
    public function replace_image_src($image, $attachment_id, $size, $icon) {
        // Проверяем, является ли это внешним изображением
        if (is_string($attachment_id) && strpos($attachment_id, 'external_') === 0) {
            $product_id = str_replace('external_', '', $attachment_id);
            $external_url = get_post_meta($product_id, '_product_image_url', true);
            
            if (!empty($external_url)) {
                $dimensions = wc_get_image_size($size);
                return array(
                    $external_url,
                    isset($dimensions['width']) ? (int)$dimensions['width'] : 600,
                    isset($dimensions['height']) ? (int)$dimensions['height'] : 600,
                    true  // is_intermediate
                );
            }
        }
        
        return $image;
    }
    
    /**
     * Заменяет srcset для внешних изображений
     */
    public function replace_image_srcset($srcset, $attachment_id, $size, $image_meta, $image_src) {
        if (is_string($attachment_id) && strpos($attachment_id, 'external_') === 0) {
            $product_id = str_replace('external_', '', $attachment_id);
            $external_url = get_post_meta($product_id, '_product_image_url', true);
            
            if (!empty($external_url)) {
                // Возвращаем простой srcset с одним размером
                $dimensions = wc_get_image_size($size);
                $width = isset($dimensions['width']) ? (int)$dimensions['width'] : 600;
                return $external_url . ' ' . $width . 'w';
            }
        }
        
        return $srcset;
    }
    
    /**
     * Заменяет sizes для внешних изображений
     */
    public function replace_image_sizes($sizes, $attachment_id, $size, $image_meta, $image_src) {
        if (is_string($attachment_id) && strpos($attachment_id, 'external_') === 0) {
            // Возвращаем стандартный размер для внешних изображений
            return '(max-width: 600px) 100vw, 600px';
        }
        
        return $sizes;
    }
    
    /**
     * Заменяет HTML изображения через wp_get_attachment_image
     * Перехватываем ДО того, как WordPress попытается получить пост через get_post()
     * Возвращаем готовый HTML, чтобы WordPress не пытался обработать виртуальный ID
     */
    public function replace_attachment_image($html, $attachment_id, $size, $icon, $attr) {
        // Проверяем, является ли это внешним изображением
        if (is_string($attachment_id) && strpos($attachment_id, 'external_') === 0) {
            $product_id = str_replace('external_', '', $attachment_id);
            $external_url = get_post_meta($product_id, '_product_image_url', true);
            
            if (!empty($external_url)) {
                $product = wc_get_product($product_id);
                $product_name = $product ? $product->get_name() : '';
                
                // Получаем размеры изображения через наш фильтр
                $image_data = wp_get_attachment_image_src($attachment_id, $size, $icon);
                if ($image_data && is_array($image_data) && count($image_data) >= 3) {
                    list($src, $width, $height) = $image_data;
                    $width = (int)$width;
                    $height = (int)$height;
                } else {
                    $image_size = wc_get_image_size($size);
                    $width = isset($image_size['width']) ? (int)$image_size['width'] : 600;
                    $height = isset($image_size['height']) ? (int)$image_size['height'] : 600;
                    $src = $external_url;
                }
                
                // Парсим атрибуты
                if (!is_array($attr)) {
                    $attr = array();
                }
                
                // Устанавливаем обязательные атрибуты
                $size_class = is_array($size) ? implode('x', $size) : $size;
                $default_attr = array(
                    'src' => $src,
                    'class' => "attachment-{$size_class} size-{$size_class} wp-post-image",
                    'alt' => !empty($attr['alt']) ? $attr['alt'] : $product_name,
                    'width' => $width,
                    'height' => $height,
                );
                
                $attr = wp_parse_args($attr, $default_attr);
                
                // Убеждаемся, что width и height - это числа (не строки!)
                $attr['width'] = (int)$attr['width'];
                $attr['height'] = (int)$attr['height'];
                
                // Собираем атрибуты в строку
                $attributes = '';
                foreach ($attr as $key => $value) {
                    if ($value === '' || $value === null) {
                        continue;
                    }
                    $attributes .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
                }
                
                $html = sprintf(
                    '<img%s />',
                    $attributes
                );
                
                return $html;
            }
        }
        
        return $html;
    }
    
    /**
     * Заменяет HTML изображения товара
     */
    public function replace_product_image($image, $product, $size, $attr, $placeholder) {
        $external_url = get_post_meta($product->get_id(), '_product_image_url', true);
        
        if (!empty($external_url)) {
            $image_size = wc_get_image_size($size);
            $image_html = sprintf(
                '<img src="%s" alt="%s" class="wp-post-image" width="%d" height="%d" />',
                esc_url($external_url),
                esc_attr($product->get_name()),
                (int)$image_size['width'],
                (int)$image_size['height']
            );
            
            return $image_html;
        }
        
        return $image;
    }
    
    /**
     * Заменяет HTML галереи товара на странице товара
     * Это перехватывает placeholder и заменяет его на реальное изображение
     */
    public function replace_gallery_image($html, $post_thumbnail_id) {
        global $product;
        
        if (!$product) {
            return $html;
        }
        
        $external_url = get_post_meta($product->get_id(), '_product_image_url', true);
        
        // Если есть внешнее изображение и нет обычного изображения (placeholder)
        if (!empty($external_url) && empty($post_thumbnail_id)) {
            // Заменяем placeholder на реальное изображение
            $image_size = wc_get_image_size('woocommerce_single');
            $image_html = sprintf(
                '<div class="woocommerce-product-gallery__image">%s</div>',
                sprintf(
                    '<img src="%s" alt="%s" class="wp-post-image" width="%d" height="%d" />',
                    esc_url($external_url),
                    esc_attr($product->get_name()),
                    (int)$image_size['width'],
                    (int)$image_size['height']
                )
            );
            
            return $image_html;
        }
        
        return $html;
    }
    
    /**
     * Добавляет внешнее изображение в галерею товара
     */
    public function add_external_to_gallery($gallery_image_ids, $product) {
        $external_url = get_post_meta($product->get_id(), '_product_image_url', true);
        
        if (!empty($external_url) && empty($gallery_image_ids)) {
            // Возвращаем виртуальный ID для внешнего изображения
            return array('external_' . $product->get_id());
        }
        
        return $gallery_image_ids;
    }
    
    /**
     * Возвращает метаданные для виртуальных attachment
     */
    public function get_external_attachment_meta($value, $object_id, $meta_key, $single) {
        // Проверяем, является ли это виртуальным attachment ID
        if (is_string($object_id) && strpos($object_id, 'external_') === 0) {
            $product_id = str_replace('external_', '', $object_id);
            $external_url = get_post_meta($product_id, '_product_image_url', true);
            
            if (!empty($external_url)) {
                // Для _wp_attachment_image_alt возвращаем название товара
                if ($meta_key === '_wp_attachment_image_alt') {
                    $product = wc_get_product($product_id);
                    return $product ? $product->get_name() : '';
                }
                
                // Для других мета-полей возвращаем пустые значения
                return $single ? '' : array();
            }
        }
        
        return $value;
    }
}

new External_Product_Images();
