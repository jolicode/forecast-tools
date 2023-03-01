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

use App\Entity\ForecastAccount;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class ForecastAccountCrudController extends AbstractCrudController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return ForecastAccount::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Forecast organizations')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('name'),
            IntegerField::new('forecastId')
                ->formatValue(fn ($value): string => sprintf('<a href="https://forecastapp.com/%s/schedule/team">%s</a>', $value, $value))->onlyOnDetail(),
            AssociationField::new('harvestAccount'),
            AssociationField::new('userForecastAccounts', 'Users')->onlyOnIndex(),
            AssociationField::new('publicForecasts', 'Public forecasts')->onlyOnIndex(),
            AssociationField::new('invoicingProcesses', 'Invoicing processes')->onlyOnIndex(),
            AssociationField::new('forecastAccountSlackTeams', 'Slack teams')->onlyOnIndex(),
            CollectionField::new('userForecastAccounts', 'Users')
                ->onlyOnDetail()
                ->addCssClass('field-boolean')
                ->formatValue(function ($value, $entity): string {
                    $formattedValue = [];
                    $users = $entity->getUserForecastAccounts()->toArray();
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
            DateTimeField::new('createdAt')->onlyOnIndex(),
            BooleanField::new('allowNonAdmins', 'Allow non admins to create public forecasts')->hideOnIndex(),
            TextField::new('accessToken')->onlyOnDetail(),
            TextField::new('refreshToken')->onlyOnDetail(),
            BooleanField::new('refreshToken', 'Refreshable')
                ->formatValue(fn ($value): bool => null !== $value)->setCustomOptions([
                    'renderAsSwitch' => false,
                ]),
            IntegerField::new('expires', 'Token expiration')
                ->formatValue(function ($value): string {
                    $interval = (new \DateTime())->setTimestamp($value)->diff(new \DateTime());
                    $mapping = [
                        ['y', 'y'],
                        ['m', 'm'],
                        ['d', 'd'],
                        ['h', 'h'],
                        ['i', 'min'],
                        ['s', 'sec'],
                    ];
                    $tmp = [];

                    foreach ($mapping as [$format, $toDisplay]) {
                        if ($interval->format('%' . $format) > 0) {
                            $tmp[] = sprintf('%s%s', $interval->format("%$format"), $toDisplay);
                        }
                    }

                    if (0 === $interval->invert) {
                        $tmp[] = 'ago';
                    }

                    return implode(' ', $tmp);
                }),
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
