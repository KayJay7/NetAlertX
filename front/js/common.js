/* -----------------------------------------------------------------------------
*  NetAlertX
*  Open Source Network Guard / WIFI & LAN intrusion detector 
*
*  common.js - Front module. Common Javascript functions
*-------------------------------------------------------------------------------
#  Puche 2021 / 2022+ jokob             jokob@duck.com                GNU GPLv3
----------------------------------------------------------------------------- */

// -----------------------------------------------------------------------------
var timerRefreshData = ''

var emptyArr            = ['undefined', "", undefined, null, 'null'];
var UI_LANG = "English";
var settingsJSON = {}


// -----------------------------------------------------------------------------
// Simple session cache withe expiration managed via cookies
// -----------------------------------------------------------------------------
function getCache(key, noCookie = false)
{
  // check cache
  cachedValue = localStorage.getItem(key)

  // console.log(cachedValue);

  if(cachedValue)
  {
    // // check if not expired
    // if(noCookie || getCookie(key + '_session_expiry') != "")
    // {
      return cachedValue;
    // }
  }

  return "";  
}

// -----------------------------------------------------------------------------
function setCache(key, data, expirationMinutes='')
{
  localStorage.setItem(key, data);  

  // // create cookie if expiration set to handle refresh of data
  // if (expirationMinutes != '') 
  // {
  //   setCookie ('cache_session_expiry', 'OK', 1)
  // }
}


// -----------------------------------------------------------------------------
function setCookie (cookie, value, expirationMinutes='') {
  // Calc expiration date
  var expires = '';
  if (typeof expirationMinutes === 'number') {
    expires = ';expires=' + new Date(Date.now() + expirationMinutes *60*1000).toUTCString();
  } 

  // Save Cookie
  document.cookie = cookie + "=" + value + expires;
}

// -----------------------------------------------------------------------------
function getCookie (cookie) {
  // Array of cookies
  var allCookies = document.cookie.split(';');

  // For each cookie
  for (var i = 0; i < allCookies.length; i++) {
    var currentCookie = allCookies[i].trim();

    // If the current cookie is the correct cookie
    if (currentCookie.indexOf (cookie +'=') == 0) {
      // Return value
      return currentCookie.substring (cookie.length+1);
    }
  }

  // Return empty (not found)
  return "";
}


// -----------------------------------------------------------------------------
function deleteCookie (cookie) {
  document.cookie = cookie + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC';
}

// -----------------------------------------------------------------------------
function deleteAllCookies() {
  // Array of cookies
  var allCookies = document.cookie.split(";");

  // For each cookie
  for (var i = 0; i < allCookies.length; i++) {
    var cookie = allCookies[i].trim();
    var eqPos = cookie.indexOf("=");
    var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
    document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 UTC";
    }
}




// -----------------------------------------------------------------------------
// Get settings from the .json file generated by the python backend 
// and cache them, if available, with options 
// -----------------------------------------------------------------------------
function cacheSettings()
{
  return new Promise((resolve, reject) => {
    if(!getCache('completedCalls').includes('cacheSettings'))
    {  
      $.get('api/table_settings.json?nocache=' + Date.now(), function(resSet) { 

        $.get('api/plugins.json?nocache=' + Date.now(), function(resPlug) {        

          pluginsData = resPlug["data"]; 
          settingsData = resSet["data"];  

          settingsData.forEach((set) => {  

            resolvedOptions = createArray(set.Options)
            setPlugObj     = {};
            options_params = [];
          
            // proceed only if first option item contains something to resolve
            if( !set.Code_Name.includes("__metadata") && 
                resolvedOptions.length != 0 && 
                resolvedOptions[0].includes("{value}"))
            {
              // get setting definition from the plugin config if available
              setPlugObj = getPluginSettingObject(pluginsData, set.Code_Name)

              // check if options contains parameters and resolve 
              if(setPlugObj != {} && setPlugObj["options_params"])
              {
                // get option_params for {value} resolution
                options_params = setPlugObj["options_params"]      

                if(options_params != [])
                {
                  // handles only strings of length == 1
                  resolvedOptions = `["${resolveParams(options_params, resolvedOptions[0])}"]`
                }
              }    
            }

            setCache(`pia_set_${set.Code_Name}`, set.Value) 
            setCache(`pia_set_opt_${set.Code_Name}`, resolvedOptions) 
          });
        }).then(() => handleSuccess('cacheSettings', resolve())).catch(() => handleFailure('cacheSettings', reject("cacheSettings already completed")));    // handle AJAX synchronization
      })
    } 
  });
}

