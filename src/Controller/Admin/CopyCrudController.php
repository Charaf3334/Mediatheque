<?php

namespace App\Controller\Admin;

use App\Entity\Copy;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CopyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Copy::class;
    }
}
