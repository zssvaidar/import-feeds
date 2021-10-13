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
    <div class="mapping-container panel-body panel-collapse collapse in">
        <div class="list-container">{{{configurator}}}</div>
    </div>
</div>
