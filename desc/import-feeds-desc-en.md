The "Import Feeds" module enables you to import all the data for any entity in the AtroCore system and its direct relations, eg it is possible to import the product data together with the corresponding categories. You can create, configure and use many inport feeds.

With the help of the "Import Feeds" module, data import may be performed in two ways:

- **manually** – by using some configured import feed directly
- **automatically** – by scheduled job.

You will be able to create a scheduled job for your import tasks only if the appropriate import feed type is available in your system. Such import feed types enable the system to download the data via URL, execute REST API call, make a query directly to the database and so on (premium modules are needed for this feature). With this free module you can manually import the data via CSV files. You can also code your own import feed types.

## The following modules extend the functionality of the import feeds

- Import Feeds: Rollback – allows to rollback the last import with the full data recovery
- Import Feeds: Databases – allows to import data from MSSQL, MySQL, PostgreSQL, Oracle, HANA databases
- Import Feeds: JSON and XML – allows to import data from JSON and XML files
- Import Feeds: URL – allows to import data via URL from CSV, JSON and XML files
- Import Feeds: REST API – allows to import data via REST API
- Connector – orchestrates multiple import and export feeds to automate the complex data exchange

## Administrator Functions

After the module installation two new entities will be created in your system - `Import Feeds` and `Import Results`. Via `Administration > System > User Interface`you can add these items to the navigation of your system, if it did not happen automatically.
![Import feeds adding](_assets/import-feeds-admin-layout-manager.png)

### Access Rights

To enable import feed creation, editing, usage and deletion by other users, configure the corresponding access rights to the `Import feeds` and `Import results` entities for the desired user/team/portal user role on the `Administration > Roles > *Role name*` page: 

![Import role cfg](_assets/import-feeds-admin-roles.png)

Please, note that enabling at least `Import feeds` read permission for the user is a required minimum for him to be able to run import feed.

## User Functions 

After the "Import Feeds" module is installed and configured by the administrator, user can work with import feeds in accordance with his role rights that are predefined by the administrator.

## Import Feed Creation

To create a new import feed, click `Import Feeds` in the navigation menu and then click the `Create Import Feed` button. 

> If there is no `Import Feeds` option in the navigation menu, please, contact your administrator.

The standard form for the import feed creating appears:
![Import feed creation](_assets/import-feeds-create.png)

Here fill in the required fields and select the import feed type from the corresponding drop-down list. This module adds only **CSV File** type.
Сlick the `Save` button. The new record will be added to the import feeds list. You can configure it right away on the detail view page that opens or return to it later.

### Overview panel

Overview panel enables you to define the main feed parameters (name, action, activity etc):
![Import feed cfg](_assets/import-feeds-create-overview.png)

The following settings are available here:

- **Active** – activity of the import feed, import is imposible unless this checkbox is activated
- **Name** – import feed name
- **Description** – description of the import feed usage, can be used as a reminder for the future or as a hint for other users of the given import feed
- **Type** – the import feed type, cannot be modified later
- **Action** – define the action to be performed in the system during the data import:
    - *Create Only* – only new data records will be created, existing data records will not be updated
    - *Update Only* – existing records will be updated, new data records will not be created
    - *Create & Update* – new data records will be created and the existing records will be updated.

### File Properties

The import file parameters are configured on the `FILE PROPERTIES` panel:

![Import feed cfg file](_assets/import-feeds-create-file-properties.png)

- **File** – here you can upload the file which is to be imported or its shortened version, which will be used for the configuration. The file should be UTF-8 encoded. 
- **Header row** – activate the checkbox if the column names are included in the import file or leave it empty if the file to be imported has no header row with column names.
- **Thousand separator** –  define the symbol, which is used as thousand separator. This parameter is optional. The numerical values without thousand separator will be also imported (eg both values 1234,34 and 1.234,34 will be imported, if "." is defined as a thousand separator).
- **Decimal mark** – select the used decimal mark, usually `.` or `,` should be defined here.
- **Field delimiter** – select the preferred field delimiter to be used in the CSV import file, possible values are `,`, `;`,`\t`, `|`.
- **Text qualifier** – select the preferred separator of the values within a cell: single or double quotes can be selected.

### Settings

The next panel is the settings panel:

![Simple type settings](_assets/import-feeds-create-settings.png)

