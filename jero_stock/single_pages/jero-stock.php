<?php  

if (array_key_exists('auth',$_GET)){
	$auth= md5(PASSWORD_SALT . ': jero-stock');
	if ($_GET['auth'] == $auth){
		Loader::model('stock','jero_stock');
		$stock = new JeRoCoreCommerceStockCSV();
		$stock->download();
		die();
	}
}
?>
<h1><?php   echo t('Invalid auth parameter')?></h1>
