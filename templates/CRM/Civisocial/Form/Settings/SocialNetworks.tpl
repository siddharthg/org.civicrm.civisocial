<div class="crm-block crm-form-block crm-date-form-block civisocial-wrapper">
  <div class="civisocial-box">
    <div class="help">
      Use this screen to connect to your organization's accounts on social networks for social insight and other features.
    </div>
  </div>

  {* Facebook Section *}
  <div class="civisocial-box">
    {if $facebookPageConnected eq '1'}
      <div class="box-item">
        <div class="image">
          <img src="{$facebookPagePicture}">
          <div class="logo bg-facebook"></div>
        </div>
        <div class="content">
          <div class="name">{$facebookPageName}</div>
          <div><a href="{$disconnectUrl}">Disconnect</a></div>
        </div>
      </div>
      <div class="spacer"></div>
      <div class="box-item">
        <label class="input">{$form.integrate_facebook_events.html} Integrate Facebook events</label>
      </div>
    {else}
      <div class="crm-section">
          <a class="btn btn-facebook bg-facebook" href="{crmURL p='civicrm/admin/civisocial/network/connect/facebookpage'}?continue={$currentUrl}">Connect Facebook Page</a>
      </div>
    {/if}
  </div>

  {* Twitter Section *}
  <div class="civisocial-box">
    <div class="crm-section">
        <a class="btn btn-twitter bg-twitter" href="#">Connect Twitter</a>
    </div>
  </div>
</div>
