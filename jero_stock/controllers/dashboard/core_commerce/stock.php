<?php
defined('C5_EXECUTE') or die("Access Denied.");

class DashboardCoreCommerceStockController extends Controller {
	function __construct(){
	    Loader::model('stock', 'jero_stock');
	    $this->stock = new JeRoCoreCommerceStockCSV();
	}
	function download(){
		$this->stock->download();
		die();
	}
	function upload(){
	    $this->stock->upload();
	    if ($this->stock->error->has()) {
		$this->set('error',$this->stock->error);
	    } else {
		$this->stock->import();
		$this->set('success',t('Products updated'));
	    }
	}
}