<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ImageEngine extends Module
{
    /**
     * Client hints.
     *
     * @var []string
     */
    private static $client_hints = array(
        'sec-ch-dpr',
        'sec-ch-width',
        'sec-ch-viewport-width',
        'ect', //kept in for legacy reasons
        'sec-ch-ect',
        'sec-ch-ua-full-version',
        'sec-ch-ua-full-version-list',
        'sec-ch-ua-platform-version',
        'sec-ch-ua-arch',
        'sec-ch-ua-wow64',
        'sec-ch-ua-bitness',
        'sec-ch-ua-model',
        /**
         * Disabled for CORS compatibility:
         * 'ECT',
         * 'Device-Memory',
         * 'RTT',
         * 'Downlink',
         */
    );

    private const CFG_ACTIVE = 'IMAGEENGINE_ACTIVE';
    private const CFG_URL = 'IMAGEENGINE_CDN_URL';
    private const CFG_PRECONNECT = 'IMAGEENGINE_PRECONNECT';
    private const CFG_CLIENT_HINTS = 'IMAGEENGINE_CLIENT_HINTS';
    private const CFG_PERMISSIONS_POLICY = 'IMAGEENGINE_PERMISSIONS_POLICY';
    private const PS_MEDIA_1 = 'PS_MEDIA_SERVER_1';
    private const PS_MEDIA_2 = 'PS_MEDIA_SERVER_2';
    private const PS_MEDIA_3 = 'PS_MEDIA_SERVER_3';

    public function __construct()
    {
        $this->name = 'imageengine';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'ImageEngine.io';
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
            && $this->registerHook('displayHeader')
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
            $configValueActive = (bool) Tools::getValue(self::CFG_ACTIVE);
            $configValueUrl = (string) Tools::getValue(self::CFG_URL);
            $configValuePreconnect = (bool) Tools::getValue(self::CFG_PRECONNECT);
            $configValueClientHints = (bool) Tools::getValue(self::CFG_CLIENT_HINTS);
            $configValuePermissionsPolicy = (bool) Tools::getValue(self::CFG_PERMISSIONS_POLICY);
            $validDomain = $this->isValidDomain($configValueUrl);

            if ($configValueActive && (!Validate::isUrl($configValueUrl) || !$validDomain)) {
                $output .= $this->displayError($this->l('Cannot enable with invalid value for CDN URL'));
            } else {
                if ($configValueActive) {
                    // Set media servers 1
                    Configuration::updateValue(self::PS_MEDIA_1, $configValueUrl);
                    // Erase media servers 2 & 3 if set
                    if (Configuration::get(self::PS_MEDIA_2)) {
                        Configuration::updateValue(self::PS_MEDIA_2, '');
                    }
                    if (Configuration::get(self::PS_MEDIA_3)) {
                        Configuration::updateValue(self::PS_MEDIA_3, '');
                    }
                } else {
                    // Erase media server 1 only if it is set to same value as ImageEngine CDN URL
                    if ($configValueUrl == Configuration::get(self::PS_MEDIA_1)) {
                        Configuration::updateValue(self::PS_MEDIA_1, '');
                    }
                }

                Configuration::updateValue(self::CFG_URL, $configValueUrl);
                Configuration::updateValue(self::CFG_ACTIVE, $configValueActive);
                Configuration::updateValue(self::CFG_PRECONNECT, $configValuePreconnect);
                Configuration::updateValue(self::CFG_CLIENT_HINTS, $configValueClientHints);
                Configuration::updateValue(self::CFG_PERMISSIONS_POLICY, $configValuePermissionsPolicy);
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
        $accountLink = 'https://control.imageengine.io/?utm_source=prestashop_module&utm_medium=imageengine&utm_campaign=admin';
        $currentCdnUrl = Configuration::get(self::CFG_URL);
        if (empty($currentCdnUrl) || strpos($currentCdnUrl, 'imgeng.in') === false) {
            $textAccountInfo = 'Don\'t have an account yet? <br/>'
                . '<a class="btn btn-primary" target="_blank" href="https://control.imageengine.io/register/website/?website='
                . $websiteOrigin
                . '"><i class="material-icons">call_made</i>&nbsp; Claim your ImageEngine Account</a>';
        } else {
            $textAccountInfo =
                '<a class="btn btn-primary" target="_blank" href="'
                . $accountLink . '"><i class="material-icons">call_made</i>&nbsp; ImageEngine Account Control Panel</a>';
        }

        if (
            !empty(Configuration::get(self::PS_MEDIA_1))
            && Configuration::get(self::PS_MEDIA_1) != Configuration::get(self::CFG_URL)
        ) {
            $mediaServerLink = $this->context->link->getAdminLink('AdminPerformance', true) . '#media_servers_media_server_one';
            $textMediaServerWarning = '
            <div class="alert medium-alert alert-warning" role="alert">
                <p class="alert-text">
                    We detected you are already using a media server: ' . Configuration::get(self::PS_MEDIA_1)
                . '<br/>
                    Enabling ImageEngine CDN will overwrite your current media server configuration.<br/>
                    <a href="' . $mediaServerLink . '">Click here to check your media server configuration</a>.
                </p>
            </div>
            ';
        } else if (
            empty(Configuration::get(self::PS_MEDIA_1))
            && !empty(Configuration::get(self::CFG_URL))
        ) {
            $mediaServerLink = $this->context->link->getAdminLink('AdminPerformance', true) . '#media_servers_media_server_one';
            $textMediaServerWarning = '
            <div class="alert medium-alert alert-warning" role="alert">
                <p class="alert-text">
                    We detected an invalid configuration state: Media server is empty while ImageEngine CDN is enabled.<br/>
                    Please Save configuration on this page to set proper Media server value.<br/>
                    You can also <a href="' . $mediaServerLink . '">click here to check your media server configuration</a>.
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
                        'name' => self::CFG_ACTIVE,
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
                        'name' => self::CFG_URL,
                        'desc' =>
                            $textAccountInfo
                            // below we display info panels unrelated to CDN URL field, until we refactor to a template
                            . '<br/>' . $textMediaServerWarning
                            . '<br/>' . $textInfo
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Add Preconnect directive to all pages'),
                        'name' => self::CFG_PRECONNECT,
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
                        'type' => 'switch',
                        'label' => $this->l('Add Client hints directives to all pages'),
                        'name' => self::CFG_CLIENT_HINTS,
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
                        'type' => 'switch',
                        'label' => $this->l('Add Permissions policy directives to all pages'),
                        'name' => self::CFG_PERMISSIONS_POLICY,
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

        $helper->fields_value[self::CFG_ACTIVE] = Tools::getValue(self::CFG_ACTIVE, Configuration::get(self::CFG_ACTIVE));
        $helper->fields_value[self::CFG_URL] = Tools::getValue(self::CFG_URL, Configuration::get(self::CFG_URL));
        $helper->fields_value[self::CFG_PRECONNECT] = Tools::getValue(self::CFG_PRECONNECT, Configuration::get(self::CFG_PRECONNECT));
        $helper->fields_value[self::CFG_CLIENT_HINTS] = Tools::getValue(self::CFG_CLIENT_HINTS, Configuration::get(self::CFG_CLIENT_HINTS));
        $helper->fields_value[self::CFG_PERMISSIONS_POLICY] = Tools::getValue(self::CFG_PERMISSIONS_POLICY, Configuration::get(self::CFG_PERMISSIONS_POLICY));

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
     * Inject preconnect and client hints as meta tags
     * <link rel="preconnect" href="https://xyz.cdn.imgeng.in">
     */
    public function hookDisplayAfterTitleTag(array $params): string
    {
        $html = '';
        if ((bool) Configuration::get(self::CFG_ACTIVE) !== true) {
            return $html;
        }

        // Preconnect link / Resource hints
        if ((bool) Configuration::get(self::CFG_PRECONNECT) === true) {
            $cdnUrl = Configuration::get(self::CFG_URL);
            $fullCdnUrl = (Tools::usingSecureMode() ? 'https://' : 'http://') . $cdnUrl;
            $html .= '    <link rel="preconnect" href="' . $fullCdnUrl . '">' . "\n";
        }

        // Client hints
        if ((bool) Configuration::get(self::CFG_CLIENT_HINTS) === true) {
            $html .= '    <meta http-equiv="Accept-CH" content="' . (implode(', ', self::$client_hints)) . '">' . "\n";
        }

        // Tip: Permissions policy cannot be sent as meta http-equiv, so it remains only in the response headers

        return $html;
    }

    /**
     * Inject response header directives: preconnect, client hints, permissions policy
     */
    public function hookDisplayHeader(array $params): void
    {
        if ((bool) Configuration::get(self::CFG_ACTIVE) !== true) {
            return;
        }

        $host = Configuration::get(self::CFG_URL);
        $protocol = sprintf("http%s", Tools::usingSecureMode() ? 's' : '');

        // Preconnect header / Resource hints
        if ((bool) Configuration::get(self::CFG_PRECONNECT) === true) {
            header( 'Link: ' . "<{$protocol}://{$host}>; rel=preconnect" );
        }

        // Client hints header
        if ((bool) Configuration::get(self::CFG_CLIENT_HINTS) === true) {
            header( 'Accept-CH: '. strtolower( implode( ', ', self::$client_hints ) ) );
        }

        // Permissions policy header
        if ((bool) Configuration::get(self::CFG_PERMISSIONS_POLICY) === true) {
            $permissions = array();
            foreach (self::$client_hints as $hint) {
                $get_hint = str_replace('sec-', '', $hint);
                if ($get_hint === 'ect') continue;
                $permissions[] = strtolower("{$get_hint}=(\"{$protocol}://{$host}\")");
            }
            // This header replaced Feature-Policy in Chrome 88, released in January 2021.
            // @see https://github.com/w3c/webappsec-permissions-policy/blob/main/permissions-policy-explainer.md#appendix-big-changes-since-this-was-called-feature-policy .
            header('Permissions-Policy: ' . strtolower(implode(', ', $permissions)));
        }
    }
}
