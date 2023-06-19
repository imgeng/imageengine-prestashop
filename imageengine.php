<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ImageEngine extends Module
{
    public function __construct()
    {
        $this->name = 'imageengine';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Modig Agency';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Image Engine');
        $this->description = $this->l('Automatically add ImageEngine CDN to media url.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * @throws PrestaShopException
     */
    public function install(): bool
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return (
            parent::install()
            && $this->registerHook('displayAfterTitleTag')
        );
    }

    /**
     * First method called when configuration page is loaded
     * @return string
     */
    public function getContent(): string
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $configValueActive = (bool) Tools::getValue('IMAGEENGINE_ACTIVE');
            $configValueUrl = (string) Tools::getValue('IMAGEENGINE_CDN_URL');
            $validDomain = $this->isValidDomain($configValueUrl);

            if ($configValueActive && (!Validate::isUrl($configValueUrl) || !$validDomain)) {
                $output .= $this->displayError($this->l('Cannot enable with invalid value for CDN URL'));
            } else {
                if ($configValueActive) {
                    // Set media servers 1
                    Configuration::updateValue('PS_MEDIA_SERVER_1', $configValueUrl);
                    // Erase media servers 2 & 3 if set
                    if (Configuration::get('PS_MEDIA_SERVER_2')) {
                        Configuration::updateValue('PS_MEDIA_SERVER_2', '');
                    }
                    if (Configuration::get('PS_MEDIA_SERVER_3')) {
                        Configuration::updateValue('PS_MEDIA_SERVER_3', '');
                    }
                } else {
                    // Erase media server 1 only if it is set to same value as ImageEngine CDN URL
                    if (Configuration::get('IMAGEENGINE_CDN_URL') == Configuration::get('PS_MEDIA_SERVER_1')) {
                        Configuration::updateValue('PS_MEDIA_SERVER_1', '');
                    }
                }

                Configuration::updateValue('IMAGEENGINE_CDN_URL', $configValueUrl);
                Configuration::updateValue('IMAGEENGINE_ACTIVE', $configValueActive);
            }

            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output . $this->displayForm();
    }

    public function displayForm(): string
    {
        $websiteOrigin = $this->context->link->getBaseLink();
        $textMediaServerWarning = '';
        $textInfo = '
            <div class="alert alert-info" role="alert">
                <p class="alert-text">
                    CSS and JavaScript will also be cached and served by ImageEngine CDN.
                </p>
            </div>
            ';

        if (
            !empty(Configuration::get('PS_MEDIA_SERVER_1'))
            && Configuration::get('PS_MEDIA_SERVER_1') != Configuration::get('IMAGEENGINE_CDN_URL')
        ) {
            $mediaServerLink = $this->context->link->getAdminLink('AdminPerformance', true) . '#media_servers_media_server_one';
            $textMediaServerWarning = '
            <div class="alert medium-alert alert-warning" role="alert">
                <p class="alert-text">
                    We detected you are already using a media server: '.Configuration::get('PS_MEDIA_SERVER_1').'<br/>
                    Enabling ImageEngine CDN will overwrite your current media server configuration.<br/>
                    <a href="'.$mediaServerLink.'">Click here to check your media server configuration</a>.
                </p>
            </div>
            ';
        }

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('ImageEngine CDN Settings'),
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable CDN'),
                        'name' => 'IMAGEENGINE_ACTIVE',
                        'required' => true,
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('CDN URL'),
                        'name' => 'IMAGEENGINE_CDN_URL',
                        'desc' => 'Don\'t have an account yet? <br/>'
                            . '<a class="btn btn-primary" target="_blank" href="https://control.imageengine.io/register/website/?website='
                            . $websiteOrigin
                            . '"><i class="material-icons">call_made</i>&nbsp; Claim your ImageEngine Account</a>'
                            . '<br/>' . $textMediaServerWarning
                            . '<br/>' . $textInfo
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Add preconnect headers to all pages'),
                        'name' => 'IMAGEENGINE_PRECONNECT',
                        'required' => true,
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ]
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        $helper->module = $this;
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper->fields_value['IMAGEENGINE_CDN_URL'] = Tools::getValue('IMAGEENGINE_CDN_URL', Configuration::get('IMAGEENGINE_CDN_URL'));
        $helper->fields_value['IMAGEENGINE_ACTIVE'] = Tools::getValue('IMAGEENGINE_ACTIVE', Configuration::get('IMAGEENGINE_ACTIVE'));

        return $helper->generateForm([$form]);
    }

    /**
     * @param string $domainName
     * @return bool
     */
    private function isValidDomain(string $domainName): bool
    {
        if (false !== filter_var($domainName, FILTER_VALIDATE_DOMAIN)) {
            return false !== filter_var(gethostbyname($domainName), FILTER_VALIDATE_IP);
        }

        return false;
    }

    /**
     * Inject preconnect header directive meta tag
     * <link rel="preconnect" href="https://xyz.cdn.imgeng.in">
     */
    public function hookDisplayAfterTitleTag(array $params): string
    {
        if (
            ((bool) Configuration::get('IMAGEENGINE_ACTIVE') === true)
            && ((bool) Configuration::get('IMAGEENGINE_PRECONNECT') === true)
        ) {
            $cdnUrl = Configuration::get('IMAGEENGINE_CDN_URL');
            $fullCdnUrl = (Tools::usingSecureMode() ? 'https://' : 'http://') . $cdnUrl;
            return '<link rel="preconnect" href="' . $fullCdnUrl . '">';
        }
        return '';
    }
}