- **Entity** – select the desired entity for the imported data from the drop-down list of all entities available in the system.
- **Unused Columns** – this field is initially empty. After save you will see here the list of available unmapped columns.
- **Data record delimiter** – is the delimiter to split multiple values (eg for multienum or array fields and attributes) or multiple related records.
- **Mark for a non linked attribute** – this mark is only available for the product entity. This symbol marks attribute which should not be linked to the respective product.
- **Empty Value** – This symbol will be interpreted as "empty" value aditionally to the empty cell, eg "" and "none" will be interpreted as "", if you define "none" as an empty value.
- **Null value** – this value will be interpreted as "NULL" value.

If you import product data, some products may have certain attributes, other not. If the value for some attribute is empty it is not clear, whether this attribute have an empty value, or this product does not have this attribute at all. That is why the **mark for a non linked attribute** should be used, to mark clearly, which attribute should not be linked to a certain product.

If the `Unused Columns` field is empty after saving your feed you should check your field delimiter for correctness. If some column names has single or double quotes you may have set the wrong text qualifier.

> Please, note that the defined `field delimiter`, `data record delimiter`, `empty value`, `null value`, `thousand separator`, `decimal mark`, `text qualifier` and `mark for a non linked attribute` symbols must be different.

## Configurator
Configurator can be used after the import feed is created. Initially this panel is empty. This panel displays mapping rules for your data import.

![Configurator panel](_assets/import-feeds-configurator.png)

To create a new entry click on the `+` icon in the upper right corner. A popup window appears.

![Configurator panel](_assets/import-feeds-configurator-new.png)

- **Type** – choose the type of your mapping rule by selection "Field" of "Attribute" in the field `Type`. The option "Attribute" is available only for product entity.

![Configurator panel](_assets/import-feeds-configurator-new-type.png)

- **Field** – choose the field for your selected entity field for data to be imported from the selected column(s).
- **Identifier** – set this checkbox, if the value in the selected column should be interpreted as an identifier. You can select multiple columns as identifier.
- **Column(s)** – depending on the type of the selected entity field you can select one, two or multiple columns here.
- **Default Value** – you can specify the default value to be set, if the cell value is "", "empty" or "null".

Click on "Save" button to save the mapping rule.

> Please note, a certain column can be used multiple times in different rules.

To modify the mapping rule displayed on the `Configurator` panel, use the `Edit` option from the single record actions menu. Here you can also delete the selected rule.

![Configurator panel](_assets/import-feeds-configurator-menu.png)

### Identifier
For each field you can define whether it is the identifier or not. All identifiers are used together in search for a data record in the database. If some data record is found, it will be updated with the values which come from the import file. If more then one data record is found you will get an error and import will not be executed.

![Configurator identifier](_assets/import-feeds-configurator-identifiers.png)

### Default Value
For any mapping rule the column(s) or default value or both should be filled. Thus, it is possible to set the default value without choosing the column(s). In this case this value will be applied to all data records. For example you can set a value for a "Catalog". If you would import product data, all the products will be automatically assigned to the selected catalog, even if you import file have no column for a "Catalog". If "default value" is left emply or no value is set, no default value will be applied as default value.

### Attributes
Only product entity has attributes.
![Configurator attributes](_assets/import-feeds-configurator-new-attribute.png)

## Running Import Feed

To start importing the data via the active import feed, select the `Run import` option from the actions menu on its detail view page or from the single record actions menu on the "Import Feeds" list view page:

![Run import option](_assets/run-import-option.jpg)

In the pop-up that appears, attach the CSV file with data to be imported and click the `Run import` button to start the process:

![Run import pop-up](_assets/run-import-popup.jpg)

Please, note that the data file must match the example file, defined for the given import feed. Otherwise, the error message will appear:

![Wrong file error](_assets/wrong-file-error.jpg) 

When import is started, its details and current status are displayed in the Queue Manager pop-up that appears automatically:

![Queue manager](_assets/queue-manager.jpg)

