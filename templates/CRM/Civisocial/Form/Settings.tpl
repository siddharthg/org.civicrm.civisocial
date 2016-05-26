{* HEADER *}
<div class="crm-block crm-form-block crm-date-form-block">
	<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

	<fieldset><legend>{ts}Facebook{/ts}</legend>
	  <div class="crm-section">
	    <div class="label">{$form.enable_facebook.label}</div>
	    <div class="content">{$form.enable_facebook.html}</div>
	    <div class="clear"></div>
	  </div>
	  <div class="crm-section">
	    <div class="label">{$form.facebook_app_id.label}</div>
	    <div class="content">{$form.facebook_app_id.html|crmAddClass:big|crmAddClass:big}</div>
	    <div class="clear"></div>
	  </div>
	  <div class="crm-section">
	    <div class="label">{$form.facebook_secret.label}</div>
	    <div class="content">{$form.facebook_secret.html|crmAddClass:big}</div>
	    <div class="clear"></div>
	  </div>
	</fieldset>
	<fieldset><legend>{ts}Google{/ts}</legend>
	  <div class="crm-section">
	    <div class="label">{$form.enable_googleplus.label}</div>
	    <div class="content">{$form.enable_googleplus.html}</div>
	    <div class="clear"></div>
	  </div>
  	  <div class="crm-section">
	    <div class="label">{$form.google_plus_key.label}</div>
	    <div class="content">{$form.google_plus_key.html|crmAddClass:big}</div>
	    <div class="clear"></div>
	  </div>
	  <div class="crm-section">
	    <div class="label">{$form.google_plus_secret.label}</div>
	    <div class="content">{$form.google_plus_secret.html|crmAddClass:big}</div>
	    <div class="clear"></div>
	  </div>
	</fieldset>
	<fieldset><legend>{ts}Twitter{/ts}</legend>
	  <div class="crm-section">
	    <div class="label">{$form.enable_twitter.label}</div>
	    <div class="content">{$form.enable_twitter.html}</div>
	    <div class="clear"></div>
	  </div>
	  <div class="crm-section">
	    <div class="label">{$form.twitter_consumer_key.label}</div>
	    <div class="content">{$form.twitter_consumer_key.html|crmAddClass:big}</div>
	    <div class="clear"></div>
	  </div>
	  <div class="crm-section">
	    <div class="label">{$form.twitter_consumer_secret.label}</div>
	    <div class="content">{$form.twitter_consumer_secret.html|crmAddClass:big}</div>
	    <div class="clear"></div>
	  </div>
	</fieldset>
	<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
