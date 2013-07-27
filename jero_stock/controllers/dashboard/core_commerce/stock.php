<?php

defined('C5_EXECUTE') or die("Access Denied.");

class DashboardCoreCommerceStockController extends Controller {

    function __construct() {
	Loader::model('stock', 'jero_stock');
	$this->stock = new JeRoCoreCommerceStockCSV();
    }

    function download() {
	$this->stock->download();
	die();
    }

    function upload() {
	$this->stock->upload();
	if ($this->stock->error->has()) {
	    $this->set('error', $this->stock->error);
	} else {
	    $this->stock->import();
	    $this->set('success', t('Products updated'));
	}
    }

    function getAttributes() {
	$db = Loader::db();
	$form = Loader::helper('form');
	$aql = "SELECT akHandle,akName FROM AttributeKeys
	    INNER JOIN AttributeKeyCategories ON AttributeKeys.akCategoryID = AttributeKeyCategories.akCategoryID
	    WHERE akCategoryHandle = 'core_commerce_product'";
	$a = $db->getAll($aql);
	foreach ($a as $v) {
	    echo '<li><label>' .
	    $form->checkbox('Attribute_' . $v['akHandle'], 1) .
	    ' <span>' . htmlspecialchars($v['akName']) . '</span></label></li>';
	}
    }

    function getProductSets() {
	$form = Loader::helper('form');
	Loader::model('product/set', 'core_commerce');
	$list = CoreCommerceProductSet::getList();
	foreach($list as $set){
	    echo '<li><label>' .
	    $form->checkbox('Set_' . $set->prsID, 1) .
	    ' <span>' . htmlspecialchars($set->prsName) . '</span></label></li>';
	}
    }
}