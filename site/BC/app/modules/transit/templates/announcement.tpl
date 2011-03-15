{include file="findInclude:common/templates/templates/header.tpl"}

<div class="focal">
  <h2>{$title}</h2>
  <p class="smallprint">{$date|date_format:"%a %b %e, %Y"}</p>
  {$content}
</div>

{include file="findInclude:common/templates/templates/footer.tpl"}
