<?php
//------------------------------------------------------------------------------
//  NetAlertX
//  Open Source Network Guard / WIFI & LAN intrusion detector 
//
//  parameters.php - Front module. Server side. Manage Parameters
//------------------------------------------------------------------------------
#  Puche 2022+ jokob             jokob@duck.com                GNU GPLv3
//------------------------------------------------------------------------------


//------------------------------------------------------------------------------
  // External files
  require dirname(__FILE__).'/init.php';
 

//------------------------------------------------------------------------------
//  Action selector
//------------------------------------------------------------------------------
  // Set maximum execution time to 15 seconds
  ini_set ('max_execution_time','15');

  $skipCache = FALSE;
  $expireMinutes = 5;
  $defaultValue = '';
  $dbtable = '';
  $columns = '';
  $values  = '';
  

  if (isset ($_REQUEST['skipcache'])) {
    $skipCache = TRUE;    
  }

  if (isset ($_REQUEST['defaultValue'])) {
    $defaultValue = $_REQUEST['defaultValue'];    
  }

  if (isset ($_REQUEST['expireMinutes'])) {
    $expireMinutes = $_REQUEST['expireMinutes'];    
  }

  if (isset ($_REQUEST['columnName'])) {
    $columnName = $_REQUEST['columnName'];    
  }

  if (isset ($_REQUEST['id'])) {
    $id = $_REQUEST['id'];    
  }

  if (isset ($_REQUEST['values'])) {
    $values = $_REQUEST['values'];    
  }

  if (isset ($_REQUEST['columns'])) {
    $columns = $_REQUEST['columns'];    
  }

  if (isset ($_REQUEST['rawSql'])) {
    $rawSql = $_REQUEST['rawSql'];    
  }

  if (isset ($_REQUEST['dbtable'])) {
    $dbtable = $_REQUEST['dbtable'];    
  }
  // TODO: Security, read, delete, edge cases
  // Action functions
  if (isset ($_REQUEST['action']) && !empty ($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
    switch ($action) {
      case 'create':    create($defaultValue, $expireMinutes, $dbtable, $columns, $values ); break;
      case 'read'  :    read($rawSql);    break;
      case 'update':    update($columnName, $id, $defaultValue, $expireMinutes, $dbtable, $columns, $values);  break;
      case 'delete':    delete($columnName, $id, $dbtable);  break;
      case 'checkLock':     checkLock();  break;
      default:     logServerConsole ('Action: '. $action);  break;
    }
  }


//------------------------------------------------------------------------------
//  read
//------------------------------------------------------------------------------
function read($rawSql) {
  global $db;  

  // Construct the SQL query to select values
  $sql = $rawSql;

  // Execute the SQL query
  $result = $db->query($sql);

  // Check if the query executed successfully
  if (! $result == TRUE) {
    // Output an error message if the query failed
    echo "Error reading data\n\n " .$sql." \n\n". $db->lastErrorMsg();
    return;
  } else
  {
    // Output $result
    // Fetching rows from the result object and storing them in an array
    $rows = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }

    // Converting the array to JSON
    $json = json_encode($rows);

    // Outputting the JSON
    echo $json;

    return;
   }
}


