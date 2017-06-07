<?php
/**
 * Copyright (C) 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class Sucuri
 */
class Sucuri extends Module
{
    const WEBHOOK_URL = 'SUCURI_WEBHOOK';

    /**
     * CronJobs constructor.
     */
    public function __construct()
    {
        $this->name = 'sucuri';
        $this->tab = 'administration';
        $this->version = '1.0.0';

        $this->author = 'thirty bees';
        $this->need_instance = true;

        $this->bootstrap = true;

        $this->tb_versions_compliancy = '~1.0.1';

        parent::__construct();

        $this->displayName = $this->l('Sucuri');
        $this->description = $this->l('Module to clear the Sucuri cache when needed');
    }

    /**
     * Install this module
     *
     * @return bool Indicates whether the module has been successfully installed
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->registerHook('displayBackOfficeHeader');

        return true;
    }

    /**
     * Display back office header
     *
     * Used to listen to the clear cache event on the back office
     *
     * @return void
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('AdminPerformance') && Tools::isSubmit('empty_smarty_cache') && $webhookUrl = Configuration::get(static::WEBHOOK_URL)) {
            $guzzle = new \GuzzleHttp\Client();
            try {
                $guzzle->get($webhookUrl);
            } catch (Exception $e) {
            }
        }
    }

    public function getContent()
    {
        $this->postProcess();

        return $this->generateCredentialsForm();
    }

    protected function postProcess()
    {
        if (Tools::getValue('submitCredentials')) {
            Configuration::updateValue(static::WEBHOOK_URL, Tools::getValue(static::WEBHOOK_URL));
        }
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    protected function generateCredentialsForm()
    {
        $fields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Sucuri settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'label' => $this->l('Webhook URL'),
                        'name'  => static::WEBHOOK_URL,
                        'desc'  => $this->l('Webhook URL to clear the cache'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = '';
        $helper->submit_action = 'submitCredentials';
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];

        return $helper->generateForm([$fields]);
    }

    protected function getConfigFieldsValues()
    {
        return [
            static::WEBHOOK_URL => Configuration::get(static::WEBHOOK_URL),
        ];
    }
}
