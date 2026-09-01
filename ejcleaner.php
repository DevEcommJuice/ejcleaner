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
    protected $config_keys = [
        'EJCLEANER_CLEAN_CACHE',
        'EJCLEANER_CLEAN_GUESTS',
        'EJCLEANER_CLEAN_CONNECTIONS',
        'EJCLEANER_CLEAN_PAGENOTFOUND',
        'EJCLEANER_CLEAN_FACETED',
        'EJCLEANER_CLEAN_CARTS',
        'EJCLEANER_CART_DAYS',
    ];

    public function __construct()
    {
        $this->name = 'ejcleaner';
        $this->tab = 'administration';
        $this->version = '2.1';
        $this->author = 'EcommJuice';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('EcommJuice - Cache & DB Cleaner Ultimate');
        $this->description = $this->l('Mantenimiento granular: Caché, Estadísticas, Facetas y Carritos.');
        $this->ps_versions_compliancy = ['min' => '1.6.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        foreach ($this->config_keys as $key) {
            $default = ($key === 'EJCLEANER_CART_DAYS') ? 30 : 1;
            Configuration::updateValue($key, $default);
        }
        Configuration::updateValue('EJCLEANER_CRON_TOKEN', Tools::passwdGen(16));

        return parent::install();
    }

    public function uninstall()
    {
        foreach ($this->config_keys as $key) {
            Configuration::deleteByName($key);
        }
        Configuration::deleteByName('EJCLEANER_CRON_TOKEN');

        return parent::uninstall();
    }

    public function getContent()
    {
        if (Tools::getValue('ajax') && Tools::getValue('ejcleaner_action')) {
            $this->handleAjaxClean();
        }

        $output = '';
        if (Tools::isSubmit('submitEjCleaner')) {
            foreach ($this->config_keys as $key) {
                $val = Tools::getValue($key);
                Configuration::updateValue($key, $val, false, Shop::getContextShopGroupID(), Shop::getContextShopID());
            }
            $output .= $this->displayConfirmation($this->l('Configuración guardada correctamente.'));
        }

        return $output . $this->renderInfoPanel() . $this->renderForm() . $this->renderCronInfo();
    }

    protected function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Opciones de Limpieza'),
                    'icon' => 'icon-trash',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Limpiar Caché de archivos'),
                        'name' => 'EJCLEANER_CLEAN_CACHE',
                        'desc' => $this->l('Borra directorios /var/cache (1.7/8.x) o /cache/smarty (1.6).'),
                        'values' => [['id' => 'active_on', 'value' => 1], ['id' => 'active_off', 'value' => 0]],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Vaciar Tabla de Invitados (ps_guest)'),
                        'name' => 'EJCLEANER_CLEAN_GUESTS',
                        'values' => [['id' => 'active_on', 'value' => 1], ['id' => 'active_off', 'value' => 0]],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Vaciar Conexiones (ps_connections)'),
                        'name' => 'EJCLEANER_CLEAN_CONNECTIONS',
                        'desc' => $this->l('Incluye ps_connections, ps_connections_source y ps_referrer_cache si existe.'),
                        'values' => [['id' => 'active_on', 'value' => 1], ['id' => 'active_off', 'value' => 0]],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Vaciar Logs 404 (ps_pagenotfound)'),
                        'name' => 'EJCLEANER_CLEAN_PAGENOTFOUND',
                        'values' => [['id' => 'active_on', 'value' => 1], ['id' => 'active_off', 'value' => 0]],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Optimizar Faceted Search (ps_layered_price_index)'),
                        'name' => 'EJCLEANER_CLEAN_FACETED',
                        'desc' => $this->l('Limpia el índice de precios de productos inexistentes o desactivados.'),
                        'values' => [['id' => 'active_on', 'value' => 1], ['id' => 'active_off', 'value' => 0]],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Vaciar Carritos Abandonados (ps_cart, ps_cart_product)'),
                        'name' => 'EJCLEANER_CLEAN_CARTS',
                        'desc' => $this->l('Elimina carritos sin pedido y limpia ps_cart_product huerfana.'),
                        'values' => [['id' => 'active_on', 'value' => 1], ['id' => 'active_off', 'value' => 0]],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Días de antigüedad para carritos'),
                        'name' => 'EJCLEANER_CART_DAYS',
                        'class' => 'fixed-width-xs',
                        'suffix' => $this->l('días'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar Configuración'),
                    'class' => 'btn btn-default pull-right'
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitEjCleaner';

        foreach ($this->config_keys as $key) {
            $helper->fields_value[$key] = Configuration::get($key);
        }

        return $helper->generateForm([$fields_form]);
    }

    protected function renderInfoPanel()
    {
        $id_shop = (int)$this->context->shop->id;
        $ajaxUrl = AdminController::$currentIndex . '&configure=' . $this->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $stats      = $this->getTableStatsForDisplay();
        $cacheBytes = $this->getCacheSize();

        $groups = [
            [
                'action'  => 'cache',
                'label'   => $this->l('Caché de archivos'),
                'tables'  => version_compare(_PS_VERSION_, '1.7.0.0', '>=') ? '/var/cache' : '/cache/smarty',
                'rows'    => '',
                'size'    => $this->formatSizeBytes($cacheBytes),
                'enabled' => (bool)Configuration::get('EJCLEANER_CLEAN_CACHE', null, null, $id_shop),
            ],
            [
                'action'  => 'guests',
                'label'   => $this->l('Invitados'),
                'tables'  => _DB_PREFIX_ . 'guest',
                'rows'    => number_format(isset($stats['guest']) ? $stats['guest']['rows'] : 0, 0, ',', '.'),
                'size'    => $this->formatSize(isset($stats['guest']) ? $stats['guest']['size_kb'] : 0),
                'enabled' => (bool)Configuration::get('EJCLEANER_CLEAN_GUESTS', null, null, $id_shop),
            ],
            [
                'action'  => 'connections',
                'label'   => $this->l('Conexiones'),
                'tables'  => _DB_PREFIX_ . 'connections + page + source',
                'rows'    => number_format(
                    (isset($stats['connections']) ? $stats['connections']['rows'] : 0)
                    + (isset($stats['connections_page']) ? $stats['connections_page']['rows'] : 0)
                    + (isset($stats['connections_source']) ? $stats['connections_source']['rows'] : 0),
                    0, ',', '.'
                ),
                'size'    => $this->formatSize(
                    (isset($stats['connections']) ? $stats['connections']['size_kb'] : 0)
                    + (isset($stats['connections_page']) ? $stats['connections_page']['size_kb'] : 0)
                    + (isset($stats['connections_source']) ? $stats['connections_source']['size_kb'] : 0)
                    + (isset($stats['referrer_cache']) ? $stats['referrer_cache']['size_kb'] : 0)
                ),
                'enabled' => (bool)Configuration::get('EJCLEANER_CLEAN_CONNECTIONS', null, null, $id_shop),
            ],
            [
                'action'  => 'pagenotfound',
                'label'   => $this->l('Logs 404'),
                'tables'  => _DB_PREFIX_ . 'pagenotfound',
                'rows'    => number_format(isset($stats['pagenotfound']) ? $stats['pagenotfound']['rows'] : 0, 0, ',', '.'),
                'size'    => $this->formatSize(isset($stats['pagenotfound']) ? $stats['pagenotfound']['size_kb'] : 0),
                'enabled' => (bool)Configuration::get('EJCLEANER_CLEAN_PAGENOTFOUND', null, null, $id_shop),
            ],
            [
                'action'  => 'faceted',
                'label'   => $this->l('Faceted Search'),
                'tables'  => _DB_PREFIX_ . 'layered_price_index',
                'rows'    => number_format(isset($stats['layered_price_index']) ? $stats['layered_price_index']['rows'] : 0, 0, ',', '.'),
                'size'    => $this->formatSize(isset($stats['layered_price_index']) ? $stats['layered_price_index']['size_kb'] : 0),
                'enabled' => (bool)Configuration::get('EJCLEANER_CLEAN_FACETED', null, null, $id_shop),
            ],
            [
                'action'  => 'carts',
                'label'   => $this->l('Carritos abandonados'),
                'tables'  => _DB_PREFIX_ . 'cart + ' . _DB_PREFIX_ . 'cart_product',
                'rows'    => number_format(
                    (isset($stats['cart']) ? $stats['cart']['rows'] : 0)
                    + (isset($stats['cart_product']) ? $stats['cart_product']['rows'] : 0),
                    0, ',', '.'
                ),
                'size'    => $this->formatSize(
                    (isset($stats['cart']) ? $stats['cart']['size_kb'] : 0)
                    + (isset($stats['cart_product']) ? $stats['cart_product']['size_kb'] : 0)
                ),
                'enabled' => (bool)Configuration::get('EJCLEANER_CLEAN_CARTS', null, null, $id_shop),
            ],
        ];

        $this->context->smarty->assign([
            'ejcleaner_groups'   => $groups,
            'ejcleaner_ajax_url' => $ajaxUrl,
            'ejcleaner_shop_id'  => $id_shop,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/status.tpl');
    }

    protected function renderCronInfo()
    {
        $token = Configuration::get('EJCLEANER_CRON_TOKEN');
        $cronUrl = $this->context->link->getModuleLink($this->name, 'cron', ['token' => $token, 'id_shop' => (int)$this->context->shop->id], true);
        $this->context->smarty->assign(['cron_url' => $cronUrl]);
        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    private function handleAjaxClean()
    {
        $allowed = ['all', 'cache', 'guests', 'connections', 'pagenotfound', 'faceted', 'carts'];
        $action  = Tools::getValue('ejcleaner_action');
        $id_shop = (int)(Tools::getValue('id_shop') ?: Context::getContext()->shop->id);

        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');

        if (!in_array($action, $allowed)) {
            die(json_encode(['success' => false, 'message' => 'Acción no válida.']));
        }

        try {
            $message = $this->executeCleaningGroup($action, $id_shop);
            die(json_encode(['success' => true, 'message' => $message]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    private function executeCleaningGroup($action, $id_shop)
    {
        switch ($action) {
            case 'all':
                $this->executeCleaning($id_shop);
                return $this->l('Limpieza completa ejecutada correctamente.');

            case 'cache':
                $this->deleteCacheFiles();
                return $this->l('Caché de archivos limpiada.');

            case 'guests':
                $cartResult = $this->cleanAbandonedCarts($id_shop);
                $guestDeleted = $this->cleanGuests();
                return number_format($cartResult['carts'], 0, ',', '.') . ' ' . $this->l('carritos eliminados')
                    . ' | ' . number_format($guestDeleted, 0, ',', '.') . ' ' . $this->l('guests eliminados');

            case 'connections':
                $parts = [];
                foreach (['connections_page', 'connections_source', 'connections', 'referrer_cache'] as $table) {
                    if ($this->tableExists($table)) {
                        $s = $this->truncateWithStats($table);
                        $parts[] = _DB_PREFIX_ . $table . ': ' . number_format($s['rows'], 0, ',', '.') . ' filas';
                    }
                }
                return implode(' | ', $parts);

            case 'pagenotfound':
                if ($this->tableExists('pagenotfound')) {
                    $s = $this->truncateWithStats('pagenotfound');
                    return number_format($s['rows'], 0, ',', '.') . ' ' . $this->l('filas eliminadas de') . ' ' . _DB_PREFIX_ . 'pagenotfound.';
                }
                return $this->l('Tabla no encontrada.');

            case 'faceted':
                $n = $this->optimizeFacetedIndex($id_shop);
                return number_format($n, 0, ',', '.') . ' ' . $this->l('registros eliminados de') . ' ' . _DB_PREFIX_ . 'layered_price_index.';

            case 'carts':
                $r = $this->cleanAbandonedCarts($id_shop);
                return number_format($r['carts'], 0, ',', '.') . ' ' . $this->l('carritos y')
                    . ' ' . number_format($r['cart_products'], 0, ',', '.') . ' ' . $this->l('líneas de carrito eliminados.');
        }
    }

    private function getTableStatsForDisplay()
    {
        $tables = [
            'guest', 'connections', 'connections_page', 'connections_source',
            'referrer_cache', 'pagenotfound', 'layered_price_index', 'cart', 'cart_product',
        ];

        $inList = implode(',', array_map(function ($t) {
            return "'" . pSQL(_DB_PREFIX_ . $t) . "'";
        }, $tables));

        $rows = Db::getInstance()->executeS(
            'SELECT TABLE_NAME, TABLE_ROWS, ROUND((data_length + index_length) / 1024, 2) AS size_kb
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (' . $inList . ')'
        );

        $result = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $key = str_replace(_DB_PREFIX_, '', $row['TABLE_NAME']);
                $result[$key] = ['rows' => (int)$row['TABLE_ROWS'], 'size_kb' => (float)$row['size_kb']];
            }
        }
        return $result;
    }

    private function getCacheSize()
    {
        $paths = version_compare(_PS_VERSION_, '1.7.0.0', '>=')
            ? [_PS_ROOT_DIR_ . '/var/cache/prod', _PS_ROOT_DIR_ . '/var/cache/dev']
            : [_PS_ROOT_DIR_ . '/cache/smarty/compile', _PS_ROOT_DIR_ . '/cache/smarty/cache'];

        $bytes = 0;
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $bytes += $this->dirSize($path);
            }
        }
        return $bytes;
    }

    private function dirSize($dir)
    {
        $bytes = 0;
        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $file) {
                if ($file->isFile()) {
                    $bytes += $file->getSize();
                }
            }
        } catch (Exception $e) {
        }
        return $bytes;
    }

    private function formatSizeBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    public function executeCleaning($id_shop = null)
    {
        $id_shop = (int)($id_shop ?: Context::getContext()->shop->id);
        $lines = [];

        // 1. Truncates - tablas hijas antes que padres para respetar FK
        $tableMapping = [
            'EJCLEANER_CLEAN_CONNECTIONS' => ['connections_page', 'connections_source', 'connections', 'referrer_cache'],
            'EJCLEANER_CLEAN_PAGENOTFOUND' => ['pagenotfound'],
        ];

        foreach ($tableMapping as $configKey => $tables) {
            if ((int)Configuration::get($configKey, null, null, $id_shop)) {
                foreach ($tables as $table) {
                    if ($this->tableExists($table)) {
                        $stats = $this->truncateWithStats($table);
                        $lines[] = _DB_PREFIX_ . $table . ': ' . $stats['rows'] . ' filas (' . $this->formatSize($stats['size_kb']) . ')';
                    }
                }
            }
        }

        // 2. Limpieza de Carritos Abandonados (antes que guests para liberar referencias)
        if ((int)Configuration::get('EJCLEANER_CLEAN_CARTS', null, null, $id_shop)) {
            $result = $this->cleanAbandonedCarts($id_shop);
            $lines[] = _DB_PREFIX_ . 'cart: ' . $result['carts'] . ' eliminados | '
                . _DB_PREFIX_ . 'cart_product: ' . $result['cart_products'] . ' huérfanos';
        }

        // 3. Limpieza Selectiva de Invitados (después de carritos para que no queden referencias)
        if ((int)Configuration::get('EJCLEANER_CLEAN_GUESTS', null, null, $id_shop)) {
            $deleted = $this->cleanGuests();
            $lines[] = _DB_PREFIX_ . 'guest: ' . $deleted . ' filas eliminadas';
        }

        // 4. Limpieza Quirúrgica de Facetas
        if ((int)Configuration::get('EJCLEANER_CLEAN_FACETED', null, null, $id_shop)) {
            $deleted = $this->optimizeFacetedIndex($id_shop);
            $lines[] = _DB_PREFIX_ . 'layered_price_index: ' . $deleted . ' filas eliminadas';
        }

        // 5. Limpieza de Caché de Archivos
        if ((int)Configuration::get('EJCLEANER_CLEAN_CACHE', null, null, $id_shop)) {
            $this->deleteCacheFiles();
            $lines[] = 'Caché de archivos limpiada';
        }

        if (!empty($lines)) {
            PrestaShopLogger::addLog(
                '[EjCleaner] Tienda #' . $id_shop . ' | ' . implode(' | ', $lines),
                1, null, null, null, true
            );
        }
    }

    private function tableExists($table)
    {
        return (bool)Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = \'' . pSQL(_DB_PREFIX_ . $table) . '\''
        );
    }

    private function truncateWithStats($table)
    {
        $db = Db::getInstance();
        $fullTable = pSQL(_DB_PREFIX_ . $table);
        $rows = (int)$db->getValue('SELECT COUNT(*) FROM `' . $fullTable . '`');
        $sizeKb = (float)$db->getValue(
            'SELECT ROUND((data_length + index_length) / 1024, 2)
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'' . $fullTable . '\''
        );
        $db->execute('TRUNCATE TABLE `' . $fullTable . '`');
        return ['rows' => $rows, 'size_kb' => $sizeKb];
    }

    private function formatSize($kb)
    {
        if ($kb >= 1048576) {
            return round($kb / 1048576, 2) . ' GB';
        }
        if ($kb >= 1024) {
            return round($kb / 1024, 2) . ' MB';
        }
        return round($kb, 2) . ' KB';
    }

    private function optimizeFacetedIndex($id_shop)
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'layered_price_index';
        $check = $db->executeS("SHOW TABLES LIKE '" . pSQL($table) . "'");
        if (empty($check)) {
            return 0;
        }

        $hasAttr = (bool)$db->getValue(
            'SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = "' . pSQL($table) . '"
              AND COLUMN_NAME = "id_product_attribute"'
        );

        $sql = 'DELETE lpi
            FROM `' . pSQL($table) . '` lpi
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = lpi.id_product';
        if ($hasAttr) {
            $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON pa.id_product_attribute = lpi.id_product_attribute
                WHERE (
                    p.id_product IS NULL
                    OR p.active = 0
                    OR (lpi.id_product_attribute > 0 AND pa.id_product_attribute IS NULL)
                )';
        } else {
            $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON pa.id_product = lpi.id_product
                WHERE (
                    p.id_product IS NULL
                    OR p.active = 0
                    OR pa.id_product IS NULL
                )';
        }
        $sql .= ' AND lpi.id_shop = ' . (int)$id_shop;

        $before = (int)$db->getValue('SELECT COUNT(*) FROM `' . pSQL($table) . '`');
        $db->execute($sql);
        $after = (int)$db->getValue('SELECT COUNT(*) FROM `' . pSQL($table) . '`');
        $db->execute('OPTIMIZE TABLE `' . pSQL($table) . '`');

        return $before - $after;
    }

    private function cleanGuests()
    {
        $db = Db::getInstance();
        $before = (int)$db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'guest`');
        $db->execute(
            'DELETE g FROM `' . _DB_PREFIX_ . 'guest` g
            LEFT JOIN `' . _DB_PREFIX_ . 'cart` c ON c.id_guest = g.id_guest
            WHERE c.id_guest IS NULL
              AND (g.id_customer IS NULL OR g.id_customer = 0)'
        );
        $db->execute('OPTIMIZE TABLE `' . _DB_PREFIX_ . 'guest`');
        $after = (int)$db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'guest`');
        return $before - $after;
    }

    private function cleanAbandonedCarts($id_shop)
    {
        $db = Db::getInstance();
        $days = (int)Configuration::get('EJCLEANER_CART_DAYS', null, null, $id_shop) ?: 30;

        $beforeCarts = (int)$db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'cart`');
        $db->execute(
            'DELETE c FROM `' . _DB_PREFIX_ . 'cart` c
             LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON c.id_cart = o.id_cart
             WHERE o.id_cart IS NULL
               AND c.date_add < DATE_SUB(NOW(), INTERVAL ' . (int)$days . ' DAY)
               AND c.id_shop = ' . (int)$id_shop
        );
        $afterCarts = (int)$db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'cart`');

        $beforeCp = (int)$db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'cart_product`');
        $db->execute(
            'DELETE cp FROM `' . _DB_PREFIX_ . 'cart_product` cp
             LEFT JOIN `' . _DB_PREFIX_ . 'cart` c ON cp.id_cart = c.id_cart
             WHERE c.id_cart IS NULL'
        );
        $afterCp = (int)$db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'cart_product`');

        return [
            'carts' => $beforeCarts - $afterCarts,
            'cart_products' => $beforeCp - $afterCp,
        ];
    }

    private function deleteCacheFiles()
    {
        $paths = (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) 
            ? [_PS_ROOT_DIR_ . '/var/cache/prod', _PS_ROOT_DIR_ . '/var/cache/dev']
            : [_PS_ROOT_DIR_ . '/cache/smarty/compile', _PS_ROOT_DIR_ . '/cache/smarty/cache'];

        foreach ($paths as $path) {
            if (is_dir($path)) $this->recursiveDelete($path);
        }
    }

    private function recursiveDelete($dir) {
        if (!is_dir($dir)) return;
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $target = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($target) ? $this->recursiveDelete($target) : unlink($target);
        }
    }
}
