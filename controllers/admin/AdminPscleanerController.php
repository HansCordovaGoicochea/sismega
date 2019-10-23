<?php


class AdminPscleanerControllerCore extends AdminController
{

    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        parent::__construct();

        $rtl_link = 'index.php?tab=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&configure=pscleaner';

        Tools::redirectAdmin($rtl_link);

    }

}
