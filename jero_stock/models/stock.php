<?php

defined('C5_EXECUTE') or die("Access Denied.");
Loader::model('product/model', 'core_commerce');
Loader::model('attribute/categories/core_commerce_product', 'core_commerce');

class JeRoCoreCommerceStockCSV {

    public $error;
    public $errors_found = false;
    private $file = 'stockfile';
    private $headers;
    private $update;
    private $updateAttributes;
    private $line;
    private $decode;
    private $runAsJob = false;
    const JOBFILE = 'files/incoming/stock.csv';
    // These are hard coded in ecommerce, so we do the same
    private $unitsArray = array('g', 'kg', 'oz', 'lb');

    function __construct() {
	$this->error = Loader::helper('validation/error');
	ini_set('auto_detect_line_endings', 1); // MAC attack
	$this->decode = array(
	    'Qty' => 'prQuantity',
	    'Status' => 'prStatus',
	    'Price' => 'prPrice',
	    'Special' => 'prSpecialPrice',
	    'Tiered' => 'prUseTieredPricing',
	    'Login' => 'prRequiresLoginToPurchase',
	    'Min' => 'prMinimumPurchaseQuantity',
	    'Weight' => 'prWeight',
	    'Units' => 'prWeightUnits');
	$this->update = array();
	$this->updateAttributes= array();
    }

    public function import() {
	if ($this->runAsJob) {
	    if (!$handle = fopen(JeRoCoreCommerceStockCSV::JOBFILE, 'r')) {
		throw new exception(t("Error opening ") . JeRoCoreCommerceStockCSV::JOBFILE);
	    }
	} else
	    $handle = fopen($_FILES[$this->file]['tmp_name'], 'r');

	fgetcsv($handle);  // Skip the header row
	while ($fields = fgetcsv($handle)) {
	    $product = new CoreCommerceProduct();
	    $product->load($fields[$this->headers['ID']]);
// skip invalid products
	    if (!$product->getProductID())
		continue;
// setup current values
	    $data = $this->set($product);

// Override with imported ones
	    if (is_array($this->update)) {
		foreach ($this->update as $value) {
		    switch ($value) {
			case 'TP':
			    $data['prTieredPricing'] = $this->import_tiers($fields);
			    break;
			default:
			    $data[$value] = $fields[$this->headers[$value]];
			    break;
		    }
		}
	    }

// Update attributes TODO make this luser definable
	    $this->importAttributes($fields, $product);

// Update the product
	    $product->update($data);
	}
	fclose($handle);
    }

    private function import_tiers($fields) {
	for ($i = 1; $i <= 10; $i++) {
	    $t = 'Tier' . $i;
	    $p = 'Price' . $i;
	    if (array_key_exists($t, $this->headers)) {
		$bits = split(',', $fields[$this->headers[$t]]);
		if (count($bits) == 2) {
		    $data["tierStart"][] = $bits[0];
		    $data["tierEnd"][] = $bits[1];
		    $data["tierPrice"][] = $fields[$this->headers[$p]];
		}
	    }
	}
	return $data;
    }

    private function importAttributes($fields, $product) {
	foreach ($this->headers as $v => $k) {
	    if (substr($v, 0, 10) == 'Attribute_') {
		$ah = substr($v, 10);

// Only update attributes requested by user
		if (!in_array($ah, $this->updateAttributes))
		    continue;

// Does the attribute handle actually exist?
		$ak = CoreCommerceProductAttributeKey::getByHandle($ah);
		if (!$ak)
		    continue;

		switch ($ak->atHandle) :
		    case "text":
		    case "number":
		    case "rating":
		    case "boolean":
		    case "textarea":
		    case "date_time":
			$av = $fields[$k];
			break;

		    case "select":
// unmap | to newline for multiple select values
			$av = explode("|", $fields[$k]); 
			if (count($av) == 1)
			    $av = $av[0];
			break;

		    case "image_file":
			if (ctype_digit($fields[$k])) {
			    $av = File::getByID($fields[$k]);
			    if ($av->error)
				continue;
			} else {
			    continue;
			}
			break;

		    default:
			continue;
			break;
		endswitch;

		$product->setAttribute($ak, $av);
	    }
	}
    }