// -----------------------------------------------------------------------------
// Get a setting value by key
function getSettingOptions (key) {

  // handle initial load to make sure everything is set-up and cached
  // handleFirstLoad()
 
  result = getCache(`pia_set_opt_${key}`, true);

  if (result == "")
  {
    console.log(`Setting options with key "${key}" not found`)
  }

  return result;
}

// -----------------------------------------------------------------------------
// Get a setting value by key
function getSetting (key) {

  // handle initial load to make sure everything is set-up and cached
  // handleFirstLoad()
 
  result = getCache(`pia_set_${key}`, true);

  if (result == "")
  {
    console.log(`Setting with key "${key}" not found`)
  }

  return result;
}

// -----------------------------------------------------------------------------
// Get language string
// -----------------------------------------------------------------------------
function cacheStrings()
{  
  return new Promise((resolve, reject) => {
    if(!getCache('completedCalls').includes('cacheStrings'))
    {
      // handle core strings and translations
      var allLanguages = ["en_us", "es_es", "de_de", "fr_fr", "it_it", "ru_ru", "nb_no", "pl_pl", "zh_cn"]; // needs to be same as in lang.php

      allLanguages.forEach(function (language_code) {
        $.get(`php/templates/language/${language_code}.json?nocache=${Date.now()}`, function (res) {
          // Iterate over each language
          Object.entries(res).forEach(([key, value]) => {
            // Store translations for each key-value pair
            setCache(`pia_lang_${key}_${language_code}`, value)
          });

          // handle strings and translations from plugins
          $.get(`api/table_plugins_language_strings.json?nocache=${Date.now()}`, function(res) {    
                
            data = res["data"];       

            data.forEach((langString) => {      
              setCache(`pia_lang_${langString.String_Key}_${langString.Language_Code}`, langString.String_Value) 
            });        
          }).then(() => handleSuccess('cacheStrings', resolve())).catch(() => handleFailure('cacheStrings', reject("cacheStrings already completed"))); // handle AJAX synchronization
          
        });
      });
    }
  });
}

// Get translated language string
function getString (key) {

  // handle initial load to make sure everything is set-up and cached
  handleFirstLoad(getString)
 
  UI_LANG = getSetting("UI_LANG");

  lang_code = 'en_us';

  switch(UI_LANG)
  {
    case 'English': 
      lang_code = 'en_us';
      break;
    case 'Spanish': 
      lang_code = 'es_es';
      break;
    case 'German': 
      lang_code = 'de_de';
      break;
    case 'French': 
      lang_code = 'fr_fr';
      break;
    case 'Norwegian': 
      lang_code = 'nb_no';
      break;
    case 'Polish': 
      lang_code = 'pl_pl';
      break;
    case 'Portuguese (Brazil)': 
      lang_code = 'pt_br';
      break;
    case 'Italian': 
      lang_code = 'it_it';
      break;
    case 'Russian': 
      lang_code = 'ru_ru';
      break;
    case 'Chinese (zh_cn)': 
      lang_code = 'zh_cn';
      break;
  }
  result = getCache(`pia_lang_${key}_${lang_code}`, true);


  if(isEmpty(result))
  {    
    result = getCache(`pia_lang_${key}_en_us`, true);
  }

  return result;
}




