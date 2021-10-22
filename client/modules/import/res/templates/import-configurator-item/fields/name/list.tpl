{{#if isNotEmpty}}
    <div>
        {{value}}{{#if isRequired}} *{{/if}}
    </div>
    {{#if extraInfo}}
        {{{extraInfo}}}
    {{/if}}
{{else}}
    {{#if valueIsSet}}
        {{{translate 'None'}}}
    {{else}}
        ...
    {{/if}}
{{/if}}