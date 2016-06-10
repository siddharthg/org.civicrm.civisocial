<div class="civisocial-wrapper">
  {crmAPI var='result' entity='Setting' action='getvalue' name="enable_facebook"}
  {if $result.values eq '1'}
    <a class="btn btn-facebook" href="{crmURL p='civicrm/civisocial/login/facebook'}">Sign in with Facebook</a>
  {/if}
  {crmAPI var='result' entity='Setting' action='getvalue' name="enable_googleplus"}
  {if $result.values eq '1'}
    <a class="btn btn-googleplus" href="{crmURL p='civicrm/civisocial/login/googleplus'}">Sign in with Google</a>
  {/if}
  {crmAPI var='result' entity='Setting' action='getvalue' name="enable_twitter"}
  {if $result.values eq '1'}
    <a class="btn btn-twitter" href="{crmURL p='civicrm/civisocial/login/twitter'}">Sign in with Twitter</a>
  {/if}
</div>