// -----------------------------------------------------------------------------
// String utilities
// -----------------------------------------------------------------------------
function jsonSyntaxHighlight(json) {
  if (typeof json != 'string') {
       json = JSON.stringify(json, undefined, 2);
  }
  json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
      var cls = 'number';
      if (/^"/.test(match)) {
          if (/:$/.test(match)) {
              cls = 'key';
          } else {
              cls = 'string';
          }
      } else if (/true|false/.test(match)) {
          cls = 'boolean';
      } else if (/null/.test(match)) {
          cls = 'null';
      }
      return '<span class="' + cls + '">' + match + '</span>';
  });
}

function isValidBase64(str) {
  // Base64 characters set
  var base64CharacterSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
  // Remove all valid characters from the string
  var invalidCharacters = str.replace(new RegExp('[' + base64CharacterSet + ']', 'g'), '');
  // If there are any characters left, the string is invalid
  return invalidCharacters === '';
}


// -----------------------------------------------------------------------------
// General utilities
// -----------------------------------------------------------------------------

// check if JSON object
function isJsonObject(value) {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}


// remove unnecessary lines from the result
function sanitize(data)
{
  return data.replace(/(\r\n|\n|\r)/gm,"").replace(/[^\x00-\x7F]/g, "")
}


// -----------------------------------------------------------------------------
// Check and handle locked database
function handle_locked_DB(data)
{
  if(data.includes('database is locked'))
  {
    // console.log(data)    
    showSpinner()

    setTimeout(function() {
      location.reload(); 
    }, 5000);
  }
}

// -----------------------------------------------------------------------------
function numberArrayFromString(data)
{  
  data = JSON.parse(sanitize(data));
  return data.replace(/\[|\]/g, '').split(',').map(Number);
}

// -----------------------------------------------------------------------------
function setParameter (parameter, value) {
  // Retry
  $.get('php/server/parameters.php?action=set&parameter=' + parameter +
    '&value='+ value,
  function(data) {
    if (data != "OK") {
      // Retry
      sleep (200);
      $.get('php/server/parameters.php?action=set&parameter=' + parameter +
        '&value='+ value,
        function(data) {
          if (data != "OK") {
          // alert (data);
          } else {
          // alert ("OK. Second attempt");
          };
      } );
    };
  } );
}


// -----------------------------------------------------------------------------  
function saveData(functionName, id, value) {
  $.ajax({
    method: "GET",
    url: "php/server/devices.php",
    data: { action: functionName, id: id, value:value  },
    success: function(data) {      
        
        if(sanitize(data) == 'OK')
        {
          showMessage("Saved")
          // Remove navigation prompt "Are you sure you want to leave..."
          window.onbeforeunload = null;
        } else
        {
          showMessage("ERROR")
        }        

      }
  });

}


// -----------------------------------------------------------------------------
// create a link to the device
function createDeviceLink(input)
{
  if(checkMacOrInternet(input))
  {
    return `<span class="anonymizeMac"><a href="/deviceDetails.php?mac=${input}" target="_blank">${getNameByMacAddress(input)}</a><span>`
  }

  return input;
}


// -----------------------------------------------------------------------------
// remove an item from an array
function removeItemFromArray(arr, value) {
  var index = arr.indexOf(value);
  if (index > -1) {
    arr.splice(index, 1);
  }
  return arr;
}

// -----------------------------------------------------------------------------
function sleep(milliseconds) {
  const date = Date.now();
  let currentDate = null;
  do {
    currentDate = Date.now();
  } while (currentDate - date < milliseconds);
}

// --------------------------------------------------------- 
somethingChanged = false;
function settingsChanged()
{
  somethingChanged = true;
  // Enable navigation prompt ... "Are you sure you want to leave..."
  window.onbeforeunload = function() {  
    return true;
  };
}

// -----------------------------------------------------------------------------
// Get Anchor from URL
function getUrlAnchor(defaultValue){

  target = defaultValue

  var url = window.location.href;
  if (url.includes("#")) {

    // default selection
    selectedTab = defaultValue

    // the #target from the url
    target = window.location.hash.substr(1) 

    // get only the part between #...?
    if(target.includes('?'))
    {
      target = target.split('?')[0]
    }
  
    return target
  
  }

}

