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
    ];

    public function __construct()
    {
        $this->name = 'ejcleaner';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'EcommJuice';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('EcommJuice - Cache & DB Cleaner');
        $this->description = $this->l('Configuración personalizada para limpieza de caché y tablas.');
        $this->ps_versions_compliancy = ['min' => '1.6.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        // Valores por defecto: todo activado
        foreach ($this->config_keys as $key) {
            Configuration::updateValue($key, 1);
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

    /**
     * Gestión del Back Office
     */
    public function getContent()
    {
        $output = '';

        // Guardar configuración si se envía el formulario
        if (Tools::isSubmit('submitEjCleaner')) {
            foreach ($this->config_keys as $key) {
                Configuration::updateValue($key, (int)Tools::getValue($key));
            }
            $output .= $this->displayConfirmation($this->l('Configuración actualizada.'));
        }

        return $output . $this->renderForm() . $this->renderCronInfo();
    }

    protected function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Ajustes de Limpieza'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Vaciar directorios de Caché'),
                        'name' => 'EJCLEANER_CLEAN_CACHE',
                        'is_bool' => true,
                        'desc' => $this->l('Elimina archivos temporales de Smarty y Symfony.'),
                        'values' => [['id' => 'active_on', 'value' => 1, 'label' => $this->l('Sí')], ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')]],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Vaciar ps_guest'),
                        'name' => 'EJCLEANER_CLEAN_GUESTS',
                        'is_bool' => true,
                        'desc' => $this->l('Elimina IDs de invitados antiguos (Recomendado).'),
                        'values' => [['id' => 'active_on', 'value' => 1], ['id' => 'active_off', 'value' => 0]],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Vaciar ps_connections'),
                        'name' => 'EJCLEANER_CLEAN_CONNECTIONS',
                        'is_bool' => true,
                        'desc' => $this->l('Limpia el registro histórico de conexiones y fuentes.'),
                        'values' => [['id' => 'active_on', 'value' => 1], ['id' => 'active_off', 'value' => 0]],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Vaciar ps_pagenotfound'),
                        'name' => 'EJCLEANER_CLEAN_PAGENOTFOUND',
                        'is_bool' => true,
                        'desc' => $this->l('Elimina el log de errores 404 registrados en la DB.'),
                        'values' => [['id' => 'active_on', 'value' => 1], ['id' => 'active_off', 'value' => 0]],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
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
        $helper->allow_callbacks = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitEjCleaner';

        foreach ($this->config_keys as $key) {
            $helper->fields_value[$key] = Configuration::get($key);
        }

        return $helper->generateForm([$fields_form]);
    }

    protected function renderCronInfo()
    {
        $token = Configuration::get('EJCLEANER_CRON_TOKEN');
        $cronUrl = $this->context->link->getModuleLink($this->name, 'cron', ['token' => $token], true);

        $this->context->smarty->assign(['cron_url' => $cronUrl]);
        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    /**
     * Lógica de limpieza con validación de configuración
     */
    public function executeCleaning()
    {
        $db = Db::getInstance();

        // Limpieza de Tablas
        $tableMapping = [
            'EJCLEANER_CLEAN_GUESTS' => ['guest'],
            'EJCLEANER_CLEAN_CONNECTIONS' => ['connections', 'connections_source'],
            'EJCLEANER_CLEAN_PAGENOTFOUND' => ['pagenotfound'],
        ];

        foreach ($tableMapping as $configKey => $tables) {
            if ((int)Configuration::get($configKey)) {
                foreach ($tables as $table) {
                    $db->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . pSQL($table) . '`');
                }
            }
        }

        // Limpieza de Directorios
        if ((int)Configuration::get('EJCLEANER_CLEAN_CACHE')) {
            $paths = (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) 
                ? [_PS_ROOT_DIR_ . '/var/cache/prod', _PS_ROOT_DIR_ . '/var/cache/dev']
                : [_PS_ROOT_DIR_ . '/cache/smarty/compile', _PS_ROOT_DIR_ . '/cache/smarty/cache'];

            foreach ($paths as $path) {
                if (is_dir($path)) {
                    $this->recursiveDelete($path);
                }
            }
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
