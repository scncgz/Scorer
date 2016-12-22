<?php

trait basic
{
    public function show($page)
    {
        $this->smarty->display($page.'.tpl');
    }
}