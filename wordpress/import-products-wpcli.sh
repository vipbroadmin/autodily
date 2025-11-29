#!/bin/bash
# Скрипт импорта товаров через WP-CLI
# Использует массив товаров из import-all-products.sh

set -e

cd /var/www/html

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║     ИМПОРТ ТОВАРОВ ЧЕРЕЗ WP-CLI                               ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Массив всех товаров из import-all-products.sh
# Формат: "product_name|product_code|article|brand|price"
declare -a products=(
    "Амортизатор МАЗ задний 240/425 MEGAPOWER|675348|290-11-005|MEGAPOWER|4020"
    "Амортизатор МАЗ-4370 передний MEGAPOWER|902516|290-12-012|MEGAPOWER|3660"
    "Амортизатор МАЗ-4370 задний MEGAPOWER|733258|290-11-003|MEGAPOWER|3760"
    "Амортизатор КАМАЗ, ПАЗ-3205 275/460 MEGAPOWER|777873|290-12-011|MEGAPOWER|3600"
    "Амортизатор ГАЗ-3302, 3221 передний/задний, ГАЗ-2217 задний масляный MEGAPOWER|902519|290-12-004|MEGAPOWER|2090"
)

# Если массив пустой, читаем из файла
if [ ${#products[@]} -eq 0 ] || [ "${products[0]}" = "" ]; then
    echo "Чтение товаров из import-all-products.sh..."
    # Извлекаем массив из файла
    source <(grep -A 1000000 "declare -a products=(" import-all-products.sh | grep -B 1000000 "^)" | head -n -1 | tail -n +2)
fi

TOTAL=${#products[@]}
CREATED=0
ERRORS=0

echo "Найдено товаров: $TOTAL"
echo "Начало импорта..."
echo ""

for product in "${products[@]}"; do
    IFS='|' read -r product_name product_code article brand price <<< "$product"
    
    # Валидация
    if [ -z "$product_name" ] || [ -z "$price" ]; then
        continue
    fi
    
    # Создание товара
    product_id=$(wp post create \
        --post_type=product \
        --post_title="$product_name" \
        --post_excerpt="Артикул: $article" \
        --post_status=publish \
        --allow-root \
        --porcelain 2>/dev/null)
    
    if [ ! -z "$product_id" ] && [ "$product_id" -gt 0 ]; then
        # Установка мета-полей
        wp post meta update $product_id _product_type "simple" --allow-root > /dev/null 2>&1
        wp post meta update $product_id _sku "$product_code" --allow-root > /dev/null 2>&1
        wp post meta update $product_id _regular_price "$price" --allow-root > /dev/null 2>&1
        wp post meta update $product_id _price "$price" --allow-root > /dev/null 2>&1
        wp post meta update $product_id _visibility "visible" --allow-root > /dev/null 2>&1
        wp post meta update $product_id _stock_status "instock" --allow-root > /dev/null 2>&1
        wp post meta update $product_id _manage_stock "no" --allow-root > /dev/null 2>&1
        
        if [ ! -z "$brand" ]; then
            wp post meta update $product_id _brand "$brand" --allow-root > /dev/null 2>&1
        fi
        
        ((CREATED++))
    else
        ((ERRORS++))
    fi
    
    # Прогресс каждые 100 товаров
    if [ $((CREATED % 100)) -eq 0 ]; then
        echo "Прогресс: создано $CREATED из $TOTAL товаров"
    fi
done

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                    ИМПОРТ ЗАВЕРШЕН                              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "Всего товаров: $TOTAL"
echo "Создано: $CREATED"
echo "Ошибок: $ERRORS"
echo ""

