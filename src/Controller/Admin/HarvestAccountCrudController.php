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

use App\Entity\HarvestAccount;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class HarvestAccountCrudController extends AbstractCrudController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return HarvestAccount::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Harvest organizations')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id'),
            TextField::new('name'),
            IntegerField::new('harvestId')->hideOnIndex(),
            UrlField::new('baseUri'),
            AssociationField::new('forecastAccount'),
            AssociationField::new('userHarvestAccounts', 'Users')->onlyOnIndex(),
            CollectionField::new('userHarvestAccounts', 'Users')
                ->onlyOnDetail()
                ->addCssClass('field-boolean')
                ->formatValue(function ($value, $entity): string {
                    $formattedValue = [];
                    $users = $entity->getUserHarvestAccounts()->toArray();
                    usort($users, fn ($a, $b) => strcmp((string) $a->getUser()->getName(), (string) $b->getUser()->getName()));

                    foreach ($users as $user) {
                        $url = $this->adminUrlGenerator
                            ->unsetAll()
                            ->setController(UserCrudController::class)
                            ->setAction(Action::DETAIL)
                            ->setEntityId($user->getUser()->getId())
                            ->generateUrl();
                        $formattedValue[] = sprintf(
                            '<a href="%s">%s</a>%s%s',
                            $url,
                            $user->getUser()->getName(),
                            $user->getIsAdmin() ? '&nbsp;<span class="badge badge-boolean-true">admin</span>' : '',
                            !$user->getIsEnabled() ? '&nbsp;<span class="badge badge-boolean-false">disabled</span>' : '',
                        );
                    }

                    return implode('<br />', $formattedValue);
                }),
            AssociationField::new('timesheetReminderSlackTeam', 'Send timesheet reminders to')
                ->setTemplatePath('admin/fields/timesheet_reminder_slack_team.html.twig')
                ->hideOnIndex(),
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
