<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Entity\ForecastReminder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ForecastReminderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ForecastReminder::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('forecastAccount')->onlyOnIndex(),
            TextField::new('CronExpression')->onlyOnIndex(),
            AssociationField::new('updatedBy')->onlyOnIndex(),
            DateTimeField::new('updatedAt')->onlyOnIndex(),
            AssociationField::new('clientOverrides')->onlyOnIndex(),
            AssociationField::new('projectOverrides')->onlyOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT);
    }
}
