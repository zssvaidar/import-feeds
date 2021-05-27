<div class="field" data-name="column">{{{column}}}</div>
{{#if containerViews.singleColumn}}
<div class="additional-column">
    <label class="control-label"><span class="label-text">{{translate 'singleColumn' category='labels' scope='ImportFeed'}}</span> <a href="javascript:" class="text-muted single-column-info"><span class="fas fa-info-circle"></span></a></label>
    <div class="field" data-name="singleColumn">{{{singleColumn}}}</div>
</div>
{{/if}}
{{#if containerViews.columnCurrency}}
<div class="additional-column">
    <label class="control-label"><span class="label-text">{{translate 'currency' category='labels' scope='ImportFeed'}}</span></label>
    <div class="field" data-name="columnCurrency">{{{columnCurrency}}}</div>
</div>
{{/if}}
<div class="additional-column">
{{#if containerViews.columnUnit}}
    <label class="control-label"><span class="label-text">{{translate 'unit' category='labels' scope='ImportFeed'}}</span></label>
    <div class="field" data-name="columnUnit">{{{columnUnit}}}</div>
{{/if}}
</div>

<style type="text/css">
.additional-column{
width: 100%;
float: left;
margin-top: 5px;
}
</style>