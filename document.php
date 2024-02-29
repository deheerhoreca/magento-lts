<?php

// /document/{entity_id}/{document_hash}-{brand}-{sku}-{document_seo_keywords_slug}.{document_extension}
// (everything after {document_hash} is not final yet)~

// [
  // {
    // "filetype": "pdf",
    // "hash": "713ca767",
    // "language": ["en", "de", "fr", "nl"],
    // "title": "264331 Handleiding",
    // "normalized_type": "manual",
    // "url": "https:\/\/hendi.xcdn.nl\/large\/-\/264331_Quartz_Salamander_Maxi_Manual.pdf"
  // }
// ]
// $url = "https://hendi.xcdn.nl/large/-/264331_Quartz_Salamander_Maxi_Manual.pdf";
// $hash = hash("crc32", $url, false);
// dump($hash);
// ==> https://dev.chefstore.nl/document/60617/713ca767/hendi-264331-manual-de-en-fr-nl.pdf

// echo "<pre>$output</pre>";
// echo "media/dhh/documents/{$output}";
// echo "<embed type='application/pdf' src='media/dhh/documents/{$output}' width='500' height='800'>";

require __DIR__ . '/vendor/autoload.php';


// Config
const DOCUMENTS_CACHE_DIR = __DIR__."/media/documents";
const STORE_ID            = 1;
const DEBUG               = true;

// Bootstrap
if(!is_dir(DOCUMENTS_CACHE_DIR)) {
  mkdir(DOCUMENTS_CACHE_DIR, 0775, $recursive = true);
}

// Init Mage
require_once __DIR__."/app/Mage.php";
Mage::app();
Mage::init();
Mage::app()->setCurrentStore(STORE_ID);
Mage::log("OpenMage initialized", null, "system.log", true);

// Run
$current_url = Mage::helper('core/url')->getCurrentUrl();
$current_url = filter_var($current_url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);

if($current_url === false) {
  redirect("/", null, "No current URL");
}

try {
  $parsed_url = parse_url($current_url);
} catch(Exception $e) {
  redirect("/", null, "Failed to parse URL");
}

if(empty($parsed_url["path"])) {
  redirect("/", null, "No path found in parsed URL");
}

$url_params = explode("/", (string) $parsed_url["path"]);
if(!is_array($url_params)) {
  redirect("/", null, "No URL params found in parsed URL");
}

$url_params = array_filter($url_params, function($value) {
  return strlen($value) > 0;
});

$url_params = array_values($url_params);

if(sizeof($url_params) !== 4) {
  redirect("/", null, "URL param count wrong");
}

$entity_id  = (int) $url_params[1];
$hash       = (string) $url_params[2];
$doc_name   = (string) $url_params[3];

$_resource = Mage::getSingleton('catalog/product')->getResource();
$external_documents_json = $_resource->getAttributeRawValue($entity_id, "external_documents_json", STORE_ID);
dump($external_documents_json);

if(empty($external_documents_json)) {
  redirect("/", null, "No external documents found in product with entity_id ".json_encode($entity_id));
}

try {
  $external_documents = json_decode($external_documents_json, true);
} catch(Exception $e) {
  dump($e);
  redirect("/", null, "Failed to decode JSON");
}

$the_document = false;
foreach($external_documents as $i => $external_document) {
  // if(isset($external_document["hash"]) && $external_document["hash"] === $hash) {
  if($i == $hash) { // @TODO REPLACE
    $the_document = $external_document;
  }
}

if($the_document === false) {
  redirect("/", null, "Failed to locate document hash");
}

dump($the_document);

if(!isset($the_document["url"])) {
  redirect("/", null, "Document has no URL");
}

$document_url = filter_var($the_document["url"], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);

if(!$document_url) {
  redirect("/", null, "Document has invalid URL: ".json_encode($document_url));
}

if(!isset($the_document["filetype"]) || $the_document["filetype"] !== "pdf") {
  redirect($document_url, null, "Document is not a PDF or unknown type");
}

$url    = escapeshellarg($document_url);
$title  = escapeshellarg($the_document["title"]);
$output = escapeshellarg(DOCUMENTS_CACHE_DIR."/{$hash}.{$the_document["filetype"]}");

$command = new mikehaertl\shellcommand\Command("python3 ".__DIR__."/shell/PDF-manipulation/src/Convert-PDF.py");
$command->addArg("--url {$url}", null, false);
$command->addArg("--title {$title}", null, false);
$command->addArg("--output {$output}", null, false);
$command->addArg("--x-form-compress", null, false);
$command->addArg("--x-form-prepend", null, false);
$command->addArg("--x-form-watermark", null, false);

dump($command->getExecCommand());

if($command->execute()) {
  dump($command->getOutput());
} else {
  $exit_code = $command->getExitCode();
  dump($exit_code);
  dump($command->getError());
  redirect($the_document["url"], null, "Failed to convert PDF");
}

function redirect($url, $status_code = null, string $msg = "") {
  if($status_code === null) {
    $status_code = 303;
  }
  if(DEBUG) {
  print_r("REDIRECT ({$msg}): {$url}");
  } else {
    header('Location: ' . $url, true, $status_code);
  }
  die();
}



// $_all = Mage::getSingleton('catalog/product')
  // ->getCollection()
  // ->addAttributeToSelect('external_documents_json');
  // $i = 0;
  // foreach($_all as $product) {
    // $i++;
    // if( $i > 2 ){
      // break;
    // }

    // tmp
    // $document_json_str = "[
      // {
        // \"filetype\": \"pdf\",
        // \"hash\": \"713ca767\",
        // \"language\": [\"en\", \"de\", \"fr\", \"nl\"],
        // \"title\": \"264331 Handleiding\",
        // \"normalized_type\": \"manual\",
        // \"url\": \"https:\/\/hendi.xcdn.nl\/large\/-\/264331_Quartz_Salamander_Maxi_Manual.pdf\"
      // }
    // ]";
    // $document_json = json_decode($document_json_str, true);
    // dump($document_json);

    // $document_json[0]["x-form-compress"] = true;
    // $document_json[0]["x-form-prepend"] = true;
    // $document_json[0]["x-form-watermark"] = true;
    // dump($document_json);

    // $document_json_str = json_encode($document_json);
    // dump($document_json_str);
    // $document_json_str = escapeshellarg($document_json_str);
    // $cmd = "python3 /var/www/vhosts/chefstore.nl/httpdocs/deheerhoreca-magento/app/design/frontend/rwd/dhh/template/easytabs/PDF-manipulation/src/Convert-PDF.py --doc={$document_json_str}";
    // dump($cmd);

    // $lastline = exec($cmd, $output, $return_code);
    // dump("EXIT CODE: ".$return_code);
    // dump($output);

    // $entityId = $product->getData(\"entity_id');
    // echo $entity_id;
    // echo "<br>";
    // echo $entityId;
    // echo "<hr>";

    // Access product data
    // $store = Mage::app()->getStore();
    // $sku = $_resource->getAttributeRawValue($entityId, 'sku', $store);

    
// }