    private function set($product) {
// setup the product with its existing values
	$data['prName'] = $product->getProductName();
	$data['prDescription'] = $product->getProductDescription();
	$data['prStatus'] = $product->getProductStatus();
	$data['prPrice'] = $product->getProductPrice();
	$data['prSpecialPrice'] = $product->getProductSpecialPrice(false);
	if ($data['prSpecialPrice'] == 0) {
	    $data['prSpecialPrice'] = '';
	}
	$data['prQuantity'] = $product->getProductQuantity();
	$data['prMinimumPurchaseQuantity'] = $product->getMinimumPurchaseQuantity();
	$data['prQuantityUnlimited'] = $product->productHasUnlimitedQuantity();
	$data['prPhysicalGood'] = $product->productIsPhysicalGood();
	$data['prRequiresShipping'] = $product->productRequiresShipping();

	$data['prWeight'] = $product->getProductWeight();
	$data['prWeightUnits'] = $product->getProductWeightUnits();
	$data['prDimL'] = $product->getProductDimensionLength();
	$data['prDimW'] = $product->getProductDimensionWidth();
	$data['prDimH'] = $product->getProductDimensionHeight();
	$data['prDimUnits'] = $product->getProductDimensionUnits();

	$data['productID'] = $product->getProductID();
	$data['prRequiresTax'] = $product->productRequiresSalesTax();
	$data['prShippingModifier'] = $product->getProductShippingModifier();
	$data['gIDs'] = $product->getProductPurchaseGroupIDArray();
	$data['cID'] = $product->getProductCollectionID();
	$data['prRequiresLoginToPurchase'] = $product->productRequiresLoginToPurchase();
	$data['prUseTieredPricing'] = $product->productUsesTieredPricing();


// Don't overwrite with the default value
	$data['prQuantityAllowNegative'] = $product->prQuantityAllowNegative;

	if ($data['prUseTieredPricing']) {
	    Loader::model('product/tiered_price', 'core_commerce');
	    $tiers = CoreCommerceProductTieredPrice::getTiers($product);
	    if (is_array($tiers)) {
		$data['prTieredPricing'] = array();
		foreach ($tiers as $tier) {
		    $data['prTieredPricing']['tierStart'][] = $tier->getTierStart();
		    $data['prTieredPricing']['tierEnd'][] = $tier->getTierEnd();
		    $data['prTieredPricing']['tierPrice'][] = $tier->getTierPrice();
		}
	    }
	}
	return $data;
    }

    public function uploadJob() {
// Perform the upload
	$this->runAsJob = true;
	$suffix = t(' column not found in source file');
	$done = false;
	$handle = fopen(JeRoCoreCommerceStockCSV::JOBFILE, 'r');
	while ($fields = fgetcsv($handle)) {
	    $this->line++;
	    if (!$done) {
		$done = true;
		$this->headers = array_flip($fields);
		if (!array_key_exists('ID', $this->headers)) {
		    $this->error->add('ID' . $suffix);
		    $this->errors_found = true;
		    return false;
		}
		foreach ($this->decode as $key => $value) {
		    if (array_key_exists($key, $this->headers)) {
			$this->update[] = $value;
		    }
		}
		// Not checking each and every tier pricing, because they can be blank.
		// Check first one only - should ensure the user has RTFM.
		if (array_key_exists('Tier1', $this->headers)) {
		    if (!array_key_exists('Price1', $this->headers)) {
			$this->error->add('Price1' . $suffix);
			$this->errors_found = true;
			return false;
		    }
		    $this->update[] = 'TP';
		}
		if (count($this->update) == 0) {
		    throw new Exception(t('No columns to update'));
		}
		continue;
	    }
	    foreach ($this->headers as $key => $value) {
		$good_row = $this->check_data($key, $fields[$value]);
		if (!$good_row)
		    $this->errors_found = true;
	    }
	}
	fclose($handle);
	if (!$done) {
	    throw new Exception('File is empty');
	}
	// Rename the header keys to match DB fields, not the user friendly ones
	foreach ($this->decode as $key => $value) {
	    $this->headers[$value] = $this->headers[$key];
	    unset($this->headers[$key]);
	}
    }

