{* TODO: 1.7.0.0: RENAME THIS FILE AT THE NEXT RETROCOMPATIBILITY BREAK *}

<div class="header-toolbar">
  <div class="container-fluid">

    {block name=pageBreadcrumb}
      <nav aria-label="Breadcrumb">
        <ol class="breadcrumb">
          {if $breadcrumbs2.container.name != ''}
            <li class="breadcrumb-item">{$breadcrumbs2.container.name|escape}</li>
          {/if}

          {if $breadcrumbs2.tab.name != '' && $breadcrumbs2.container.name != $breadcrumbs2.tab.name && $breadcrumbs2.tab.href != ''}
            <li class="breadcrumb-item active">
              <a href="{$breadcrumbs2.tab.href|escape}" aria-current="page">{$breadcrumbs2.tab.name|escape}</a>
            </li>
          {/if}
        </ol>
      </nav>
    {/block}

    <div class="title-row">
      {block name=pageTitle}
          <h1 class="title">
            {if is_array($title)}{$title|end|escape}{else}{$title|escape}{/if}
          </h1>
      {/block}

      {block name=toolbarBox}
        <div class="toolbar-icons">
          <div class="wrapper">
            {hook h='displayDashboardToolbarTopMenu'}
            {foreach from=$toolbar_btn item=btn key=k}
              {if $k != 'back' && $k != 'modules-list'}
                {* TODO: REFACTOR ALL THIS THINGS *}
                <a
                  class="btn btn-primary {if isset($btn.target) && $btn.target} _blank{/if} pointer"{if isset($btn.href)}
                  id="page-header-desc-{$table}-{if isset($btn.imgclass)}{$btn.imgclass|escape}{else}{$k}{/if}"
                  href="{$btn.href|escape}"{/if}
                  title="{if isset($btn.help)}{$btn.help}{else}{$btn.desc|escape}{/if}"{if isset($btn.js) && $btn.js}
                  onclick="{$btn.js}"{/if}{if isset($btn.modal_target) && $btn.modal_target}
                  data-target="{$btn.modal_target}"
                  data-toggle="modal"{/if}{if isset($btn.help)}
                  data-toggle="pstooltip"
                  data-placement="bottom"{/if}
                >
                  <i class="material-icons">{$btn.icon}</i>
                  {$btn.desc|escape}
                </a>
              {/if}
            {/foreach}
          </div>
        </div>
      {/block}
    </div>
  </div>

  {if isset($headerTabContent) and $headerTabContent}
    <div class="page-head-tabs" id="head_tabs">
    {foreach $headerTabContent as $tabContent}
        {{$tabContent}}
    {/foreach}
    </div>
  {/if}

  {if $current_tab_level == 3}
    <div class="page-head-tabs" id="head_tabs">
      <ul class="nav nav-pills">
      {foreach $tabs as $level_1}
        {foreach $level_1.sub_tabs as $level_2}
          {foreach $level_2.sub_tabs as $level_3}
            {if $level_3.current}
              {foreach $level_3.sub_tabs as $level_4}
                {if $level_4.active}
                  <li class="nav-item">
                    <a href="{$level_4.href}" id="subtab-{$level_4.class_name}" class="nav-link tab {if $level_4.current}active current{/if}" data-submenu="{$level_4.id_tab}">{$level_4.name}</a>
                  </li>
                {/if}
              {/foreach}
            {/if}
          {/foreach}
        {/foreach}
      {/foreach}
      </ul>
    </div>
  {/if}
  {hook h='displayDashboardTop'}
</div>
