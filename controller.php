<?php
/**
 * Created by PhpStorm.
 * User: juhni
 * Date: 8/26/14
 * Time: 2:08 PM
 */

class CodaoneDbUpdaterPackage extends Package {

    protected $pkgHandle = 'codaone_db_updater';
    protected $appVersionRequired = '5.3.3.1';
    protected $pkgVersion = '0.0.3';
    protected $singlePages = array(
        "/dashboard/codaone_db_updater" 			=> "Db updater",
        "/dashboard/codaone_db_updater/settings" 	=> "Settings",
    );

    public function getPackageDescription() {
        return t('Db.xml files updater for Concrete5 by Codaone');
    }

    public function getPackageName(){
        return t('Db updater');
    }

    public function install() {
        $pkg = parent::install();

        Loader::model('single_page');
        $pkg = Package::getByHandle($this->pkgHandle);
        foreach($this->singlePages as $path => $name) {
            if(Page::getByPath($path)->getCollectionID() <= 0) {
                $page = SinglePage::add($path, $pkg);
                $page->update(array("cName" => $name));
            }
        }

        $c1 = Page::getByPath('/dashboard/codaone_db_updater');
        $c1->update(array('cDescription'=>$this->getPackageDescription()));
    }

    public function uninstall() {
        parent::uninstall();
    }

}