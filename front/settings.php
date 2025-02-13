<?php

require 'php/templates/header.php';

//------------------------------------------------------------------------------
//  Action selector
//------------------------------------------------------------------------------
// Set maximum execution time to 15 seconds
ini_set ('max_execution_time','30');

// check permissions
$dbPath = "../db/app.db";
$confPath = "../config/app.conf";

checkPermissions([$dbPath, $confPath]);

// get settings from the API json file

// path to your JSON file
$file = '../front/api/table_settings.json'; 
// put the content of the file in a variable
$data = file_get_contents($file); 
// JSON decode
$settingsJson = json_decode($data); 

// get settings from the DB

global $db;

$result = $db->query("SELECT * FROM Settings");  


$settings = array();
while ($row = $result -> fetchArray (SQLITE3_ASSOC)) {   
  // Push row data      
  $settings[] = array(  'setKey'          => $row['setKey'],
                        'setName'         => $row['setName'],
                        'setDescription'  => $row['setDescription'],
                        'setType'         => $row['setType'],
                        'setOptions'      => $row['setOptions'],
                        'setValue'        => $row['setValue'],
                        'setGroup'        => $row['setGroup'],
                        'setEvents'       => $row['setEvents']
                      ); 
}

$settingsJSON_DB = json_encode($settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

?>
<!-- Page ------------------------------------------------------------------ -->



<script src="lib/crypto/crypto-js.min.js"></script>


<div id="settingsPage" class="content-wrapper">

<!-- Content header--------------------------------------------------------- -->
    <section class="content-header">

      <div class="col-sm-5">
        <h1 id="pageTitle col-sm-3">
            <i class="fa fa-cog"></i>
            <?= lang('Navigation_Settings');?> 
            <a style="cursor:pointer">
              <span>
                <i id='toggleSettings' onclick="toggleAllSettings()" class="settings-expand-icon fa fa-angle-double-down"></i>
              </span> 
            </a>
        </h1>
      </div>

      <div class="col-sm-7 settingsImportedTimestamp" title="<?= lang("settings_imported");?> ">
        <div class="settingsImported ">
          <?= lang("settings_imported_label");?>:          

          <span id="lastImportedTime"></span>
        </div>    
      </div>

    </section>
    <section class="content-header">

    <div  class ="bg-white color-palette box box-solid box-primary  col-sm-12  panel panel-default panel-title" > 
      <!-- Settings imported time -->

      
      <a data-toggle="collapse" href="#settingsOverview">
        <div class ="settings-group col-sm-12 panel-heading panel-title">
            <i class="<?= lang("settings_enabled_icon");?>"></i>  <?= lang("settings_enabled");?>  
        </div>     
      </a>  
        <div id="settingsOverview" class="panel-collapse collapse in"> 
          <div class="panel-body"></div>
        <div class =" col-sm-12 " id=""></div>
      </div>
    </section>

    <div class="content settingswrap " id="accordion_gen">

      <div class ="bg-grey-dark color-palette panel panel-default col-sm-12  box-default box-info" id="core_content_header" >        
          <div class ="settings-group col-sm-12">
            <i class="<?= lang("settings_core_icon");?>"></i>  <?= lang("settings_core_label");?>       
          </div>        
          <div class =" col-sm-12" id="core_content"></div>
      </div>    

      <div class ="bg-grey-dark color-palette panel panel-default col-sm-12   box-default box-info" id="system_content_header" >        
          <div class ="settings-group col-sm-12">
            <i class="<?= lang("settings_system_icon");?>"></i>  <?= lang("settings_system_label");?>       
          </div>        
          <div class =" col-sm-12" id="system_content"></div>
      </div> 

      <div class ="bg-grey-dark color-palette  panel panel-default col-sm-12   box-default box-info" id="device_scanners_content_header" >        
          <div class ="settings-group col-sm-12">
            <i class="<?= lang("settings_device_scanners_icon");?>"></i>  <?= lang("settings_device_scanners_label");?>     
          </div>        
          <div class =" col-sm-12" id="device_scanner_content"> <?= lang("settings_device_scanners_info");?> </div>
      </div> 

      <div class ="bg-grey-dark color-palette  panel panel-default col-sm-12   box-default box-info" id="other_scanners_content_header">        
          <div class ="settings-group col-sm-12">
            <i class="<?= lang("settings_other_scanners_icon");?>"></i>  <?= lang("settings_other_scanners_label");?>       
          </div>        
          <div class =" col-sm-12" id="other_content"></div>
      </div> 

      <div class ="bg-grey-dark color-palette  panel panel-default col-sm-12   box-default box-info" id="publishers_content_header" >        
          <div class ="settings-group col-sm-12">
            <i class="<?= lang("settings_publishers_icon");?>"></i>  <?= lang("settings_publishers_label");?>       
          </div>        
          <div class =" col-sm-12" id="publisher_content"><?= lang("settings_publishers_info");?></div>
      </div> 
     
   </div>
   
    <!-- /.content -->

    <section class=" padding-bottom  col-sm-12">
      <!-- needed so the filter & save button don't hide the settings -->
    </section>


      <section class=" settings-sticky-bottom-section  col-sm-10 col-xs-12">
        <div class="col-xs-8 settingsSearchWrap has-success bg-white color-palette ">
            <div class ="col-xs-12">

              <input type="text" id="settingsSearch" class="form-control input-xs col-xs-12" placeholder="Filter Settings...">
              <div class="clear-filter ">
                <i class="fa-solid fa-circle-xmark" onclick="$('#settingsSearch').val('');filterRows();$('#settingsSearch').focus()"></i>
              </div>            
          </div>          
        </div>

        <div class="col-xs-4 saveSettingsWrapper">
            <button type="button" class="   btn btn-primary btn-default pa-btn bg-green" id="save" onclick="saveSettings()"><?= lang('DevDetail_button_Save');?></button>
        </div>
        <div id="result"></div>
    </section>
</div>

  <!-- /.content-wrapper -->

<!-- ----------------------------------------------------------------------- -->
<?php
  require 'php/templates/footer.php';  
?>


<script>

  // -------------------------------------------------------------------  
  // Get plugin and settings data from API endpoints
  function getData(){

    console.log("in getData");

    $.get('api/table_settings.json?nocache=' + Date.now(), function(res) {    
        
        settingsData = res["data"];   
        
        // Wrong number of settings processing
        if(settingsNumberDB != settingsData.length) 
        {
          showModalOk('WARNING', "<?= lang("settings_old")?>");    
          setTimeout(() => {
                  clearCache()
                }, 3000);
        } else
        {
          $.get('api/plugins.json?nocache=' + Date.now(), function(res) {  

            pluginsData = res["data"];  

            // Sort settingsData alphabetically based on the "setGroup" property
            settingsData.sort((a, b) => {
                if (a["setGroup"] < b["setGroup"]) {
                    return -1;
                }
                if (a["setGroup"] > b["setGroup"]) {
                    return 1;
                }
                return 0;
            });

            exception_occurred = false;

            // check if cache needs to be refreshed
            // the getSetting method returns an empty string even if a setting is not found
            // however, __metadata needs to be always a JSON object
            // let's use that to verify settings were initialized correctly
            settingsData.forEach((set) => {

              setKey = set['setKey']

              try {                
                const isMetadata = setKey.includes('__metadata');
                // if this isn't a metadata entry, get corresponding metadata object from the dummy setting
                const setObj = isMetadata ? {} : JSON.parse(getSetting(`${setKey}__metadata`));
                
              } catch (error) {
                console.error(`Error getting setting for ${setKey}:`, error);
                showModalOk('WARNING', "Outdated cache - refreshing (refresh browser cache if needed)");  

                setTimeout(() => {
                  clearCache()
                }, 3000);

                exception_occurred = true;                
              }
            });
  
            // only proceed if everything was loaded correctly
            if(!exception_occurred)
            {
              initSettingsPage(settingsData, pluginsData);
            }
          })
        }        
    })
  }

  // -------------------------------------------------------------------
  // main initialization function
  function initSettingsPage(settingsData, pluginsData){

    const settingPluginPrefixes = [];
    
    const enabledDeviceScanners = getPluginsByType(pluginsData, "device_scanner", true);    
    const enabledOthers         = getPluginsByType(pluginsData, "other", true);    
    const enabledPublishers     = getPluginsByType(pluginsData, "publisher", true);


    // Loop through the settingsArray and:
    //    - collect unique settingPluginPrefixes
    //    - collect enabled plugins

    settingsData.forEach((set) => {
      // settingPluginPrefixes
      if (!settingPluginPrefixes.includes(set.setGroup)) {
        settingPluginPrefixes.push(set.setGroup); // = Unique plugin prefix
      }      
    });    

    // Init the overview section
    overviewSections      = [
                              'device_scanners',  
                              'other_scanners', 
                              'publishers'                                                          
                            ]
    overviewSectionsHtml  = [
                              pluginCards(enabledDeviceScanners,['RUN', 'RUN_SCHD']),
                              pluginCards(enabledOthers, ['RUN', 'RUN_SCHD']), 
                              pluginCards(enabledPublishers, []),
                            ]

    index = 0
    overviewSections_html = ''

    overviewSections.forEach((section) => {

      overviewSections_html += `<div class="overview-section col-sm-12" id="${section}">
                                  <div class="col-sm-12 " title="${getString("settings_"+section)}">
                                    <a href="#${section}_content_header">
                                      <div class="overview-group col-sm-12 col-xs-12">
                                      
                                        <i title="${section}" class="${getString("settings_"+section+"_icon")}"></i>       
            
                                        ${getString("settings_"+section+"_label")}                                      
                                      </div>     
                                    </a>                               
                                  </div>
                                  <div class="col-sm-12">
                                    ${overviewSectionsHtml[index]}        
                                  </div>
                                </div>`
      index++;
    });

    $('#settingsOverview .panel-body').append(overviewSections_html);

    // Display warning 
    if(schedulesAreSynchronized(enabledDeviceScanners, pluginsData) == false)
    {
      $("#device_scanners").append(
        `
          <small class="label pull-right bg-red pointer" onClick="showModalOk('WARNING', '${getString("Settings_device_Scanners_desync_popup")}')">
            ${getString('Settings_device_Scanners_desync')}
          </small>
        `)
    } 

    for (const prefix of settingPluginPrefixes) {   

      // enabled / disabled icons
      enabledHtml = ''

      if(getSetting(prefix+"_RUN") != "")
      {
        // show all enabled plugins
        getSetting(prefix+"_RUN") != 'disabled' ? onOff = 'dot-circle' : onOff = 'circle';

        enabledHtml = `
                      <div class="enabled-disabled-icon">
                        <i class="fa-regular fa-${onOff}"></i>
                      </div>
                      `
      }      

      // console.log(pluginsData);

      // Start constructing the main settings HTML 
      let pluginHtml = `
        <div class="row table_row docs">
          <div class="table_cell bold">
            <i class="fa-regular fa-book fa-sm"></i>
            ${getString(prefix+'_description')} 
            <a href="https://github.com/jokob-sk/NetAlertX/tree/main/front/plugins/${getPluginCodeName(pluginsData, prefix)}" target="_blank">
            ${getString('Gen_ReadDocs')}
            </a>
          </div>
        </div>
      `;

      // Plugin HEADER
      headerHtml = `<div class="box box-solid box-primary panel panel-default" id="${prefix}_header">
                  <a data-toggle="collapse" data-parent="#accordion_gen" href="#${prefix}">
                    <div class="panel-heading">
                      <h4 class="panel-title">
                        <div class="col-sm-1 col-xs-1">${getString(prefix+"_icon")}   </div>
                        <div class="col-sm-10 col-xs-8">${getString(prefix+"_display_name")} </div>     
                        <div class="col-sm-1 col-xs-1">${enabledHtml} </div>
                      </h4>                      
                    </div>
                  </a>
                  <div id="${prefix}" data-myid="collapsible" class="panel-collapse collapse ${prefix == "General" ? ' in ' : ""}">
                    <div class="panel-body">
                    ${prefix != "General" ? pluginHtml: ""}                    
                    </div>
                  </div>
                </div>
                    `;

      // generate headers/sections      
      $('#'+getPluginType(pluginsData, prefix) + "_content").append(headerHtml);
    }  


    // generate panel content
    for (const prefix of settingPluginPrefixes) {

      // go thru all settings and collect settings per settings prefix 
      settingsData.forEach((set) => {

        const valIn = set['setValue'];
        const setKey = set['setKey'];
        const overriddenByEnv = set['setOverriddenByEnv'] == 1;
        const setType = set['setType'];
        const isMetadata = setKey.includes('__metadata');
        // is this isn't a metadata entry, get corresponding metadata object from the dummy setting
        const setObj = isMetadata ? {} : JSON.parse(getSetting(`${setKey}__metadata`));

        // not initialized properly, reload
        if(isMetadata && valIn == "" )
        {
          console.warn(`Metadata setting value is empty: ${setKey}`);
          clearCache();
        }

        // constructing final HTML for the setting
        setHtml = ""

        if(set["setGroup"] == prefix)
        {
          // hide metadata by default by assigning it a special class          
          isMetadata ? metadataClass = 'metadata' : metadataClass = '';
          isMetadata ? showMetadata = '' : showMetadata = `<i 
                                                      my-to-toggle="row_${setKey}__metadata"
                                                      title="${getString("Settings_Metadata_Toggle")}" 
                                                      class="fa fa-circle-question pointer hideOnMobile" 
                                                      onclick="toggleMetadata(this)">
                                                    </i>` ;

          infoIcon = `<i my-to-show="#row_${setKey} .setting_description"
                        title="${getString("Settings_Show_Description")}" 
                        class="fa fa-circle-info pointer hideOnBigScreen" 
                        onclick="showDescription(this)">
                      </i>` ;

          // NAME & DESCRIPTION columns
          setHtml += `
                    <div class="row table_row ${metadataClass} " id="row_${setKey}">
                      <div class="table_cell setting_name bold col-sm-2">
                        <label>${getString(setKey + '_name', set['setName'])}</label>
                        <div class="small text-overflow-hidden">
                          <code>${setKey}</code>${showMetadata}${infoIcon}
                        </div>
                      </div>
                      <div class="table_cell setting_description col-sm-4">
                        ${getString(setKey + '_description', set['setDescription'])}
                      </div>
                      <div class="table_cell input-group setting_input ${overriddenByEnv ? "setting_overriden_by_env" : ""} input-group col-xs-12 col-sm-6">
                  `;

          // OVERRIDE
          // surface settings override functionality if the setting is a template that can be overridden with user defined values
          // if the setting is a json of the correct structure, handle like a template setting

          let overrideHtml = "";  

          //pre-check if this is a json object that needs value extraction

          let overridable = false;  // indicates if the setting is overridable
          let override = false;     // If the setting is set to be overridden by the user or by default     
          let readonly = "";        // helper variable to make text input readonly
          let disabled = "";        // helper variable to make checkbox input readonly

          // TODO finish
          if ('override_value' in setObj) {
            overridable = true;
            overrideObj = setObj["override_value"]
            override = overrideObj["override"];            

            console.log(setObj)
            console.log(prefix)
          }

          // prepare override checkbox and HTML
          if(overridable)
          { 
            let checked = override ? 'checked' : '';   

            overrideHtml = `<div class="override col-xs-12">
                              <div class="override-check col-xs-1">
                                <input onChange="overrideToggle(this)" my-data-type="${setType}" my-input-toggle-readonly="${setKey}" class="checkbox" id="${setKey}_override" type="checkbox" ${checked} />
                              </div>
                              <div class="override-text col-xs-11" title="${getString("Setting_Override_Description")}">
                                ${getString("Setting_Override")}
                              </div>
                            </div>`;           

          } 
          
          // INPUT

          // Parse the setType JSON string into an object
          let inputHtml = '';

          console.log(setKey);
          console.log(setType);
          
          const setTypeObject = JSON.parse(setType.replace(/'/g, '"'));

          const dataType = setTypeObject.dataType;
          const elements = setTypeObject.elements || [];

          // Iterate through each element in elements array
          elements.forEach(elementObj => {
            const { elementType, elementOptions = [], transformers = [] } = elementObj;
            const { 
              inputType,
              readOnly,
              isMultiSelect,
              isOrdeable,
              cssClasses,
              placeholder,
              suffix,
              sourceIds,
              separator,
              editable,
              valRes,
              getStringKey,
              onClick,
              onChange,
              customParams,
              customId
            } = handleElementOptions(setKey, elementOptions, transformers, valIn);

            // override
            val = valRes;

            // Generate HTML based on dataType and elementType
            switch (elementType) {
              case 'select':
                let multi = isMultiSelect ? "multiple" : "";
                let addCss = isOrdeable ? "select2 select2-hidden-accessible" : "";


                inputHtml += `<select onChange="settingsChanged();${onChange}" 
                                      my-data-type="${dataType}" 
                                      my-editable="${editable}" 
                                      class="form-control ${addCss}" 
                                      name="${setKey}" 
                                      id="${setKey}" 
                                      my-customparams="${customParams}" 
                                      my-customid="${customId}" 
                                      ${multi}>
                                <option value="" id="${setKey + "_temp_"}"></option>
                              </select>`;

                generateOptionsOrSetOptions(setKey, createArray(val), `${setKey}_temp_`, generateOptions, targetField = null, transformers);

                break;

              case 'input':
                let checked = val === 'True' || val === '1' ? 'checked' : ''; 
                inputType === 'checkbox' ?  inputClass = 'checkbox' : inputClass = 'form-control';

                inputHtml += `
                  <input 
                    class="${inputClass} ${cssClasses}" 
                    onChange="settingsChanged();${onChange}" 
                    my-data-type="${dataType}" 
                    my-customparams="${customParams}" 
                    my-customid="${customId}" 
                    id="${setKey}${suffix}" 
                    type="${inputType}" 
                    value="${val}" 
                    ${readOnly}
                    ${checked}
                    placeholder="${placeholder}" 
                  />`;
                break;

              case 'button':
                
                inputHtml += `
                  <button 
                    class="btn btn-primary ${cssClasses}" 
                    my-customparams="${customParams}" 
                    my-customid="${customId}" 
                    my-input-from="${sourceIds}" 
                    my-input-to="${setKey}" 
                    onclick="${onClick}">
                    ${getString(getStringKey)}
                  </button>`;
                break;
              case 'textarea':                
                inputHtml += `
                  <textarea 
                    class="form-control input" 
                    my-customparams="${customParams}" 
                    my-customid="${customId}" 
                    my-data-type="${dataType}" 
                    id="${setKey}" 
                    ${readOnly}>
                      ${val}
                    </textarea>`;
                break;
              case 'span':                
                inputHtml += `
                  <span 
                    class="${cssClasses}" 
                    my-data-type="${dataType}" 
                    my-customparams="${customParams}" 
                    my-customid="${customId}" 
                    >
                    ${getString(getStringKey)}
                    </span>`;
                break;

              default:
                console.warn(`🟥Unknown element type: ${elementType}`);
            }
          });

          // EVENTS
          // process events (e.g. run a scan, or test a notification) if associated with the setting
          let eventsHtml = "";          

          const eventsList = createArray(set['setEvents']);      

          if (eventsList.length > 0) {
            // console.log(eventsList)
            eventsList.forEach(event => {
              eventsHtml += `<span class="input-group-addon pointer"
                data-myparam="${setKey}"
                data-myparam-plugin="${prefix}"
                data-myevent="${event}"
                onclick="addToExecutionQueue_settingEvent(this)"
              >
                <i title="${getString(event + "_event_tooltip")}" class="fa ${getString(event + "_event_icon")}">                 
                </i>
              </span>`;
            });
          }

          // construct final HTML for the setting
          setHtml += inputHtml +  eventsHtml + overrideHtml + `
              </div>
            </div>
          `

          // generate settings in the correct prefix (group) section
          $(`#${prefix} .panel-body`).append(setHtml);          
        }
      });

    }

    // init finished
    setTimeout(() => {
      initListInteractionOptions()  // init remove and edit listitem click gestures
    }, 50);
    
    setupSmoothScrolling()
    // try to initialize select2
    initSelect2()
    hideSpinner()

  }

  // display the name of the first person
  // echo $settingsJson[0]->name;
  var settingsNumberDB = <?php echo count($settings)?>;
  var settingsJSON_DB  = <?php echo $settingsJSON_DB  ?>;
  var settingsNumberJSON = <?php echo count($settingsJson->data)?>;

  // Wrong number of settings processing
  if(settingsNumberJSON != settingsNumberDB) 
  {
    showModalOk('WARNING', getString("settings_missing"));    
  }

  // ---------------------------------------------------------
  function saveSettings() {
    if(settingsNumberJSON != settingsNumberDB) 
    {
      console.log(`Error settingsNumberJSON != settingsNumberDB: ${settingsNumberJSON} !=  ${settingsNumberDB}`);

      showModalOk('WARNING', getString("settings_missing_block")); 

      setTimeout(() => {
              clearCache()  
            }, 1500);
         
    } else {
      var settingsArray = [];

      // collect values for each of the different input form controls
      // get settings to determine setting type to store values appropriately
      $.get('api/table_settings.json?nocache=' + Date.now(), function(res) { 
        // loop through the settings definitions from the json
        res["data"].forEach(set => {

          prefix      = set["setGroup"]
          setType     = set["setType"]
          setCodeName = set["setKey"]

          console.log(prefix);

          const setTypeObject = JSON.parse(setType.replace(/'/g, '"'));         
          // console.log(setTypeObject);

          const dataType = setTypeObject.dataType;
          

          // get the element with the input value(s)
          let elements = setTypeObject.elements.filter(element => element.elementHasInputValue === 1);

          //  if none found, take last
          if(elements.length == 0)
          {
            elementWithInputValue = setTypeObject.elements[setTypeObject.elements.length - 1]
          } else
          {
            elementWithInputValue = elements[0]
          }

          const { elementType, elementOptions = [], transformers = [] } = elementWithInputValue;
          const { 
            inputType,
            readOnly,
            isMultiSelect,
            isOrdeable,
            cssClasses,
            placeholder,
            suffix,
            sourceIds,
            separator,
            editable,
            valRes,
            getStringKey,
            onClick,
            onChange,
            customParams,
            customId
          } = handleElementOptions('none', elementOptions, transformers, val = "");

          let value;

          if (dataType === "string" || 
              (dataType === "integer" && (inputType === "number" || inputType === "text"))) {
           
            value = $('#' + setCodeName).val();
            value = applyTransformers(value, transformers);

            settingsArray.push([prefix, setCodeName, dataType, value]);

          } else if (inputType === 'checkbox') {
            
            value = $(`#${setCodeName}`).is(':checked') ? 1 : 0;            

            if(dataType === "boolean")
            {
              value = value == 1 ? "True" : "False";
            }

            value = applyTransformers(value, transformers);
            settingsArray.push([prefix, setCodeName, dataType, value]);

          } else if (dataType === "array" ) {

            let temps = [];

            if(isOrdeable)
            {
              temps = $(`#${setCodeName}`).val()
            } else
            {            
              // make sure to collect all if set as "editable" or selected only otherwise
              $(`#${setCodeName}`).attr("my-editable") == "true" ? additionalSelector = "" : additionalSelector = ":selected"; 
              
              $(`#${setCodeName} option${additionalSelector}`).each(function() {
                const vl = $(this).val();
                if (vl !== '') {
                  temps.push(applyTransformers(vl, transformers));
                }
              });
            }
          
            value = JSON.stringify(temps);

            settingsArray.push([prefix, setCodeName, dataType, value]);

          } else if (dataType === "json") {
            
            value = $('#' + setCodeName).val();
            value = applyTransformers(value, transformers);
            value = JSON.stringify(value, null, 2)
            settingsArray.push([prefix, setCodeName, dataType, value]);

          } else {
            
            console.error(`[saveSettings] Couldn't determine how to handle (setCodeName|dataType|inputType):(${setCodeName}|${dataType}|${inputType})`);

            value = $('#' + setCodeName).val();
            value = applyTransformers(value, transformers);
            console.error(`[saveSettings] Saving value "${value}"`);
            settingsArray.push([prefix, setCodeName, dataType, value]);
          }
        });

        // sanity check to make sure settings were loaded & collected correctly
        if(settingsCollectedCorrectly(settingsArray, settingsJSON_DB))
        {

          console.log(settingsArray);
          console.log( JSON.stringify(settingsArray));
          // return;
          // trigger a save settings event in the backend
          $.ajax({
          method: "POST",
          url: "php/server/util.php",
          data: { 
            function: 'savesettings', 
            settings: JSON.stringify(settingsArray) },
            success: function(data, textStatus) {                    
              
              if(data == "OK")
              {                
                // showMessage (getString("settings_saved"), 5000, "modal_grey");
                // Remove navigation prompt "Are you sure you want to leave..."
                window.onbeforeunload = null;         

                // Reloads the current page
                // setTimeout("clearCache()", 5000);    

                write_notification(`[Settings] Settings saved by the user`, 'info')

                clearCache()
              } else{
                // something went wrong                
                write_notification("[Important] DO NOT REFERSH the page. Open the browser DEV console (F12). Please take a screenshot of it. Submit it (with the nginx and php error logs) as a new issue here: https://github.com/jokob-sk/NetAlertX/issues", 'interrupt')

                console.log("🔽");
                console.log(settingsArray);
                console.log(JSON.stringify(settingsArray));    
                console.log(data);            
                console.log("🔼");
              }
            }
          });
        } 
        
      })

    }
  }

</script>


<!-- INIT THE PAGE -->
<script defer>

  function handleLoadingDialog()
  {
    // Check if app config is read only
    const canReadAndWriteConfig = <?php echo (is_readable($confPath) && is_writable($confPath)) ? 'true' : 'false'; ?>;

    if(!canReadAndWriteConfig)
    {
      showMessage (getString("settings_readonly"), 10000, "modal_red");
      console.log(`app.conf seems to be read only (canRWConfig: ${canReadAndWriteConfig})`);
    } else
    {
      // check if config file has been updated
      $.get('api/app_state.json?nocache=' + Date.now(), function(appState) {   
        
        fileModificationTime = <?php echo filemtime($confPath)*1000;?>;  

        // console.log(appState["settingsImported"]*1000)
        importedMiliseconds = parseInt((appState["settingsImported"]*1000));
        humanReadable = (new Date(importedMiliseconds)).toLocaleString("en-UK", { timeZone: "<?php echo $timeZone?>" });

        // check if displayed settings are outdated
        if(appState["showSpinner"] || fileModificationTime > importedMiliseconds)
        {     
          showSpinner("settings_old")

          setTimeout("handleLoadingDialog()", 1000);

        } else
        {       
          checkInitialization();
        }

        document.getElementById('lastImportedTime').innerHTML = humanReadable; 
      })

    }

  }


  function checkInitialization() {

    if (isAppInitialized()) {
        // App is initialized, hide spinner and proceed with initialization
        console.log("App initialized, proceeding...");
        getData();

        // Reload page if outdated information might be displayed
        if (secondsSincePageLoad() > 10) {
          console.log("App outdated, reloading...");
          clearCache();
        }
    } else {
        console.log("App not initialized, checking again in 1s...");

        // Check again after a delay
        setTimeout(checkInitialization, 1000);
    }
  }
  

  showSpinner()
  handleLoadingDialog()



</script>
