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
        $id_shop = (int)Tools::getValue('id_shop');

        if (!$token || $token !== Configuration::get('EJCLEANER_CRON_TOKEN')) {
            header('HTTP/1.1 403 Forbidden');
            die('Access denied: Invalid token.');
        }

        // Si estamos en entorno Multitienda, forzamos el contexto de la tienda recibida por parámetro
        if ($id_shop && Shop::isFeatureActive()) {
            $shop = new Shop($id_shop);
            if (Validate::isLoadedObject($shop)) {
                $this->context->shop = $shop;
            }
        }

        try {
            $this->module->executeCleaning($id_shop);
            die('OK: Limpieza procesada correctamente para la tienda ID: ' . ($id_shop ?: 'Estándar'));
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Error');
            die('Error: ' . $e->getMessage());
        }
    }
}
