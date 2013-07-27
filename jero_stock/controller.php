<?php 

defined('C5_EXECUTE') or die(_("Access Denied."));

class JeroStockPackage extends Package {

    protected $pkgHandle = 'jero_stock';
    protected $appVersionRequired = '5.6';
    protected $pkgVersion = '2.2';
    private $jobName = 'jero_stock_csv_import';

    public function getPackageDescription() {
        return t("CSV Import/Export eCommerce Stock Levels and Prices");
    }

    public function getPackageName() {
        return t("eCommerce CSV Mass Update");
    }

    public function install() {
        $installed = Package::getInstalledHandles();
        if (in_array('core_commerce', $installed)) {
            $ccpkg = Package::getByHandle('core_commerce');
            if (version_compare($ccpkg->getPackageVersion(), '2.0.0', '<')) {
                throw new Exception(t('You must upgrade the eCommerce add-on to version 2.0.0 or higher.'));
            }
        } else {
            throw new Exception(t('You must install eCommerce before installing this add-on.'));
        }

        $pkg = parent::install();
        Loader::model('single_page');
        $page = SinglePage::add('dashboard/core_commerce/stock', $pkg);
	$page->setAttribute('icon_dashboard', 'icon-wrench');

        $chk = SinglePage::add('/jero-stock', $pkg);
        $chk->setAttribute('exclude_nav', 1);
        $chk->setAttribute('exclude_page_list', 1);
        $chk->setAttribute('exclude_sitemapxml', 1);
        $chk->setAttribute('exclude_search_index', 1);
        Loader::model('job');
        Job::installByPackage($this->jobName, $pkg);
    }

    public function upgrade() {
        parent::upgrade();
        $pkg = Package::getByHandle($this->pkgHandle);
        Loader::model('job');
        if (! Job::getByHandle($this->jobName))
            Job::installByPackage($this->jobName, $pkg);

	$page = SinglePage::getByPath('/dashboard/core_commerce/stock');
	if (!$page->isError())
	    $page->setAttribute('icon_dashboard', 'icon-wrench');
    }

    public function uninstall() {
        parent::uninstall();
    }

}

?>
