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
        $this->version = '0.0.1';
        $this->author = 'K. Pianka';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Change default combination');
        $this->description = $this->l('Change default combination when it\'s out of stock');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => '8.0');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CHANGEDEFAULTCOMBINATION_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionUpdateQuantity');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CHANGEDEFAULTCOMBINATION_LIVE_MODE');

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

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {

        $form_fields = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icons' => 'icon-cogs'
                ],
                'tabs' => [
                    'settings_one' => $this->trans("Ustawienia 1", [], 'Modules.Changedefaultcombination.Admin'),
                    'settings_two' => $this->trans("Ustawienia 2", [], 'Modules.Changedefaultcombination.Admin')
                ],
                'input' => [
                    [
                        'type' => 'radio',
                        'label' => $this->l('Set default combination'),
                        'name' => 'CHANGEDEFAULTCOMBINATION_SET_DEFAULT',
                        'values' => [
                            [
                                'id' => 'h_s',
                                'value' => 0,
                                'label' => $this->l('Highest stock')
                            ],
                            [
                                'id' => 'l_s',
                                'value' => 1,
                                'label' => $this->l('Lowest stock')
                            ],
                            [
                                'id' => 'h_p',
                                'value' => 2,
                                'label' => $this->l('Highest price')
                            ],
                            [
                                'id' => 'l_p',
                                'value' => 3,
                                'label' => $this->l('Lowest price')
                            ],
                            'tab' => 'settings_one'
                        ]
                    ]

                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ]

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

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'CHANGEDEFAULTCOMBINATION_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'CHANGEDEFAULTCOMBINATION_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'CHANGEDEFAULTCOMBINATION_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CHANGEDEFAULTCOMBINATION_LIVE_MODE' => Configuration::get('CHANGEDEFAULTCOMBINATION_LIVE_MODE', true),
            'CHANGEDEFAULTCOMBINATION_ACCOUNT_EMAIL' => Configuration::get('CHANGEDEFAULTCOMBINATION_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'CHANGEDEFAULTCOMBINATION_ACCOUNT_PASSWORD' => Configuration::get('CHANGEDEFAULTCOMBINATION_ACCOUNT_PASSWORD', null),
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

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookActionUpdateQuantity($params)
    {
        $id = $params['id_product'];
        $id_attribute = $params['id_product_attribute'];
    
        $product = new Product($id);
  
        $default_attribute_id = Product::getDefaultAttribute($id);
        $default_attribute_stock = StockAvailable::getQuantityAvailableByProduct($id, $default_attribute_id);
    
        if($default_attribute_stock <= 0){
            $combinations = $product->getAttributeCombinations((int)Configuration::get('PS_LANG_DEFAULT'));
            foreach ($combinations as $combination){
                $attribute_id = $combination['id_product_attribute'];
                $attribute_stock = StockAvailable::getQuantityAvailableByProduct($id, $attribute_id);
                if($attribute_stock > 0){
                    $product->cache_default_attribute = $attribute_id;
    
                    $sql_product = 'UPDATE `' . _DB_PREFIX_ . 'product_shop`
                    SET `cache_default_attribute` = ' . (int)$attribute_id . '
                    WHERE `id_product` = ' . (int)$id;
    
                    $sql_update_default_off = 'UPDATE `' . _DB_PREFIX_ . 'product_attribute_shop`
                    SET `default_on` = NULL
                    WHERE `id_product` = ' . (int)$id . ' AND `default_on` = 1';
    
                    $sql_update_default_on = 'UPDATE `' . _DB_PREFIX_ . 'product_attribute_shop`
                    SET `default_on` = 1
                    WHERE `id_product_attribute` = ' . (int)$attribute_id;
    
                    Db::getInstance()->execute($sql_product);
                    Db::getInstance()->execute($sql_update_default_off);
                    Db::getInstance()->execute($sql_update_default_on);
    
                    break;
                }
            }
        }
    }
}
