<div class="field" data-name="column">{{{column}}}</div>
{{#if containerViews.singleColumn}}
<span class="text-muted">{{translate 'singleColumn' category='labels' scope='ImportFeed'}} <a href="javascript:" class="text-muted single-column-info"><span class="fas fa-info-circle"></span></a></span>
<div class="field" data-name="singleColumn">{{{singleColumn}}}</div>
{{/if}}
{{#if containerViews.columnCurrency}}
<span class="text-muted">{{translate 'currency' category='labels' scope='ImportFeed'}}</span>
<div class="field" data-name="columnCurrency">{{{columnCurrency}}}</div>
{{/if}}
{{#if containerViews.columnUnit}}
<span class="text-muted">{{translate 'unit' category='labels' scope='ImportFeed'}}</span>
<div class="field" data-name="columnUnit">{{{columnUnit}}}</div>
{{/if}}