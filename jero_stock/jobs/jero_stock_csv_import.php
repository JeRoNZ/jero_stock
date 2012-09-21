<?php 

/**
 *
 * Responsible for loading the indexed search class and initiating the reindex command.
 * @package Utilities
 */
defined('C5_EXECUTE') or die("Access Denied.");

class JeroStockCsvImport extends Job {

    public function getJobName() {
	return t('Import eCommerce CSV Data');
    }

    public function getJobDescription() {
	return t("Import files/incoming/stock.csv into ecommerce");
    }

    function run() {
	Loader::model('stock', 'jero_stock');
	if (!is_readable(JeRoCoreCommerceStockCSV::JOBFILE))
	    return t("stock.csv not found or not readable");

	$stock = new JeRoCoreCommerceStockCSV();
	$stock->uploadJob();
	if ($stock->errors_found) {
	    $err = $stock->error->getList();
	    return $err[0];
	} else {
	    try {
		$stock->import();
	    } catch (exception $e) {
		return $e->getMessage();
	    }
	}
	return t("Update complete");
    }

}

?>