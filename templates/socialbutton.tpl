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
</div>
</div>

{literal}
<style type="text/css">

div.socialbutton {
	margin-top: 10px;
}

 div.social-wrap button {
    padding-right: 45px;
    height: 35px;
    background: none;
    border: none;
    /*display: block;*/
    background-size: 35px 35px;
    background-position: right center;
    background-repeat: no-repeat;
    border-radius: 4px;
    color: white;
    font-size: 14px;
    margin-bottom: 15px;
    width: 210px;
    border-bottom: 2px solid transparent;
    border-left: 1px solid transparent;
    border-right: 1px solid transparent;
    box-shadow: 0 4px 2px -2px gray;
    text-shadow: rgba(0, 0, 0, .5) -1px -1px 0;
}

button#facebook {
    border-color: #4A6E88;
    background-color: #4A6EA9;
    background-image: url(http://icons.iconarchive.com/icons/danleech/simple/512/facebook-icon.png);
}
/*
button#twitter {
    border-color: #007aa6;
    background-color: #008cbf;
    background-image: url(https://twitter.com/images/resources/twitter-bird-white-on-blue.png);
}*/

button#googleplus {
    border-color: #BB4132;
    background-color: #D44132;
    text-shadow: #333 -1px -1px 0;
    background-image:url(http://www.siam.org/publicawareness/images/Google-plus-icon.png);
}

div.social-wrap button:active {
    background-color: #222;
}

</style>
{/literal}