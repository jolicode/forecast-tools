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
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                AssociationField::new('forecastAccount'),
                TextField::new('CronExpression'),
                AssociationField::new('updatedBy'),
                DateTimeField::new('updatedAt'),
                DateTimeField::new('lastTimeSentAt'),
                AssociationField::new('clientOverrides')->setTemplatePath('admin/foreceast-reminder/client-overrides.html.twig'),
                AssociationField::new('projectOverrides')->setTemplatePath('admin/foreceast-reminder/project-overrides.html.twig'),
            ];
        }

        return [
            AssociationField::new('forecastAccount'),
            TextField::new('CronExpression'),
            AssociationField::new('updatedBy'),
            DateTimeField::new('updatedAt'),
            DateTimeField::new('lastTimeSentAt'),
            AssociationField::new('clientOverrides'),
            AssociationField::new('projectOverrides'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT)
        ;
    }
}
