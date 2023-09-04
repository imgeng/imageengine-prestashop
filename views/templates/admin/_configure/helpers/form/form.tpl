{**
 * 2023 ImageEngine.io
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.
 *
 * @author      ImageEngine.io <https://imageengine.io>
 * @copyright   Since 2023 ImageEngine.io
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
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
				<div class="alert medium-alert alert-warning" role="alert" style="margin-top: 17px">
					<p class="alert-text">
						We detected you are already using a media server: {$media_server_1}<br/>
						Enabling ImageEngine CDN will overwrite your current media server configuration.<br/>
						<a href="{$media_server_link}">Click here to check your media server configuration</a>.
					</p>
				</div>
			{/if}
			{if $alert_invalid}
				<div class="alert medium-alert alert-warning" role="alert" style="margin-top: 17px">
					<p class="alert-text">
						We detected an invalid configuration state: Media server is empty while ImageEngine CDN is enabled.<br/>
						Please Save configuration on this page to set proper Media server value.<br/>
						You can also <a href="{$media_server_link}">click here to check your media server configuration</a>.
					</p>
				</div>
			{/if}
			<div>
				<div class="alert alert-info" role="alert" style="margin-top: 17px">
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
