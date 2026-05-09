<?php
/**
 * @author    EcommJuice <https://www.ecommjuice.com/>
 * @copyright EcommJuice
 * @license   Commercial
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class EjCleaner extends Module
{
    public function __construct()
    {
        $this->name = 'ejcleaner';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'EcommJuice';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('EcommJuice - Cache & DB Cleaner');
        $this->description = $this->l('Clears cache directories and truncates connection tables periodically via cron.');

        $this->ps_versions_compliancy = ['min' => '1.6.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        $token = Tools::passwdGen(16);
        Configuration::updateValue('EJCLEANER_CRON_TOKEN', $token);
        return parent::install();
    }

    public function uninstall()
    {
        Configuration::deleteByName('EJCLEANER_CRON_TOKEN');
        return parent::uninstall();
    }

    public function getContent()
    {
        $token = Configuration::get('EJCLEANER_CRON_TOKEN');
        $cronUrl = $this->context->link->getModuleLink(
            $this->name,
            'cron',
            ['token' => $token],
            true
        );

        $this->context->smarty->assign([
            'cron_url' => $cronUrl,
            'module_dir' => $this->_path
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function executeCleaning()
    {
        $this->cleanCacheDirectories();
        $this->truncateTables();
    }

    private function cleanCacheDirectories()
    {
        $pathsToClean = [];
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $pathsToClean[] = _PS_ROOT_DIR_ . '/var/cache/prod';
            $pathsToClean[] = _PS_ROOT_DIR_ . '/var/cache/dev';
        } else {
            $pathsToClean[] = _PS_ROOT_DIR_ . '/cache/smarty/compile';
            $pathsToClean[] = _PS_ROOT_DIR_ . '/cache/smarty/cache';
        }

        foreach ($pathsToClean as $path) {
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            }
        }
    }

    private function recursiveDelete($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        // We keep the main directory but empty it to avoid permission issues on the next write
    }

    private function truncateTables()
    {
        $tables = ['guest', 'connections_source', 'connections'];
        $db = Db::getInstance();
        foreach ($tables as $table) {
            $tableName = _DB_PREFIX_ . pSQL($table);
            $sql = 'TRUNCATE TABLE `' . $tableName . '`';
            try {
                $db->execute($sql);
            } catch (Exception $e) {
                PrestaShopLogger::addLog('EJCleaner Error: ' . $e->getMessage(), 3);
            }
        }
    }
}
