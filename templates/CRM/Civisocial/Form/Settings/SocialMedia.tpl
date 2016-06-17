{* HEADER *}
<div class="crm-block crm-form-block crm-date-form-block">
	<div class="help">
	  Use this screen to connect to social networks for social insight and other features.
	</div>
	<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
	  <div class="crm-section">
	    <div class="label">{$form.facebook_page_url.label}</div>
	    <div class="content">
        {$form.facebook_page_url.html|crmAddClass:huge}
        <div class="spacer"></div>
        <a href="#">Connect to Facebook Page</a>
      </div>
	    <div class="clear"></div>
	  </div>
	  <div class="crm-section">
	    <div class="label">{$form.twitter_timeline_url.label}</div>
	    <div class="content">
        {$form.twitter_timeline_url.html|crmAddClass:huge}
        <div class="spacer"></div>
        <a href="#">Connect to Twitter Timeline</a>
      </div>
	    <div class="clear"></div>
	  </div>
	<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
