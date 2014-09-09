<?php
/**
 * Created by PhpStorm.
 * User: juhni
 * Date: 8/26/14
 * Time: 2:11 PM
 */
defined('C5_EXECUTE') or die(_("Access Denied."));
/* @var $uh ConcreteUrlsHelper */
$uh = Loader::helper('concrete/urls');
/* @var $form FormHelper */
$form = Loader::helper('form');
/* @var $ih ConcreteInterfaceHelper */
$ih = Loader::helper('concrete/interface');
/** @var $dh ConcreteDashboardHelper */
$dh = Loader::helper('concrete/dashboard');

echo  $dh->getDashboardPaneHeaderWrapper(t('Db.xml updater'));

?>

<form action="<?php echo $this->url("/dashboard/codaone_db_updater/settings/save/") ?>" method="post">

    <div class="input">
        <label><b><?php echo t("Tables to add to the db.xml")?></b></label>
        <?php
            echo $form->selectMultiple("tables", $tables, $selectedTables, array("style" => "min-width:250px;"));
        ?>
        <script type="text/javascript">
            $(function(){
                $("select[id=tables]").select2();
            });
        </script>
    </div><br />

<!--    <div class="input">-->
<!--        <label><b>--><?php //echo t("Or table prefix to add to the db.xml")?><!--</b></label>-->
<!--        --><?php
//        echo $form->text("prefix", $prefix, $selectedPrefix, array("style" => "min-width:250px;"));
//        ?>
<!--    </div>-->
	<div class="input">
		<?php print $ih->submit(t('Save and update'), 'ccm-user-form', 'left', 'primary'); ?>
		<?php //print $ih->button(t('Update db.xml'), $this->url('/dashboard/codaone_db_updater/settings/update/'), 'right') ?>
	</div>
</form>
