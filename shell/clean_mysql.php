<?php

// Cleans out some tables that tend to get filled with irrelevant data over time

const DRYRUN = false;

$queries = [];

// dataflow_batch_import
$queries[] = "DELETE FROM `dataflow_batch_import`
  WHERE batch_import_id <= (
    SELECT batch_import_id
    FROM (
      SELECT batch_import_id
      FROM `dataflow_batch_import`
      ORDER BY batch_import_id DESC
      LIMIT 1 OFFSET 10000 -- keep this many records
    ) foo
  )
";
$queries[] = "OPTIMIZE TABLE `dataflow_batch_import`";

// index_event
$queries[] = "DELETE FROM index_event WHERE created_at < NOW() - INTERVAL 365 DAY";
$queries[] = "OPTIMIZE TABLE `index_event`";

require_once __DIR__."/../app/Mage.php";
Mage::app("default");
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$write_db = Mage::getSingleton("core/resource")->getConnection("core_write");

foreach($queries as $query) {
  if(DRYRUN !== true) {
    $result = $write_db->query($query);
    echo "Affected rows: {$result->rowCount()}".PHP_EOL.PHP_EOL;
  } else {
    echo $query.PHP_EOL;
  }
}