Please, note that if the number of records in the data file exceeds the [limit](#import-feed-configuration) value defined on the `OVERVIEW` panel of the given import feed, the import job will be split into the corresponding parts in accordance with the limit value.

### Import Results

Information about completed import jobs is displayed on the `IMPORT RESULTS` panel, which is empty on the import feed [creation](#import-feed-creation) step, but gets filled in after the data import is performed via the given import feed.

Results of the data import operations can be viewed in two ways:
- on the **`IMPORT RESULTS` panel** of the import feed – the details on the import operations performed via the currently open import feed:

![Import results panel](_assets/import-results-panel.jpg)

- on the **import results list view page** – the details on all import operations performed in the system via import feeds:

![Import results list](_assets/import-results-list.jpg)

The import results details contain the following information:

- **Name** – the import result record name, which is generated automatically based on the date and time of the import operation start. Click the import result record name to open its detail view page.
- **Import feed** – the name of the import feed used for the import operation. Click the import feed record name to open its detail view page.
- **Imported file** – the name of the data file (CSV) used for the import operation. Click the imported file name to download it. 
- **Status** – the current status of the import operation.
- **Restored** – the indication of whether the given import result record has been restored (the checkbox is selected) or not.
- **Start** – the date and time of the import operation start.
- **End** – the date and time of the import operation end.
- **Created** – the number of records created as a result of the performed import operation. Click this value for the desired import result to open the list view page of the corresponding entity records filtered by the given import result, i.e. with the `Created by import` filter applied.
- **Updated** – the number of records updated as a result of the performed import operation. Click this value for the desired import result to open the list view page of the corresponding entity records filtered by the given import result, i.e. with the `Updated by import` filter applied.
- **Errors** – the number of errors, if any, that occurred during the import operation. Click this value for the desired import result to open the list view page of the import result log records.
- **[Error file](#error-file)** – the name of the CSV file that contains only rows with errors. The error file name is generated automatically based on the imported file name. Click the error file name to download it.

The following status options are available:
- **Running** – for the currently running import job.
- **Pending** – for the import job, which is next in line for execution.
- **Success** – for the successfully finished import job (no matter if it contains any errors in it).
- **Failed** – for the import job that could not be performed due to some technical issues.

#### Details

To view the import result record details, click its name on the `IMPORT RESULTS` panel within the desired import feed detail view page or in the import results list; the corresponding import result record detail view page opens:

![Import result details](_assets/import-result-details.jpg)

The error messages, if any, are displayed on the `ERRORS LOG` panel on this page: 

![Errors log](_assets/errors-log.jpg)

To see a full list of error records, use the `Show Full List` action menu command – the import result logs list appears with records filtered by the given import result:

![Import result logs](_assets/import-result-logs.jpg)

Alternatively, view the import result record details in the pop-up that appears when you use the `View` option from the single record actions menu for the desired record on the import results list view page or on the `IMPORT RESULTS` panel of the currently open import feed:

![Import result pop-up](_assets/import-result-popup.jpg)

Please, note that you can download the imported file from any [interface page](https://atropim.com/help/views-and-panels), where its name is clickable.

#### Error File

All data of the required entity fields are added to the import file. In case any required field is empty (i.e. not filled in) in the entity record added to the import feed, or the entered data are not validated (e.g. the data entered do not match the field type (e.g. text instead of numerical values in the `Boolean`, `Currency`, `Float`, `Unit` field types), the link entered does not exist in the system, etc.), this record is not imported. Instead, it is added to the error file – a separately generated CSV file containing only rows of records with errors.

You can download the error file from any [interface page](https://atropim.com/help/views-and-panels) where its name is clickable (e.g. import results list view, import result record detail/quick detail view, etc.), correct the data in the defined rows, and run the import operation again using the corrected error file as the data file.

#### Data Restoration

The "Import Feeds" module supports data restoration for separate import results record to the pre-import state. To do this, select the `Restore` option from the single record actions menu for the desired import result record on the import feed detail view page:

![Restore option](_assets/restore-option.jpg)

Click the `Restore` button in the confirmation message that appears to start the process or `Cancel` to abort the process. The Queue Manager pop-up will automatically appear:

![Restore process](_assets/queue-manager-restore.jpg)

As a consequence, the 'reverted' import result record will disappear from the `IMPORT RESULTS` panel, and on the import results list view page the `Restored` checkbox will become selected for the 'initial' import result record:

![Restored checkbox](_assets/restored-checkbox.jpg)

Please, note that data restoration is performed only for the latest import result. 

## Import Feed Operations and Actions

Import feed records can be duplicated and removed whenever needed.

To *duplicate* the existing import feed record, use the corresponding option from the actions menu on the desired import feed record detail view page:

![Duplicate feed](_assets/duplicate-feed.jpg)

You will be redirected to the import feed creation page and get all the values of the last chosen import feed record copied in the empty fields of the new feed record to be created. 

In order to *remove* the import feed record, use the corresponding option from the actions menu on the desired import feed record detail view page or from the single record actions menu on the import feeds list view page:

![Remove feed](_assets/remove-import-feed.jpg)

To complete the operation, click the `Remove` button in the confirmation message that appears.

The "Import Feeds" module also supports common AtroCore *mass actions* that can be applied to several selected import feed records, i.e. records with set checkboxes. These actions can be found in the corresponding menu on the import feeds list view page:

![Mass actions](_assets/mass-actions.jpg)

- **Remove** – to remove the selected import feed records (multiple deletion).
- **Merge** – to merge the selected import feed records.
- **Mass update** – to update several selected import feed records at once. To have a longer list of fields available for mass updating, please, contact your administrator.
- **Export** – to export the desired data fields of the selected import feed records in the XLSX or CSV format.
- **Add relation** – to relate the selected import feed records with other import result record(s).
- **Remove relation** – to remove the relations that have been added to the selected import feed records.

## Import Cron Job Configuration

In order to run import automatically according to the schedule, you can configure **import cron jobs**. To create a new job record, click the `Import Cron Jobs` in the navigation menu to get to the import cron jobs list view, and then click the `Create Import Cron Job` button. 

> If there is no `Import Cron Jobs` option in the navigation menu, please, contact your administrator.

The common creation window will open:

![Cron job creation](_assets/cron-job-create.jpg)

Here define the following parameters for the import cron job being created:

- **Active** – select the checkbox to activate the job.
- **Name** – enter the import cron job name. 
- **Import feed** – select the desired import feed, based on which the data will be imported.
- **Link** – enter a direct link to the example file (CSV) from which the data will be imported.
- **Scheduling** – enter a desired cron job schedule, i.e. frequency of job runs, using common syntax rules. 

When an import cron job is started, its details are shown in the Queue Manager pop-up.

The results of the run import cron job are displayed on the	`LOGS` panel of the corresponding job record:

![Cron job logs](_assets/cron-job-logs.jpg)

To view the import cron jobs queue and status details, please, go to `Administration > Schedule Jobs` and click the `Jobs` button:

![Cron jobs queue](_assets/cron-jobs-queue.jpg)

## Special Import Cases

### Product Variants

If there is the ["Product Variants" module](https://atropim.com/store/product-variants) installed in your [AtroPIM system](https://atropim.com/help/what-is-atropim), product variants can be imported via import feeds. For this, add the `Configurable Product` entity field in the same way as described [above](#adding-entity-fields):

![Create configurable product](_assets/create-configurable-product.jpg)

Make sure the configurable product and its variant(s) belong to the same [product family](https://atropim.com/help/product-families) and [catalog](https://atropim.com/help/catalogs). Otherwise, these records will not be imported, but added to the [error file](#error-file).

The setup of other fields and attributes for the configurable product record is the same as for the [simple product](#import-feed-configuration).

All attributes of the `Enum` type as well as [multilingual attributes](https://atropim.com/store/multi-languages#multilingual-attributes), added on the `CONFIGURATOR` panel within the desired import feed record detail view page, become *variant-forming*, whereas attributes of other types are imported as common attributes of the product variants.

Prior to running the import process for product variants, data validation is performed on the basis of the `Configurable Product` entity field, and depending on the validation results, there may be several scenarios:

- the `Configurable Product` field is **empty** for some records: these product records are imported as records of the `Simple Product` type, i.e. you can import at the same time not only product variant records, but also product records of other types;

- a configurable product, which is added to the import file, already **exists** in the system: as a result of the import operation, product variant records are created on the basis of the given configurable product;

- a configurable product defined in the import file **is missing** in the system: [running](#running-import-feed) the import operation results in the creation of this configurable product record on the basis of the corresponding import file row.

In all other cases, besides the described scenarios, the product variants import via import feeds is performed in the same way as the [simple product import](#running-import-feed). Also note that once the import process is completed, all fields and panels are automatically unlocked for the imported product variants.

## Customization
The module can be adapted to your needs, additional functions can be programmed, existing functions can be changed. Please contact us regarding this. Our GTC (General Terms and Conditions) apply.

## Demo
https://demo.atropim.com/

## License
This module is published under the GNU GPLv3 [license](https://www.gnu.org/licenses/gpl-3.0.en.html).