    function upload() {
// Perform the upload
	$suffix = t(' column not found in source file');
	if (is_uploaded_file($_FILES[$this->file]['tmp_name'])) {
	    $done = false;
	    $handle = fopen($_FILES[$this->file]['tmp_name'], 'r');
	    while ($fields = fgetcsv($handle)) {
		$this->line++;
		if (!$done) {
		    $done = true;
		    $this->headers = array_flip($fields);
		    if (!array_key_exists('ID', $this->headers)) {
			$this->error->add('ID' . $suffix);
			$this->errors_found = true;
			return false;
		    }
		    foreach ($this->decode as $key => $value) {
			if (array_key_exists($value, $_POST)) {
			    if (!array_key_exists($key, $this->headers)) {
				$this->error->add($key . $suffix);
				$this->errors_found = true;
				return false;
			    }
			    $this->update[] = $value;
			}
		    }
		    // Not checking each and every tier pricing, because they can be blank.
		    // Check first one only - should ensure the user has RTFM.
		    if (array_key_exists('TP', $_POST)) {
			if (!array_key_exists('Tier1', $this->headers)) {
			    $this->error->add('Tier1' . $suffix);
			    $this->errors_found = true;
			    return false;
			}
			if (!array_key_exists('Price1', $this->headers)) {
			    $this->error->add('Price1' . $suffix);
			    $this->errors_found = true;
			    return false;
			}
			$this->update[] = 'TP';
		    }

		    foreach ($_POST as $k => $v) {
			if (substr($k, 0, 10) == 'Attribute_')
			    $this->updateAttributes[] = substr($k, 10);
		    }

		    continue;
		}
		foreach ($this->headers as $key => $value) {
		    $good_row = $this->check_data($key, $fields[$value]);
		    if (!$good_row)
			$this->errors_found = true;
		}
	    }
	    fclose($handle);
	    // Rename the header keys to match DB fields, not the user friendly ones
	    foreach ($this->decode as $key => $value) {
		$this->headers[$value] = $this->headers[$key];
		unset($this->headers[$key]);
	    }
	} else {
	    $this->error->add(t('No file specified'));
	    $this->errors_found = true;
	}
    }

    private function check_data($key, $value) {
	// Check for nonsense values in the data
	switch ($key) {
	    case 'ID':
	    case 'Name':
		return true;
	    case 'prQuantity':
	    case 'Min':
		if (ctype_digit($value))
		    return true;
		break;
	    case 'Status':
	    case 'Tiered':
	    case 'Login':
		if ($value == '1' or $value == '0')
		    return true;
		break;
	    case 'Units':
		if (in_array($value, $this->unitsArray))
		    return true;
		break;
	    case 'Weight':
	    case 'Price':
	    case 'Special':
	    case 'Price1':
	    case 'Price2':
	    case 'Price3':
	    case 'Price4':
	    case 'Price5':
	    case 'Price6':
	    case 'Price7':
	    case 'Price8':
	    case 'Price9':
	    case 'Price10':
		if (preg_match('/^[0-9]+(\.[0-9]{1,4}){0,1}$/', $value) === 1)
		    return true;
		if ($value == '')
		    return true;
		break;

	    case 'Tier1':
	    case 'Tier2':
	    case 'Tier3':
	    case 'Tier4':
	    case 'Tier5':
	    case 'Tier6':
	    case 'Tier7':
	    case 'Tier8':
	    case 'Tier9':
	    case 'Tier10':
		if (preg_match('/^[0-9]+\,[0-9]+$/', $value) === 1)
		    return true;
		if ($value == '')
		    return true;
		break;

	    default: // any unrecognised fields will be ignored
		return true;
		break;
	}
	$this->error->add(t('Line: ') . $this->line . t(': Invalid value for ') . $key . ': ' . $value);

	return false;
    }

