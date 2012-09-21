<?php  
//redirect the default page link to the first Tab

defined('C5_EXECUTE') or die(_("Access Denied."));
class DashboardCCStockController extends Controller {

	public function view() {
		$this->redirect('/dashboard/core_commerce/stock/');
	}
}
?>