//------------------------------------------------------------------------------
//  update
//------------------------------------------------------------------------------
function update($columnName, $id, $defaultValue, $expireMinutes, $dbtable, $columns, $values) {

  global $db;  

  // Handle one or multiple columns
  if(strpos($columns, ',') !== false) {
      $columnsArr = explode(",", $columns);
  } else {
      $columnsArr = array($columns);
  }

  // Handle one or multiple values
  if(strpos($values, ',') !== false) {
      $valuesArr = explode(",", $values);
  } else {
      $valuesArr = array($values);
  }

  // Handle one or multiple IDs
  if(strpos($id, ',') !== false) {
      $idsArr = explode(",", $id);
      $idsPlaceholder = rtrim(str_repeat('?,', count($idsArr)), ',');
  } else {
      $idsArr = array($id);
      $idsPlaceholder = '?';
  }

  // Build column-value pairs string
  $columnValues = '';
  foreach($columnsArr as $column) {
      $columnValues .= '"' . $column . '" = ?,';
  }
  // Remove trailing comma
  $columnValues = rtrim($columnValues, ',');

  // Construct the SQL query
  $sql = 'UPDATE ' . $dbtable . ' SET ' . $columnValues . ' WHERE ' . $columnName . ' IN (' . $idsPlaceholder . ')';
  
  // Prepare the statement
  $stmt = $db->prepare($sql);

  // Check for errors
  if(!$stmt) {
      echo "Error preparing statement: " . $db->lastErrorMsg();
      return;
  }

  // Bind the parameters
  $paramTypes = str_repeat('s', count($columnsArr));
  foreach($valuesArr as $i => $value) {
      $stmt->bindValue($i + 1, $value);
  }
  foreach($idsArr as $i => $idValue) {
      $stmt->bindValue(count($valuesArr) + $i + 1, $idValue);
  }

  // Execute the statement
  $result = $stmt->execute();

  $changes = $db->changes();
  if ($changes == 0) {
    // Insert new value
    create( $defaultValue, $expireMinutes, $dbtable, $columns, $values);
  }

  // update cache  
  $uniqueHash = hash('ripemd160', $dbtable . $columns);
  setCache($uniqueHash, $values, $expireMinutes);

  echo 'OK' ;  
}


//------------------------------------------------------------------------------
//  create
//------------------------------------------------------------------------------
function create( $defaultValue, $expireMinutes, $dbtable, $columns, $values)
{
  global $db;

  echo "NOT IMPLEMENTED!\n\n";
  return;

  // // Insert new value
  // $sql = 'INSERT INTO '.$dbtable.' ('.$columns.')
  //         VALUES ("'. quotes($parameter) .'",
  //                 "'. $values     .'")';
  // $result = $db->query($sql);

  // if (! $result == TRUE) {
  //   echo "Error creating entry\n\n$sql \n\n". $db->lastErrorMsg();
  //   return;
  // }
}

//------------------------------------------------------------------------------
//  delete
//------------------------------------------------------------------------------
function delete($columnName, $id, $dbtable)
{
  global $db;

  // Handle one or multiple ids
  if(strpos($id, ',') !== false) 
  {
    $idsArr = explode(",", $id);
  } else
  {
    $idsArr = array($id);
  }

  // Initialize an empty string to store the comma-separated list of IDs
  $idsStr = "";
  
  // Iterate over each ID
  foreach ($idsArr as $index => $item) 
  {
    // Append the current ID to the string
    $idsStr .= '"' . $item . '"';
    
    // Add a comma if the current ID is not the last one
    if ($index < count($idsArr) - 1) {
      $idsStr .= ', ';
    }
  }

  // Construct the SQL query to delete entries based on the given IDs
  $sql = 'DELETE FROM '.$dbtable.' WHERE "'.$columnName.'" IN ('. $idsStr .')';

  // Execute the SQL query
  $result = $db->query($sql);

  // Check if the query executed successfully
  if (! $result == TRUE) {
    // Output an error message if the query failed
    echo "Error deleting entry\n\n".$sql." \n\n". $db->lastErrorMsg();
    return;
  } else
  {
    // Output 'OK' if the deletion was successful
    echo 'OK' ; 
    return;
  }
}



//------------------------------------------------------------------------------
//  check if the database is locked
//------------------------------------------------------------------------------
function checkLock() {

  return checkLock_file() or checkLock_db(); 

}

function checkLock_db() {
  global $DBFILE, $db_locked;

  $file = fopen($DBFILE, 'r+');

  if (!$file or $db_locked) {
      // Could not open the file
      return 1;
  }

  if (flock($file, LOCK_EX | LOCK_NB)) {
      // Lock acquired, meaning the database is not locked by another process
      flock($file, LOCK_UN); // Release the lock
      return 0; // Not locked
  } else {
      // Could not acquire lock, meaning the database is locked
      fclose($file);
      return 1; // Locked
  }

  
}

function checkLock_file() {
    $DBFILE_LOCKED_FILE = dirname(__FILE__).'/../../../logs/db_is_locked.log';

        if (file_exists($DBFILE_LOCKED_FILE)) {
            $status = file_get_contents($DBFILE_LOCKED_FILE);
            return $status; // Output the content of the lock file (0 or 1)
        } else {
            return '0'; // If the file doesn't exist, consider it as unlocked
        }
        exit;
}

?>
