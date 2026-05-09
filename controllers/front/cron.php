<?php
/**
 * @author    EcommJuice <https://www.ecommjuice.com/>
 * @copyright EcommJuice
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class EjCleanerCronModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = true;
        $token = Tools::getValue('token');
        $validToken = Configuration::get('EJCLEANER_CRON_TOKEN');

        if (!$token || $token !== $validToken) {
            header('HTTP/1.1 403 Forbidden');
            die('Access denied: Invalid token.');
        }

        try {
            $this->module->executeCleaning();
            die('OK: Cache and tables cleared.');
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Error');
            die('Error: ' . $e->getMessage());
        }
    }
}
