{{#each columns}}
    {{#if label}}<span class="text-muted">{{label}}</span>{{/if}}
    <div class="field" data-name="{{name}}">{{{var name ../this}}}</div>
{{/each}}