    private function quote($string) {
	return '"' . preg_replace("/\"/", '""', $string) . '"';
    }

    public function download() {
	header("Content-type: application/force-download");
	header('Content-disposition: attachment; filename="stock.csv"');
	$header = 'Name,ID,Qty,Status,Price,Special,Tiered,Login,Min,Weight,Units,Tier1,Price1,Tier2,Price2,Tier3,Price3,Tier4,Price4,Tier5,Price5,Tier6,Price6,Tier7,Price7,Tier8,Price8,Tier9,Price9,Tier10,Price10';
	$db = Loader::db();
	$sql = 'select productID,prName,prQuantity,prStatus,prPrice,prSpecialPrice,prUseTieredPricing,prRequiresLoginToPurchase,prMinimumPurchaseQuantity,prWeight,prWeightUnits from CoreCommerceProducts';
	$tql = 'select * from CoreCommerceProductTieredPricing where productID=? order by productTieredPricingID';

	$aql = "select akID,akHandle from AttributeKeys inner join AttributeKeyCategories on AttributeKeys.akCategoryID = AttributeKeyCategories.akCategoryID where  akCategoryHandle = 'core_commerce_product'";
	$a = $db->getAll($aql);
	foreach ($a as $v) {
	    $attributeHandles[$v['akID']] = $v['akHandle'];
	    $header.=',Attribute_' . $v['akHandle'];
	}

	echo $header . PHP_EOL;

	$data = $db->getAll($sql);
	foreach ($data as $row) {
	    if ($row['prUseTieredPricing'] == 1) {
		$row['prPrice'] = '';
	    }
	    echo $this->quote($row['prName']) . ',' .
	    $row['productID'] . ',' .
	    $row['prQuantity'] . ',' .
	    $row['prStatus'] . ',' .
	    $row['prPrice'] . ',' .
	    $row['prSpecialPrice'] . ',' .
	    $row['prUseTieredPricing'] . ',' .
	    $row['prRequiresLoginToPurchase'] . ',' .
	    $row['prMinimumPurchaseQuantity'] . ',' .
	    $row['prWeight'] . ',' .
	    $row['prWeightUnits'];

	    if ($row['prUseTieredPricing'] == 1) {
		$tiers = $db->getAll($tql, array($row['productID']));
		foreach ($tiers as $v) {
		    echo ',' . $this->quote($v['tierStart'] . ',' . $v['tierEnd']) . ',' . $v['tierPrice'];
		}
	    }
	    else
		echo ",,,,,,,,,,,,,,,,,,,,";
	    /*
	      Loader::model('attribute/categories/core_commerce_product','core_commerce');
	      return CoreCommerceProductAttributeKey::getAttributes($productID);
	      Above won't return a value if a new attribute has been added and not set for the product.
	      Thus picking out a list of defined attributes and then getting values for each one is the only way to proceed
	     */
	    $prObject = new CoreCommerceProduct();
	    $prObject->load($row['productID']);
	    echo ',';
	    foreach ($attributeHandles as $v) {
		$at = $prObject->getAttribute($v);

// may be a file attribute, so will be return an object. Output the file ID.
		if (is_object($at)) {
		    if (get_class($at) == 'File')
			$at = $at->fID;
		}
// multiple values are new line delimited, map this to |

		echo $this->quote(str_replace("\n", '|', $at)) . ',';
	    }
	    echo PHP_EOL;
	}
    }

}

?>