{{#unless optionList}}
    {{translate 'No Data'}}
{{/unless}}
<ul class="list-group array-add-list-group">
{{#each optionList}}
    <li class="list-group-item clearfix">
        {{#if ../translatedOptions}}{{prop ../../translatedOptions value}}{{else}}{{value}}{{/if}}
        <div class="btn-group pull-right">
            {{#if relation}}
            <div class="import-by-field">
                <span class="label-text">{{translate 'importBy' category='labels' scope='ImportFeed'}}</span>
                <div class="field">
                    <select class="form-control">
                        {{options options value scope=scope field=name translatedOptions=translatedOptions}}
                    </select>
                </div>
            </div>
            {{/if}}
            <button class="btn btn-default pull-right" data-value="{{value}}" data-action="add">{{translate 'Add'}}</button>
        </div>
    </li>
{{/each}}
</ul>

<style>
    .import-by-field {
        display: inline-block;
    }
    .import-by-field .field {
        display: inline-block;
        margin: 0 10px;
        min-width: 116px;
    }
</style>