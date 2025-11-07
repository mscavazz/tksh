<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class TokenShop_MetamaskConnect extends Module
{
    public function __construct()
    {
        $this->name = 'tokenshop_metamaskconnect';
        $this->version = '1.0.11';
        $this->author = 'TokenShop';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('TokenShop MetamaskConnect');
        $this->description = $this->l('Connexion via MetaMask (desktop & mobile) - Polygon only. Compatible PrestaShop 1.7.6+.');
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayCustomerAccount')
            && $this->createTable(true);
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->dropTable();
    }

    private function createTable($forceDrop = false)
    {
        $table = _DB_PREFIX_ . 'tokenshop_metamaskconnect';
        if ($forceDrop) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS `' . $table . '`');
        }
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id_wallet` INT AUTO_INCREMENT PRIMARY KEY,
            `id_customer` INT DEFAULT NULL,
            `wallet_address` VARCHAR(255) NOT NULL,
            `network` VARCHAR(50) DEFAULT NULL,
            `label` VARCHAR(100) DEFAULT NULL,
            `date_add` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_customer` (`id_customer`),
            UNIQUE KEY `uniq_wallet` (`wallet_address`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4;";
        return Db::getInstance()->execute($sql);
    }

    private function dropTable()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'tokenshop_metamaskconnect`';
        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitTokenShopMetamaskConnect')) {
            $output .= $this->displayConfirmation($this->l('Paramètres mis à jour.'));
        }

        $this->context->smarty->assign([
            'form_action' => AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
        ]);
        return $output . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function hookDisplayHeader($params)
    {
        $this->context->controller->registerJavascript(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            array('server' => 'remote', 'position' => 'head', 'priority' => 20)
        );

        $this->context->controller->registerJavascript(
            'ts_metamask_manager',
            'modules/' . $this->name . '/views/js/metamask_manager.js',
            array('position' => 'bottom', 'priority' => 100)
        );

        $this->context->controller->registerStylesheet(
            'ts_metamask_css',
            'modules/' . $this->name . '/views/css/wallet.css',
            array('media' => 'all', 'priority' => 100)
        );

        Media::addJsDef(array(
            'ts_urls' => array(
                'save' => $this->context->link->getModuleLink($this->name, 'ajax'),
                'get' => $this->context->link->getModuleLink($this->name, 'ajax') . '?action=get',
                'delete' => $this->context->link->getModuleLink($this->name, 'ajax') . '?action=delete'
            ),
            'ts_module_name' => $this->name,
        ));
    }

    public function hookDisplayCustomerAccount($params)
    {
        return $this->display(__FILE__, 'views/templates/hook/account_block.tpl');
    }
}
