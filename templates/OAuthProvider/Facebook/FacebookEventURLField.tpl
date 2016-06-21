<script type="text/javascript">
{if $fbEventEnabled eq '1'}
  cj('<tr><td class="label">{$form.facebook_event_url.label}</td><td>{$form.facebook_event_url.html|crmAddClass:huge}<br/><span class="description">{ts}Please ensure that the Facebook event is public.{/ts}</span></td></tr>').insertAfter('tr.crm-event-manage-eventinfo-form-block-title');
{else}
  cj('<div class="help"><a href="{crmURL p="civicrm/admin/civisocial/networks"}">Connect Facebook page</a> to integrate Facebook event.</div>').insertBefore('.crm-block table');    
{/if}
</script>
