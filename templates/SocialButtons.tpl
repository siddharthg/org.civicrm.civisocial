<div class="socialbutton" style="overflow: hidden;">
    <div class="social-wrap a">
    	<form action="{crmURL p='civicrm/civisocial/login/facebook'}" style ="float: left; padding: 5px; padding-left: 0px;" method="GET">
            <input type="hidden" name="redirect" value="{$redirecturl}">
        	<button type="submit" id="facebook">Sign in with Facebook</button>
        </form>
        <form method="GET" action="{crmURL p='civicrm/civisocial/login/googleplus'}" style ="float: left; padding: 5px;">
        	<input type="hidden" name="redirect" value="{$redirecturl}">
            <button type="submit" id="googleplus">Sign in with Google</button>
        </form>
        <form method="GET" action="{crmURL p='civicrm/civisocial/login/googleplus'}" style ="float: left; padding: 5px;">
            <input type="hidden" name="redirect" value="{$redirecturl}">
            <button type="submit" id="twitter">Sign in with Twitter</button>
        </form>
    </div>
</div>