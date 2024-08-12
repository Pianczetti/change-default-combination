<?php

namespace PrestaShop\Module\ChangeDefaultCombination\Selector;

class CombinationSelector
{
    public static function getBestCombination(int $id_product, int $setting): ?array
    {
        $product = new Product($id);
        $combinations = $product->getAttributeCombinations((int)Configuration::get('PS_LANG_DEFAULT'));
        $default_attribute_id = Product::getDefaultAttribute($id_product);
        $default_attribute_stock = StockAvailable::getQuantityAvailableByProduct($id_product, $default_attribute_id);
        $attribute_stock = null;
        $bestCombination = null;
        $bestPrice = null;
        $bestStock = null;

        if($default_attribute_stock <= 0){
            foreach ($combinations as $combination) {
                $attribute_id = $combination['id_product_attribute'];
                $attribute_stock = StockAvailable::getQuantityAvailableByProduct($id_product, $attribute_id);
                $attribute_price = Product::getPriceStatic($id_product, true, $attribute_id);

                if($attribute_id !== $default_attribute_id){
                    switch ($setting) {
                        case 0: // Najwyższa cena
                            if ($bestCombination === null || $attribute_price > $bestPrice) {
                                $bestCombination = $combination;
                                $bestPrice = $attribute_price;
                            }
                            break;

                        case 1: // Najniższa cena
                            if ($bestCombination === null || $attribute_price < $bestPrice) {
                                $bestCombination = $combination;
                                $bestPrice = $attribute_price;
                            }
                            break;

                        case 2: // Najwyższy stan magazynowy
                            if ($bestCombination === null || $attribute_stock > $bestStock) {
                                $bestCombination = $combination;
                                $bestStock = $attribute_stock;
                            }
                            break;

                        case 3: // Najniższy stan magazynowy
                            if ($bestCombination === null || $attribute_stock < $bestStock) {
                                $bestCombination = $combination;
                                $bestStock = $attribute_stock;
                            }
                            break;
                        }
                    }
            }
            return $bestCombination;
        }
        else
        {
            return [];
        }
    }

    // Inne metody np. do masowej aktualizacji kombinacji mogą być dodane tutaj.
}