{extends file="findExtends:modules/{$moduleID}/stop.tpl"}

{block name="refreshButton"}
{/block}

{block name="headerServiceLogo"}
  {$serviceLogoExt = '.gif'}
  {$smarty.block.parent}
{/block}

{block name="stopInfo"}
  {$smarty.block.parent}
  &nbsp;(<a href="{$refreshURL}">refresh</a>)
{/block}

{block name="autoReload"}
{/block}
