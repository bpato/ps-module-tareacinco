<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class tareacinco extends Module
{
    /** @var string Unique name */
    public $name = 'tareacinco';

    /** @var string Version */
    public $version = '1.0.0';

    /** @var string author of the module */
    public $author = 'Brais Pato';

    /** @var int need_instance */
    public $need_instance = 0;

    /** @var string Admin tab corresponding to the module */
    public $tab = 'front_office_features';

    /** @var array filled with known compliant PS versions */
    public $ps_versions_compliancy = [
        'min' => '1.7.3.3',
        'max' => '1.7.9.99'
    ];

    /** @var array Hooks used */
    public $hooks = [
        'displayAdminProductsExtra',
        'displayProductAdditionalInfo',
        'actionProductSave'
    ];

    /** Name of ModuleAdminController used for configuration */
    const MODULE_ADMIN_CONTROLLER = 'AdminTareacinco';

    /**
     * Constructor of module
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Modulo Tarea 5', [], 'Modules.Tareacinco.Admin');
        $this->description = $this->trans('Crear un módulo para configurar un texto para mostrar en la ficha de producto.', [], 'Modules.Tareacinco.Admin');
        $this->confirmUninstall = $this->trans('¿Estás seguro de que quieres desinstalar el módulo?', array(), 'Modules.Tareacinco.Admin');
        $this->templateFile = 'module:tareacinco/views/templates/hook/tareacinco.tpl';
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install() 
            && $this->registerHook($this->hooks)
            && $this->installDB();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDB();
    }

    public function installDB()
    {
        $return = true;
        $return &= Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'tareacinco` (
                `id_tareacinco` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_product` INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`id_tareacinco`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;'
        );

        $return &= Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'tareacinco_lang` (
                `id_tareacinco_lang` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_tareacinco` INT(10) UNSIGNED NOT NULL,
                `id_lang` INT(10) UNSIGNED NOT NULL,
                `text` varchar(255) NOT null,
                PRIMARY KEY (`id_tareacinco_lang`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;'
        );

        return $return;
    }

    public function uninstallDB($drop_table = true)
    {
        $ret = true;
        if ($drop_table) {
            $ret &= Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'tareacinco`')
                && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'tareacinco_lang`');
        }

        return $ret;
    }

    public function processSaveCustomText()
    {
        $shops = Tools::getValue('checkBoxShopAsso_configuration', [$this->context->shop->id]);
        $text = [];
        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $text[$lang['id_lang']] = (string) Tools::getValue('text_' . $lang['id_lang']);
        }

        $saved = true;
        foreach ($shops as $shop) {
            Shop::setContext(Shop::CONTEXT_SHOP, $shop);
            $id_product = Tools::getValue('id_product');
            $id_lang = Context::getContext()->language->id;

            $sql = new DbQuery();
            $sql->select('*')
                ->from('tareacinco', 't')
                ->where('id_product = '.(int)$id_product );
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
            unset($sql);

            if (!$row) {
                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'tareacinco` (`id_product`)
                    VALUES (' . (int) $id_product . ')'
                );
                $id_tareacinco = Db::getInstance()->Insert_ID();
            } else {
                $id_tareacinco = $row['id_tareacinco'];
            }

            if ($this->getTextData($id_product)) {
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'tareacinco_lang`
                    SET text = "' . pSQL($text[$id_lang], true) . '"
                    WHERE id_tareacinco = ' . (int) $id_tareacinco . '
                     AND  id_lang = ' . (int) $id_lang
                );
            } else {
                Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'tareacinco_lang` (`id_tareacinco`, `id_lang`, `text`)
                    VALUES (' . (int) $id_tareacinco . ', ' . (int) $id_lang . ', "' . pSQL($text[$id_lang], true) . '")'
                );
            }
        }

        return true;
    }

    public function hookActionProductSave()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        if (Tools::isSubmit('savetareacinco_customtext')) {
            if (Tools::getValue('text_' . $default_lang, false)) {
                $this->processSaveCustomText();
            }
        }
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $output = '';
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form = array(
            'tinymce' => true,
            'input' => array(
                'id_product' => array(
                    'type' => 'hidden',
                    'name' => 'id_product'
                ),
                'text' => array(
                    'type' => 'textarea',
                    'label' => $this->trans('Texto', array(), 'Modules.Tareacinco.Admin'),
                    'lang' => true,
                    'name' => 'text',
                    'cols' => 40,
                    'rows' => 10,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
            ),
            'buttons' => array(
                array(
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'title' => $this->trans('Back to list', array(), 'Admin.Actions'),
                    'icon' => 'process-icon-back'
                )
            )
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->name_controller = 'tareacinco';
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0)
            );
        }

        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'savetareacinco_customtext';

        $helper->fields_value = $this->getFormValues($params['id_product']);

        return $output . $helper->generateForm(array(array('form' => $fields_form)));
    }

    public function getFormValues($productId)
    {
        $fields_value = array();
        $text = $this->getTextData($productId);
        $lang = (int) Context::getContext()->language->id;
        $fields_value['text'][$lang] = $text['text'];
        $fields_value['id_product'] = $productId;

        return $fields_value;
    }

    public function hookDisplayProductAdditionalInfo($params)
    {

            $text = $this->getTextData($params['product']['id_product']);
            $this->smarty->assign([
                'tareacinco' => $text,
            ]);

        return $this->fetch($this->templateFile);
    }

    public function getTextData($productId)
    {
        $sql = new DbQuery();
        $sql->select('tl.text')
            ->from('tareacinco', 't')
            ->innerJoin('tareacinco_lang', 'tl', 't.id_tareacinco = tl.id_tareacinco')
            ->where('t.id_product = '.(int)$productId)
            ->where('tl.id_lang = '.(int) Context::getContext()->language->id);

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        if (!$result) {
            return false;
        }

        return $result;
    }
}