// -----------------------------------------------------------------------------
// get query string from URL
function getQueryString(key){
  params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams, prop) => searchParams.get(prop),
  });

  tmp = params[key] 

  if(emptyArr.includes(tmp))
  {
    var queryParams = {};
    fullUrl = window.location.toString();

    // console.log(fullUrl);

    if (fullUrl.includes('?')) {
      var queryString = fullUrl.split('?')[1];
  
      // Split the query string into individual parameters
      var paramsArray = queryString.split('&');
  
      // Loop through the parameters array
      paramsArray.forEach(function(param) {
          // Split each parameter into key and value
          var keyValue = param.split('=');
          var keyTmp = decodeURIComponent(keyValue[0]);
          var value = decodeURIComponent(keyValue[1] || '');
  
          // Store key-value pair in the queryParams object
          queryParams[keyTmp] = value;
      });
    }

    // console.log(queryParams);

    tmp = queryParams[key]
  }

  result = emptyArr.includes(tmp) ? "" : tmp;

  return result
}  
// -----------------------------------------------------------------------------
function translateHTMLcodes (text) {
  if (text == null || emptyArr.includes(text)) {
    return null;
  } else if (typeof text === 'string' || text instanceof String)
  {
    var text2 = text.replace(new RegExp(' ', 'g'), "&nbsp");
    text2 = text2.replace(new RegExp('<', 'g'), "&lt");
    return text2;
  }

  return "";
}


// -----------------------------------------------------------------------------
function stopTimerRefreshData () {
  try {
    clearTimeout (timerRefreshData); 
  } catch (e) {}
}


// -----------------------------------------------------------------------------
function newTimerRefreshData (refeshFunction, timeToRefresh) {
  
  if(timeToRefresh && (timeToRefresh != 0 || timeToRefresh != ""))
  {
    time = parseInt(timeToRefresh)
  } else
  {
    time = 60000
  }

  timerRefreshData = setTimeout (function() {
    refeshFunction();
  }, time);
}


// -----------------------------------------------------------------------------
function debugTimer () {
  $('#pageTitle').html (new Date().getSeconds());
}

// -----------------------------------------------------------------------------
function secondsSincePageLoad() {
  // Get the current time
  var currentTime = Date.now();

  // Get the time when the page was loaded
  var pageLoadTime = performance.timeOrigin;

  // Calculate the difference in milliseconds
  var timeDifference = currentTime - pageLoadTime;

  // Convert milliseconds to seconds
  var secondsAgo = Math.floor(timeDifference / 1000);

  return secondsAgo;
}

// -----------------------------------------------------------------------------
// Open url in new tab
function openInNewTab (url) {
  window.open(url, "_blank");
}

// ----------------------------------------------------------------------------- 
// Navigate to URL if the current URL is not in the provided list of URLs
function openUrl(urls) {
  var currentUrl = window.location.href;
  var mainUrl = currentUrl.match(/^.*?(?=#|\?|$)/)[0]; // Extract main URL

  var isMatch = false;

  $.each(urls,function(index, obj){

    // remove . for comaprison if in the string, e.g.: ./devices.php
    arrayUrl = obj.replace('.','')

    // check if we are on a url contained in the array
    if(mainUrl.includes(arrayUrl))
    {
      isMatch = true;
    }
  });

  // if we are not, redirect
  if (isMatch == false) {
    window.location.href = urls[0]; // Redirect to the first URL in the list if not found
  }
}


// -----------------------------------------------------------------------------
function navigateToDeviceWithIp (ip) {

  $.get('api/table_devices.json', function(res) {    
        
    devices = res["data"];

    mac = ""
    
    $.each(devices, function(index, obj) {
      
      if(obj.dev_LastIP.trim() == ip.trim())
      {
        mac = obj.dev_MAC;

        window.open(window.location.origin +'/deviceDetails.php?mac=' + mac , "_blank");
      }
    });
    
  });
}

// -----------------------------------------------------------------------------
function getNameByMacAddress(macAddress) {
  return getDeviceDataByMacAddress(macAddress, "dev_Name")
}

// -----------------------------------------------------------------------------
// Check if MAC or Internet
function checkMacOrInternet(inputStr) {
  // Regular expression pattern for matching a MAC address
  const macPattern = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;

  if (inputStr.toLowerCase() === 'internet') {
      return true;
  } else if (macPattern.test(inputStr)) {
      return true;
  } else {
      return false;
  }
}

// -----------------------------------------------------------------------------
// Gte MAC from query string
function getMac(){
  params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams, prop) => searchParams.get(prop),
  });

  return params.mac
}  

