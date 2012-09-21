<?php  
defined('C5_EXECUTE') or die("Access Denied.");

Loader::model('stock','jero_stock');

if ($_SERVER['REQUEST_METHOD']=='POST'){
	$stock = new JeRoCoreCommerceStockCSV();
	switch($_POST['function']){
		case 'Download':
			$stock->download();
			die();
			break;

		case 'Upload':
			$stock->upload();
			if (!$stock->errors_found){
				$stock->import();
				$stock->message(t('Products updated'));
			}
			break;

		default:
			$stock->error(t('Unknown Action'));
			break;
	}
	$stock->squeal();
}
?>
<h1><span><?php   echo t('Overview')?></span></h1>
<div class="ccm-dashboard-inner">
<?php   echo t('<p>Click the download button to generate a comma separated values (CSV) file.')?></p>

<p><?php   echo t('Use the Upload form to upload the file after you have edited it using your favourite spreadsheet software. Columns in the file may be in any order, but must as a minimum contain the &quot;ID&quot; column and one other data column. The &quot;Name&quot; column is <strong>not</strong> updated and is included for convenience only. You may have other columns in your CSV file, they are silently ignored. ID is a value that cannot be changed.')?></p>

<p><?php   echo t('When using tiered prices, tier ranges are input in the format <em>1,10</em> and must be accompanied by a correspnding price column, e.g. <em>Tier1</em> and <em>Price1</em>. Up to ten tiers are available. If you require more than ten tiers, <strong>beware that your data will be lost beyond the 10th tier.</strong>')?></p>

<p><?php   echo t('The Upload form allows you to decide which fields you wish to update. You must choose at least one field. You must ensure that if you choose a field to update, that the corresponding field exists in your CSV file.')?></p>

<p><?php   echo t('The data is validated, and any errors are reported if found. No updates are done if there are any errors.')?></p>

<p><?php   echo t('The <em>Status,Tiered</em> and <em> Login</em> fields must contain either a <em>1</em> (enable) or <em>0</em> (disable). Any other value will cause an error to be generated.')?></p>

<p><strong><?php   echo t('As with all mass update programs, the possibility of disaster is every present, and therefore a ')?><a href="<?php   echo $this->url('/dashboard/system/backup')?>"><?php   echo t('judicious backup is advised.')?></a></strong></p>


</div>
<h1><span><?php   echo t('Download Data')?></span></h1>
<div class="ccm-dashboard-inner">
	<div class="ccm-buttons">
		<div style="float:left;margin-right:10px">
<form method="post" enctype="multipart/form-data" action="<?php   echo $_SERVER['PHP_SELF']?>">
<input type="submit"  class="ccm-button-v2" name="function" value="Download" />
</form>
		</div>
		<div>
<?php   echo t('The CSV download may be automated from outside Concrete5 by using this URL:')?><br/><b><?php   $auth= md5(PASSWORD_SALT.': jero-stock');
echo BASE_URL.$this->url('/jero-stock?auth=' . $auth)?></b><br/><br/>
		</div>
	</div>
</div>

<h1><span><?php   echo t('Upload Data')?></span></h1>
<div class="ccm-dashboard-inner">
<form id="UploadForm" method="post" enctype="multipart/form-data" action="<?php   echo $_SERVER['PHP_SELF']?>">
<label for="stockfile"><?php   echo t('File name')?> </label><input id="stockfile" type="file" name="stockfile" />

<table class="entry-form" cellspacing="1" cellpadding="0" border="0">
<tbody>
<tr><td class="header" colspan="1"><?php   echo t('Fields to update')?></td></tr>

<tr><td><input type="checkbox" id="prQuantity" value="1" name="prQuantity" class="ccm-input-checkbox" /><label for="prQuantity"><?php   echo t('Quantity')?></label></td></tr>
<tr><td><input type="checkbox" id="prStatus" value="1" name="prStatus" class="ccm-input-checkbox" /><label for="prStatus"><?php   echo t('Status')?></label></td></tr>
<tr><td><input type="checkbox" id="prPrice" value="1" name="prPrice" class="ccm-input-checkbox" /><label for="prPrice"><?php   echo t('Price')?></label></td></tr>
<tr><td><input type="checkbox" id="prSpecialPrice" value="1" name="prSpecialPrice" class="ccm-input-checkbox" /><label for="prSpecialPrice"><?php   echo t('Special')?></label></td></tr>
<tr><td><input type="checkbox" id="prUseTieredPricing" value="1" name="prUseTieredPricing" class="ccm-input-checkbox" /><label for="prUseTieredPricing"><?php   echo t('Use tiered pricing')?></label></td></tr>
<tr><td><input type="checkbox" id="prRequiresLoginToPurchase" value="1" name="prRequiresLoginToPurchase" class="ccm-input-checkbox" /><label for="prRequiresLoginToPurchase"><?php   echo t('Login to purchase')?></label></td></tr>
<tr><td><input type="checkbox" id="prMinimumPurchaseQuantity" value="1" name="prMinimumPurchaseQuantity" class="ccm-input-checkbox" /><label for="prMinimumPurchaseQuantity"><?php   echo t('Minimum order quantity')?></label></td></tr>
<tr><td><input type="checkbox" id="prWeight" value="1" name="prWeight" class="ccm-input-checkbox" /><label for="prWeight"><?php   echo t('Weight')?></label></td></tr>
<tr><td><input type="checkbox" id="prWeightUnits" value="1" name="prWeightUnits" class="ccm-input-checkbox" /><label for="prWeightUnits"><?php   echo t('Weight units')?></label></td></tr>
<tr><td><input type="checkbox" id="TP" value="1" name="TP" class="ccm-input-checkbox" /><label for="TP"><?php   echo t('Tiered prices')?></label></td></tr>
</tbody>
</table>


<div class="ccm-buttons">
<input class="ccm-button-v2" type="submit" name="function" value="Upload" />

<a class="ccm-button" id="All"><span><?php   echo t('Select all')?></span></a>
<a class="ccm-button" id="None"><span><?php   echo t('Clear all')?></span></a>

</div>
</form>
</div>

<script type="text/javascript">
$(document).ready(function(){
	$('#All').click(function(e){
		e.preventDefault();
		$('#UploadForm input[type=checkbox]').attr('checked',true);
	});
	$('#None').click(function(e){
		e.preventDefault();
		$('#UploadForm input[type=checkbox]').attr('checked',false);
	});
	$('#UploadForm').submit(function(e){
		if ($('#UploadForm input:checkbox:checked').length==0){
			e.preventDefault();
			alert('<?php   echo t('You must select at least one field to update')?>');
			return false;
		}
	});
})
</script>
