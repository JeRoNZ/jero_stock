<?php
defined('C5_EXECUTE') or die("Access Denied.");

Loader::model('stock', 'jero_stock');
$ih = Loader::helper('concrete/interface');
$settings = Page::getByPath('/dashboard/core_commerce/stock', 'ACTIVE');
$form = Loader::helper('form');

echo Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t('Mass Update'), false, false, false, array(), $settings);
?>
<div class="ccm-pane-body">
    <h3><?php echo t('Overview') ?></h3>
    <p><?php echo t('<p>Click the download button to generate a comma separated values (CSV) file.') ?></p>
    <p><?php echo t('Use the Upload form to upload the file after you have edited it using your favourite spreadsheet software. Columns in the file may be in any order, but must as a minimum contain the &quot;ID&quot; column and one other data column. The &quot;Name&quot; column is <strong>not</strong> updated and is included for convenience only. You may have other columns in your CSV file, they are silently ignored. ID is a value that cannot be changed.') ?></p>
    <p><?php echo t('When using tiered prices, tier ranges are input in the format <em>1,10</em> and must be accompanied by a correspnding price column, e.g. <em>Tier1</em> and <em>Price1</em>. Up to ten tiers are available. If you require more than ten tiers, <strong>beware that your data will be lost beyond the 10th tier.</strong>') ?></p>
    <p><?php echo t('The Upload form allows you to decide which fields you wish to update. You must choose at least one field. You must ensure that if you choose a field to update, that the corresponding field exists in your CSV file.') ?></p>
    <p><?php echo t('The data is validated, and any errors are reported if found. No updates are done if there are any errors.') ?></p>
    <p><?php echo t('The <em>Status,Tiered</em> and <em> Login</em> fields must contain either a <em>1</em> (enable) or <em>0</em> (disable). Any other value will cause an error to be generated.') ?></p>
    <p><strong><?php echo t('As with all mass update programs, the possibility of disaster is every present, and therefore a ') ?><a href="<?php echo $this->url('/dashboard/system/backup_restore/backup/') ?>"><?php echo t('judicious backup is advised.') ?></a></strong></p>
    <h3><span><?php echo t('Download Data') ?></span></h3>
    <div class="ccm-buttons">
	<div style="float:left;margin-right:10px">
	    <form method="post" enctype="multipart/form-data" action="<?php echo $this->action('download') ?>">
		<?php echo $ih->submit(t('Download'), 'function', 'left', 'primary', array('style' => 'outline:none')) ?>
	    </form>
	</div>
	<div>
	    <?php echo t('The CSV download may be automated from outside Concrete5 by using this URL:') ?><br/><b><?php $auth = md5(PASSWORD_SALT . ': jero-stock');
	    echo BASE_URL . $this->url('/jero-stock?auth=' . $auth) ?></b><br/><br/>
	</div>
    </div>

    <h3><span><?php echo t('Upload Data') ?></span></h3>
    <form id="UploadForm" method="post" enctype="multipart/form-data" action="<?php echo $this->action('upload') ?>">
	<label for="stockfile"><?php echo t('File name') ?> </label><?php echo $form->file('stockfile') ?>

	<div class="clearfix">
	    <div style="float:left;width:33%">
		<h4><?php echo t('Fields to update') ?></h4>
		 <ul class="inputs-list inputs-list-options">
		    <li><label><?php echo $form->checkbox('prQuantity', 1) ?> <span><?php echo t('Quantity') ?></span></label></li>
		    <li><label><?php echo $form->checkbox('prStatus', 1) ?> <span><?php echo t('Status') ?></span></label></li>
		    <li><label><?php echo $form->checkbox('prPrice', 1) ?> <span><?php echo t('Price') ?></span></label></li>
		    <li><label><?php echo $form->checkbox('prSpecialPrice', 1) ?> <span><?php echo t('Special') ?></span></label></li>
		    <li><label><?php echo $form->checkbox('prUseTieredPricing', 1) ?> <span><?php echo t('Use tiered pricing') ?></span></label></li>
		    <li><label><?php echo $form->checkbox('prRequiresLoginToPurchase', 1) ?> <span><?php echo t('Login to purchase') ?></span></label></li>
		    <li><label><?php echo $form->checkbox('prMinimumPurchaseQuantity', 1) ?> <span><?php echo t('Minimum order quantity') ?></span></label></li>
		    <li><label><?php echo $form->checkbox('prWeight', 1) ?> <span><?php echo t('Weight') ?></span></label></li>
		    <li><label><?php echo $form->checkbox('prWeightUnits', 1) ?> <span><?php echo t('Weight units') ?></span></label></li>
		    <li><label><?php echo $form->checkbox('prRequiresTax', 1) ?> <span><?php echo t('Requires Tax') ?></span></label></li>
		    <li><label><?php echo $form->checkbox('TP', 1) ?> <span><?php echo t('Tiered prices') ?></span></label></li>
		</ul>
	    </div>


	    <div style="float:left;width:33%">
		<h4><?php echo t('Attributes to update') ?></h4>
		 <ul class="inputs-list inputs-list-attributes">
			 <?php $this->controller->getAttributes(); ?>
		</ul>
	    </div>
	     <div style="float:left">
		<h4><?php echo t('Sets to update') ?></h4>
		 <ul class="inputs-list inputs-list-sets">
			 <?php $this->controller->getProductSets(); ?>
		</ul>
	    </div>
	</div>


	<?php echo $ih->submit(t('Upload'), 'function', 'left', 'primary', array('style' => 'outline:none')) ?>
	<?php echo $ih->submit(t('All'), 'All', 'left', NULL, array('style' => 'outline:none')) ?>
	<?php echo $ih->submit(t('Attributes'), 'Attributes', 'left', NULL, array('style' => 'outline:none')) ?>
	<?php echo $ih->submit(t('Sets'), 'Sets', 'left', NULL, array('style' => 'outline:none')) ?>
	<?php echo $ih->submit(t('None'), 'None', 'left', NULL, array('style' => 'outline:none')) ?>
    </form>
</div>
<div class="ccm-pane-footer"></div>
<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneFooterWrapper(false); ?>

<script type="text/javascript">
    $(document).ready(function(){
	$('#ccm-submit-All').click(function(e){
	    e.preventDefault();
	    $('#UploadForm ul.inputs-list-options input[type=checkbox]').attr('checked',true);
	});
	$('#ccm-submit-Attributes').click(function(e){
	    e.preventDefault();
	    $('#UploadForm ul.inputs-list-attributes input[type=checkbox]').attr('checked',true);
	});
	$('#ccm-submit-Sets').click(function(e){
	    e.preventDefault();
	    $('#UploadForm ul.inputs-list-sets input[type=checkbox]').attr('checked',true);
	});
	$('#ccm-submit-None').click(function(e){
	    e.preventDefault();
	    $('#UploadForm input[type=checkbox]').attr('checked',false);
	});
	$('#UploadForm').submit(function(e){
	    if ($('#UploadForm input:checkbox:checked').length==0){
		e.preventDefault();
		alert('<?php echo t('You must select at least one field to update') ?>');
		return false;
	    }
	});
    });
</script>