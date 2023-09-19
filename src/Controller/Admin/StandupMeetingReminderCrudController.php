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

use App\Entity\StandupMeetingReminder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class StandupMeetingReminderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return StandupMeetingReminder::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('slackTeam')->onlyOnIndex(),
            TextField::new('channelId')->onlyOnIndex(),
            ArrayField::new('forecastClients')->onlyOnIndex(),
            ArrayField::new('forecastProjects')->onlyOnIndex(),
            TextField::new('updatedBy')->onlyOnIndex(),
            TextField::new('time'),
            BooleanField::new('isEnabled'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
        ;
    }
}
