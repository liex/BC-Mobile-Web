<form method="POST" id="loginForm" action="login">
<input type="hidden" name="url" value="{$url|escape}" />
<input type="hidden" name="startOver" value="1" />
{if $authority}
{if !$message}
<p class="nonfocal">To access {$strings.SITE_NAME}, please sign in.</p>
{/if}
<input type="hidden" name="authority" value="{$authority}" />
<div class="focal">
    {if $message}<p>{$message}</p>{/if}
    <p><label for="loginUser">{$moduleStrings.LOGIN_LABEL|default:'login'}:</label> <input type="text" name="loginUser" id="loginUser" class="loginFormInput" /></p>
    <p><label for="loginPassword">{$moduleStrings.PASSWORD_LABEL|default:'password'}:</label> <input type="password" name="loginPassword" id="loginPassword" class="loginFormInput" /> </p>
    {if $allowRemainLoggedIn}<p class="checkitem"><input type="checkbox" id="remember" name="remainLoggedIn"{if $remainLoggedIn} checked{/if} /> <label for="remember">Remember me</label></p>{/if}

    <div class="formbuttons">
    {block name="submit"}<a class="formbutton" onclick="loginFormSubmit();"><div>Sign in</div></a>{/block}
    </div>   
</div>
{if $FORGET_PASSWORD_URL}<p class="nonfocal smallprint"><a href="{$FORGET_PASSWORD_URL}">{$moduleStrings.FORGET_PASSWORD_TEXT|default:'Forgot your password?'|escape}</a></p>{/if}
{else}
{if $authenticationAuthorities.direct}
{if $authenticationAuthorities.indirect}
    <h3 class="nonfocal">Sign in directly</h3>
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$authenticationAuthorities.direct navlistClass="loginbuttons" navlistID="directList"}


{/if}
{if $authenticationAuthorities.indirect}
{if $authenticationAuthorities.direct}
<p class="nonfocal">or</p>
<h3 class="nonfocal">Use your account with an external service</h3>
{/if}
<p class="smallprint nonfocal">Once you've signed in, you'll be returned to {$strings.SITE_NAME}.</p>

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$authenticationAuthorities.indirect  navlistClass="loginbuttons" navlistID="indirectList"}
{/if}

{if $allowRemainLoggedIn}
    <p class="nonfocal" id="rememberme"><input type="checkbox" id="remember" name="remainLoggedIn" onclick="toggleRememberMe()" /> <label for="remember">Remember this choice</label></p>
{/if}
{/if}
</form>
