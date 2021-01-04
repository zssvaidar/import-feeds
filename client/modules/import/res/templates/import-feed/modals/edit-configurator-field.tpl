<div class="edit-container record configurator-modal">
    <div class="col-sm-12">
        <div class="row">
            <div class="cell col-sm-6">
            {{#if isAttribute}}
                <label class="control-label">
                    <span class="label-text">{{translate 'Attribute' scope='Global' category='labels'}}</span>
                </label>
                <div class="field" data-name="attribute">{{{attribute}}}</div>
            {{else}}
                <label class="control-label">
                    <span class="label-text">{{translate 'Field' scope='Import' category='labels'}}</span>
                </label>
                <div class="field" data-name="name">{{{name}}}</div>
            {{/if}}
            </div>
            <div class="cell col-sm-6">
                <label class="control-label">
                    <span class="label-text">{{translate 'column' scope='ImportFeed' category='fields'}}</span>
                </label>
                <div class="field" data-name="column">{{{column}}}</div>
            </div>
        </div>
        <div class="row">
            <div class="cell col-sm-6">
                <label class="control-label">
                    <span class="label-text">{{translate 'default' scope='ImportFeed' category='fields'}}</span>
                </label>
                <div class="field" data-name="default">{{{default}}}</div>
            </div>
            <div class="cell col-sm-6">
                <label class="control-label">
                    <span class="label-text">{{translate 'importBy' scope='ImportFeed' category='labels'}}</span>
                </label>
                <div class="field" data-name="field">{{{field}}}</div>
            </div>
        </div>
        <div class="row">
            <div class="cell col-sm-6">
                <label class="control-label">
                    <span class="label-text">{{translate 'scope' scope='ProductAttributeValue' category='fields'}}</span>
                </label>
                <div class="field" data-name="scope">{{{scope}}}</div>
            </div>
            <div class="cell col-sm-6">
                <label class="control-label">
                    <span class="label-text">{{translate 'channel' scope='ProductAttributeValue' category='fields'}}</span>
                </label>
                <div class="field" data-name="channel">{{{channel}}}</div>
            </div>
        </div>
        <div class="row">
            <div class="cell col-sm-6">
                <label class="control-label">
                    <span class="label-text">{{translate 'locale' scope='ImportFeed' category='fields'}}</span>
                </label>
                <div class="field" data-name="locales">{{{locale}}}</div>
            </div>
        </div>
    </div>
</div>