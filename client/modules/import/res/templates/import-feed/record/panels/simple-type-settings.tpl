<div class="row">
    <div class="cell form-group col-sm-6 col-xs-12" data-name="entity">
        <label class="control-label" data-name="entity"><span class="label-text">{{translate 'entity' scope=scope category='labels'}}</span></label>
        <div class="field" data-name="entity">
            {{{entity}}}
        </div>
    </div>
    <div class="cell form-group col-sm-6 col-xs-12" data-name="idField">
        <label class="control-label" data-name="idField"><span class="label-text">{{translate 'id' scope='Global' category='fields'}}</span></label>
        <div class="field" data-name="idField">
            {{{idField}}}
        </div>
    </div>
</div>
<div class="row">
    <div class="cell form-group col-sm-6 col-xs-12" data-name="delimiter">
        <label class="control-label" data-name="delimiter"><span class="label-text">{{translate 'delimiter' scope=scope category='labels'}}</span></label>
        <div class="field" data-name="delimiter">
            {{{delimiter}}}
        </div>
    </div>
</div>
<div class="panel panel-default panel-configurator">
    <div class="panel-heading">
        <div class="pull-right btn-group">
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-name="configuratorActions" data-toggle="dropdown">
                <span class="fas fa-plus"></span>
            </button>
            <ul class="dropdown-menu">
                {{#each configuratorActions}}
                <li><a href="javascript:" class="action" data-action="{{action}}">{{label}}</a></li>
                {{/each}}
            </ul>
        </div>
        <h4 class="panel-title">{{translate 'configurator' scope=scope category='labels'}}</h4>
    </div>
    <div class="mapping-container">
        <div class="list-container">{{{configurator}}}</div>
    </div>
</div>
