{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{extends file="helpers/form/form.tpl"}
{block name="field"}
	{if $input.name == $cfg_url_field_name}
		{$smarty.block.parent}
		<div class="col-lg-4"></div>
		<div class="col-lg-8">
			{if $register}
				Don't have an account yet? <br/>
				<a class="btn btn-primary" target="_blank" href="https://control.imageengine.io/register/website/?website={$url_hint_link}">
					<i class="material-icons">call_made</i>&nbsp; Claim your ImageEngine Account
				</a>
			{else}
				<a class="btn btn-primary" target="_blank" href="{$url_hint_link}"><i class="material-icons">call_made</i>&nbsp; ImageEngine Account Control Panel</a>
			{/if}
			{if $alert_overwrite}
				<div class="alert medium-alert alert-warning" role="alert">
					<p class="alert-text">
						We detected you are already using a media server: {$media_server_1}<br/>
						Enabling ImageEngine CDN will overwrite your current media server configuration.<br/>
						<a href="{$media_server_link}">Click here to check your media server configuration</a>.
					</p>
				</div>
			{/if}
			{if $alert_invalid}
				<div class="alert medium-alert alert-warning" role="alert">
					<p class="alert-text">
						We detected an invalid configuration state: Media server is empty while ImageEngine CDN is enabled.<br/>
						Please Save configuration on this page to set proper Media server value.<br/>
						You can also <a href="{$media_server_link}">click here to check your media server configuration</a>.
					</p>
				</div>
			{/if}
			<div>
				<div class="alert alert-info" role="alert">
					<p class="alert-text">
						CSS and JavaScript will also be cached and served by ImageEngine CDN.
					</p>
				</div>
			</div>
		</div>

	{else}
		{$smarty.block.parent}
	{/if}
{/block}