// -----------------------------------------------------------------------------
// A function used to make the IP address orderable
function isValidIPv6(ipAddress) {
  // Regular expression for IPv6 validation
  const ipv6Regex = /^([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}$|^([0-9a-fA-F]{1,4}:){1,7}:|^([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}$|^([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}$|^([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}$|^([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}$|^([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}$|^[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})$/;

  return ipv6Regex.test(ipAddress);
}

function formatIPlong(ipAddress) {
  if (ipAddress.includes(':') && isValidIPv6(ipAddress)) {
    const parts = ipAddress.split(':');

    return parts.reduce((acc, part, index) => {
      if (part === '') {
        const remainingGroups = 8 - parts.length + 1;
        return acc << (16 * remainingGroups);
      }

      const hexValue = parseInt(part, 16);
      return acc | (hexValue << (112 - index * 16));
    }, 0);
  } else {
    // Handle IPv4 address
    const parts = ipAddress.split('.');

    if (parts.length !== 4) {
      console.log("⚠ Invalid IPv4 address: " + ipAddress);
      return -1; // or any other default value indicating an error
    }

    return (parseInt(parts[0]) << 24) |
           (parseInt(parts[1]) << 16) |
           (parseInt(parts[2]) << 8) |
           parseInt(parts[3]);
  }
}

// -----------------------------------------------------------------------------
// Check if MAC is a random one
function isRandomMAC(mac)
{
  isRandom = false;

  isRandom = ["2", "6", "A", "E", "a", "e"].includes(mac[1]); 

  // if detected as random, make sure it doesn't start with a prefix which teh suer doesn't want to mark as random
  if(isRandom)
  {
    $.each(createArray(getSetting("UI_NOT_RANDOM_MAC")), function(index, prefix) {

      if(mac.startsWith(prefix))
      {
        isRandom = false;     
      }    
      
    });
    
  }

  return isRandom;
}

  // ---------------------------------------------------------  
  // Generate an array object from a string representation of an array
  function createArray(input) {
    // Empty array
    if (input === '[]') {
      return [];
    }
  
    // Regex pattern for brackets
    const patternBrackets = /(^\s*\[)|(\]\s*$)/g;
    const replacement = '';
  
    // Remove brackets
    const noBrackets = input.replace(patternBrackets, replacement);
  
    const options = [];
  
    // Detect the type of quote used after the opening bracket
    const firstChar = noBrackets.trim()[0];
    const isDoubleQuoted = firstChar === '"';
    const isSingleQuoted = firstChar === "'";
  
    // Create array while handling commas within quoted segments
    let currentSegment = '';
    let withinQuotes = false;
    for (let i = 0; i < noBrackets.length; i++) {
      const char = noBrackets[i];
      if ((char === '"' && !isSingleQuoted) || (char === "'" && !isDoubleQuoted)) {
        withinQuotes = !withinQuotes;
      }
      if (char === ',' && !withinQuotes) {
        options.push(currentSegment.trim());
        currentSegment = '';
      } else {
        currentSegment += char;
      }
    }
    // Push the last segment
    options.push(currentSegment.trim());
  
    // Remove quotes based on detected type
    options.forEach((item, index) => {
      let trimmedItem = item.trim();
      // Check if the string starts and ends with the same type of quote
      if ((isDoubleQuoted && trimmedItem.startsWith('"') && trimmedItem.endsWith('"')) ||
          (isSingleQuoted && trimmedItem.startsWith("'") && trimmedItem.endsWith("'"))) {
        // Remove the quotes
        trimmedItem = trimmedItem.substring(1, trimmedItem.length - 1);
      }
      options[index] = trimmedItem;
    });
  
    return options;
  }

// -----------------------------------------------------------------------------
// A function to get a device property using the mac address as key and DB column nakme as parameter
//  for the value to be returned
function getDeviceDataByMacAddress(macAddress, dbColumn) {

  const sessionDataKey = 'devicesListAll_JSON';  
  const devicesCache = getCache(sessionDataKey);

  if (!devicesCache || devicesCache == "") {
      console.error(`Session variable "${sessionDataKey}" not found.`);
      return "Unknown";
  }

  const devices = JSON.parse(devicesCache);

  for (const device of devices) {
      if (device["dev_MAC"].toLowerCase() === macAddress.toLowerCase()) {

          return device[dbColumn];
      }
  }

  return "Unknown"; // Return a default value if MAC address is not found
}

// -----------------------------------------------------------------------------
// Cache teh devices as one JSON
function cacheDevices()
{ 

  return new Promise((resolve, reject) => {

    if(!getCache('completedCalls').includes('cacheDevices'))
    {
      $.get('api/table_devices.json', function(data) {    
        
        // console.log(data)

        devicesListAll_JSON = data["data"]

        devicesListAll_JSON_str = JSON.stringify(devicesListAll_JSON)

        if(devicesListAll_JSON_str == "")
        {
          showSpinner()

          setTimeout(() => {
            cacheDevices()
          }, 1000);
        }
        // console.log(devicesListAll_JSON_str);

        setCache('devicesListAll_JSON', devicesListAll_JSON_str)

        // console.log(getCache('devicesListAll_JSON'))
      }).then(() => handleSuccess('cacheDevices', resolve())).catch(() => handleFailure('cacheDevices', reject("cacheDevices already completed"))); // handle AJAX synchronization
    } 
  });
}

var devicesListAll_JSON      = [];   // this will contain a list off all devices 

// -----------------------------------------------------------------------------
function isEmpty(value)
{
  return emptyArr.includes(value)
}

// -----------------------------------------------------------------------------
function mergeUniqueArrays(arr1, arr2) {
  let mergedArray = [...arr1]; // Make a copy of arr1

  arr2.forEach(element => {
      if (!mergedArray.includes(element)) {
          mergedArray.push(element);
      }
  });

  return mergedArray;
}

// -----------------------------------------------------------------------------
// Generate a GUID
function getGuid() {
  return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c =>
    (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
  );
}

// -----------------------------------------------------------------------------
// UI 
// -----------------------------------------------------------------------------
// -----------------------------------------------------------------------------


// -----------------------------------------------------------------------------
//  Loading Spinner overlay
// -----------------------------------------------------------------------------
function showSpinner(stringKey='Loading')
{

  if(stringKey == "")
  {
    text = ''
  } else
  {
    text = getString(stringKey)
  }

  if($("#loadingSpinner").length)
  {    
    $("#loadingSpinner").show();
  }
  else{    
    html =  `
    <!-- spinner -->
    <div id="loadingSpinner" style="display: block">
      <div class="pa_semitransparent-panel"></div>
      <div class="panel panel-default pa_spinner">
        <table>
          <td width="130px" align="middle">${text}</td>
          <td><i class="ion ion-ios-loop-strong fa-spin fa-2x fa-fw"></td>
        </table>
      </div>
    </div>
    `

    $(".wrapper").append(html)
  }
}
// -----------------------------------------------------------------------------
function hideSpinner()
{
  $("#loadingSpinner").hide()
}


// --------------------------------------------------------
// Calls a backend function to add a front-end event to an execution queue
function updateApi()
{

  // value has to be in format event|param. e.g. run|ARPSCAN
  action = `update_api|devices,appevents`

  $.ajax({
    method: "POST",
    url: "php/server/util.php",
    data: { function: "addToExecutionQueue", action: action  },
    success: function(data, textStatus) {
        console.log(data)
    }
  })
}

// ----------------------------------------------------------------------------- 
// handling smooth scrolling
// ----------------------------------------------------------------------------- 
function setupSmoothScrolling() {
  // Function to scroll to the element
  function scrollToElement(id) {
      $('html, body').animate({
          scrollTop: $("#" + id).offset().top - 50
      }, 1000);
  }

  // Scroll to the element when clicking on anchor links
  $('a[href*="#"]').on('click', function(event) {
      var href = $(this).attr('href');
      if (href !=='#' && href && href.includes('#') && !$(this).is('[data-toggle="collapse"]')) {
          var id = href.substring(href.indexOf("#") + 1); // Get the ID from the href attribute
          if ($("#" + id).length > 0) {
              event.preventDefault(); // Prevent default anchor behavior
              scrollToElement(id); // Scroll to the element
          }
      }
  });

  // Check if there's an ID in the URL and scroll to it
  var url = window.location.href;
  if (url.includes("#")) {
      var idFromURL = url.substring(url.indexOf("#") + 1);
      if (idFromURL != "" && $("#" + idFromURL).length > 0) {
          scrollToElement(idFromURL);
      }
  }
}

// -------------------------------------------------------------------
// Function to check if options_params contains a parameter with type "sql"
function hasSqlType(params) {
  for (let param of params) {
      if (param.type === "sql") {
          return true; // Found a parameter with type "sql"
      }
  }
  return false; // No parameter with type "sql" found
}

// -------------------------------------------------------------------
// Function to check if string is SQL query
function isSQLQuery(query) {
  // Regular expression to match common SQL keywords and syntax with word boundaries
  var sqlRegex = /\b(SELECT|INSERT|UPDATE|DELETE|CREATE|DROP|ALTER|FROM|JOIN|WHERE|SET|VALUES|GROUP BY|ORDER BY|LIMIT)\b/i;

  return sqlRegex.test(query);
}


// -------------------------------------------------------------------
// Get corresponding plugin setting object
function getPluginSettingObject(pluginsData, setting_key, unique_prefix ) {

  result = {}
  unique_prefix == undefined ? unique_prefix = setting_key.split("_")[0] : unique_prefix = unique_prefix;
  
  $.each(pluginsData, function (i, plgnObj){
    // go thru plugins
    if(plgnObj.unique_prefix == unique_prefix)
    {
      // go thru plugin settings
      $.each(plgnObj["settings"], function (j, setObj){
        
        if(`${unique_prefix}_${setObj.function}` == setting_key)
        {          
          result = setObj          
        }

      });
    }
  });

  return result
}

// -------------------------------------------------------------------
// Resolve all option parameters
function resolveParams(params, template) {
  params.forEach(param => {
      // Check if the template includes the parameter name
      if (template.includes("{" + param.name + "}")) {
          // If the parameter type is 'setting', retrieve setting value
          if (param.type == "setting") {
              var value = getSetting(param.value);
              // Replace placeholder with setting value
              template = template.replace("{" + param.name + "}", value);
          } else {
              // If the parameter type is not 'setting', use the provided value
              template = template.replace("{" + param.name + "}", param.value);
          }
      }
  });

  // Log the resolved template
  // console.log(template);

  // Return the resolved template
  return template;
}

// -----------------------------------------------------------------------------
// check if two arrays contain same values even if out of order
function arraysContainSameValues(arr1, arr2) {
  // Check if both parameters are arrays
  if (!Array.isArray(arr1) || !Array.isArray(arr2)) {
    return false;
  } else
  {  
    // Sort and stringify arrays, then compare
    return JSON.stringify(arr1.slice().sort()) === JSON.stringify(arr2.slice().sort());
  }
}

// -----------------------------------------------------------------------------
// initialize
// -----------------------------------------------------------------------------
// -----------------------------------------------------------------------------

// Define a unique key for storing the flag in sessionStorage
const sessionStorageKey = "myScriptExecuted_common_js";
var completedCalls = []
var completedCalls_final = ['cacheSettings', 'cacheStrings', 'cacheDevices'];

// -----------------------------------------------------------------------------
// Clearing all the caches
function clearCache() {
  showSpinner();
  sessionStorage.clear();
  localStorage.clear();
  setTimeout(() => {
  window.location.reload();
}, 500);
}

// -----------------------------------------------------------------------------
// Function to check if cache needs to be refreshed because of setting changes
function checkSettingChanges() {
  $.get('api/app_state.json?nocache=' + Date.now(), function(appState) {   
    const importedMilliseconds = parseInt(appState["settingsImported"] * 1000);
    const lastReloaded = parseInt(sessionStorage.getItem(sessionStorageKey + '_time'));

    if (importedMilliseconds > lastReloaded) {
      console.log("Cache needs to be refreshed because of setting changes");
      setTimeout(() => {
        clearCache();
      }, 500);
    }
  });
}

// -----------------------------------------------------------------------------
// Display spinner and reload page if not yet initialized
async function handleFirstLoad(callback) {
  if (!isAppInitialized()) {
    await new Promise(resolve => setTimeout(resolve, 1000));
    callback();
  }
}

// -----------------------------------------------------------------------------
// Check if the code has been executed before by checking sessionStorage
function isAppInitialized() {
   return arraysContainSameValues(getCache("completedCalls").split(',').filter(Boolean), completedCalls_final);
}

// Define a function that will execute the code only once
async function executeOnce() {
  showSpinner();

  if (!isAppInitialized()) {
    try {
      await cacheStrings();
      await cacheSettings();
      await cacheDevices();
      console.log("✅ All AJAX callbacks have completed");
      onAllCallsComplete();
    } catch (error) {
      console.error("Error:", error);
    }
  }
}

// -----------------------------------------------------------------------------
// Function to handle successful completion of an AJAX call
const handleSuccess = (callName) => {
  console.log(`AJAX call successful: ${callName}`);
  completedCalls.push(callName);
  setCache('completedCalls', mergeUniqueArrays(getCache('completedCalls').split(','), [callName]));
};

// -----------------------------------------------------------------------------
// Function to handle failure of an AJAX call
const handleFailure = (callName, callback) => {
  console.error(`AJAX call ${callName} failed`);
  // Implement retry logic here if needed
};

// -----------------------------------------------------------------------------
// Function to execute when all AJAX calls have completed
const onAllCallsComplete = () => {
  completedCalls = mergeUniqueArrays(getCache('completedCalls').split(','), completedCalls);
  setCache('completedCalls', completedCalls);

  // Check if all necessary strings are initialized
  if (areAllStringsInitialized()) {
    sessionStorage.setItem(sessionStorageKey, "true");
    const millisecondsNow = Date.now();
    sessionStorage.setItem(sessionStorageKey + '_time', millisecondsNow);

    console.log('✔ Cache initialized');
  } else {
    // If not all strings are initialized, retry initialization
    console.log('❌ Not all strings are initialized. Retrying...');
    executeOnce();
    return;
  }

  // Call any other initialization functions here if needed

  // No need for location.reload() here
};

// Function to check if all necessary strings are initialized
const areAllStringsInitialized = () => {
  // Implement logic to check if all necessary strings are initialized
  // Return true if all strings are initialized, false otherwise
  return getString('UI_LANG') != ""
};

// Call the function to execute the code
executeOnce();

// Set timer for regular checks 
setTimeout(() => {

  // page refresh if configured
  const refreshTime = getSetting("UI_REFRESH");
  if (refreshTime && refreshTime !== "0" && refreshTime !== "") {
    newTimerRefreshData(clearCache, parseInt(refreshTime)*1000);
  }

  // Check if page needs to refresh due to setting changes
  checkSettingChanges()

}, 10000);


console.log("init common.js");







