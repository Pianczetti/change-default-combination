<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\Module\ChangeDefaultCombination\Selector\CombinationSelector;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Changedefaultcombination extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'changedefaultcombination';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'WeNet';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Change default combination when out of stock');
        $this->description = $this->l('Module allows You to change default combination when the combination runs out of stock.');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => '8.99.99');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CHANGEDEFAULTCOMBINATION_SETTING', 0);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionUpdateQuantity');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CHANGEDEFAULTCOMBINATION_SETTING');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitChangedefaultcombinationModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);


        return $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                // 'tabs' => [
                //     'ustawienia_1' => $this->l('Ustawienia 1'),
                //     'ustawienia_2' => $this->l('Ustawienia 2')
                // ],
                'input' => [
                    [
                        'type' => 'radio',
                        'label' => $this->l('Kombinacja która ma zostać ustawiona, po wyczerpaniu zapasów w domyślnej kombinacji'),
                        'name' => 'CHANGEDEFAULTCOMBINATION_SETTING',
                        'class' => 't',
                        'required'  => true,
                        'is_bool' => true, 
                        'values' => [
                            [
                                'id' => 'highest_price',
                                'value' => 0,
                                'label' => $this->l('Najwyższa cena')
                            ],
                            [
                                'id' => 'lowest_price',
                                'value' => 1,
                                'label' => $this->l('Najniższa cena')
                            ],
                            [
                               'id' => 'highest_stock',
                                'value' => 2,
                                'label' => $this->l('Najwyższy stan magazynowy')
                            ],
                            [
                                'id' => 'lowest_stock',
                                 'value' => 3,
                                 'label' => $this->l('Najniższy stan magazynowy')
                             ]
                        ],
                        // 'tab' => 'ustawienia_1'
                    ]
                ],
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ]
        ];

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitChangedefaultcombinationModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        // return $helper->generateForm(array($this->getConfigForm()));
        return $helper->generateForm([$fields_form]);
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CHANGEDEFAULTCOMBINATION_SETTING' => Configuration::get('CHANGEDEFAULTCOMBINATION_SETTING')
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function hookActionUpdateQuantity($params)
    {
        $id = $params['id_product'];
        $setting = (int)Configuration::get('CHANGEDEFAULTCOMBINATION_SETTING');

        // $default_attribute_id = Product::getDefaultAttribute($id);
        // $default_attribute_stock = StockAvailable::getQuantityAvailableByProduct($id, $default_attribute_id);

        // $combinations = $product->getAttributeCombinations((int)Configuration::get('PS_LANG_DEFAULT'));
    
        // if($default_attribute_stock <= 0){
        //     // Inicjalizacja zmiennych pomocniczych
        //     $attribute_stock = null;
        //     $bestCombination = null;
        //     $bestPrice = null;
        //     $bestStock = null;

        //     foreach ($combinations as $combination) {
        //         $attribute_id = $combination['id_product_attribute'];
        //         // Pobranie stanu magazynowego i ceny dla kombinacji
        //         $attribute_stock = StockAvailable::getQuantityAvailableByProduct($id, $attribute_id);
        //         $attribute_price = Product::getPriceStatic($id, true, $attribute_id);

        //         // Warunki w zależności od ustawienia
        //         if($attribute_id !== $default_attribute_id){
        //             switch ($setting) {
        //                 case 0: // Najwyższa cena
        //                     if ($bestCombination === null || $attribute_price > $bestPrice && $attribute_stock > 0) {
        //                         $bestCombination = $combination;
        //                         $bestPrice = $attribute_price;
        //                     }
        //                     break;

        //                 case 1: // Najniższa cena
        //                     if ($bestCombination === null || $attribute_price < $bestPrice && $attribute_stock > 0) {
        //                         $bestCombination = $combination;
        //                         $bestPrice = $attribute_price;
        //                     }
        //                     break;
        //                 case 2: // Najwyższy stan magazynowy
        //                     if ($bestCombination === null|| $attribute_stock > $bestStock && $attribute_stock > 0) {
        //                         $bestCombination = $combination;
        //                         $bestStock = $attribute_stock;
        //                     }
        //                     break;

        //                 case 3: // Najniższy stan magazynowy
        //                     if (($bestCombination === null  && $attribute_id !== $default_attribute_id) || $attribute_stock < $bestStock && $attribute_stock > 0) {
        //                         $bestCombination = $combination;
        //                         $bestStock = $attribute_stock;
        //                     }
        //                     break;
        //             }
        //         }
        //     }
        // }

        $bestCombination = CombinationSelector::getBestCombination($id, $setting);
        
        if($bestCombination){
            if(StockAvailable::getQuantityAvailableByProduct($id, $bestCombination['id_product_attribute']) > 0){
                $bestAttribute_id = $bestCombination['id_product_attribute'];

                $product->cache_default_attribute = $bestAttribute_id;

                $sql_product = 'UPDATE `' . _DB_PREFIX_ . 'product_shop`
                SET `cache_default_attribute` = ' . (int)$bestAttribute_id . '
                WHERE `id_product` = ' . (int)$id;

                $sql_update_default_off = 'UPDATE `' . _DB_PREFIX_ . 'product_attribute_shop`
                SET `default_on` = NULL
                WHERE `id_product` = ' . (int)$id . ' AND `default_on` = 1';

                $sql_update_default_on = 'UPDATE `' . _DB_PREFIX_ . 'product_attribute_shop`
                SET `default_on` = 1
                WHERE `id_product_attribute` = ' . (int)$bestAttribute_id;

                Db::getInstance()->execute($sql_product);
                Db::getInstance()->execute($sql_update_default_off);
                Db::getInstance()->execute($sql_update_default_on);
            }
        }
    }
}
