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

class ForecastAccountCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ForecastAccount::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('name'),
            IntegerField::new('forecastId')->onlyOnDetail(),
            AssociationField::new('userForecastAccounts', 'Users')->onlyOnIndex(),
            AssociationField::new('publicForecasts', 'Public forecasts')->onlyOnIndex(),
            AssociationField::new('invoicingProcesses', 'Invoicing processes')->onlyOnIndex(),
            AssociationField::new('forecastAccountSlackTeams', 'Slack teams')->onlyOnIndex(),
            CollectionField::new('userForecastAccounts', 'Users')
                ->onlyOnDetail()
                ->formatValue(function ($value, $entity) {
                    $formattedValue = [];
                    $users = $entity->getUserForecastAccounts();

                    foreach ($users as $user) {
                        $formattedValue[] = sprintf(
                            '%s%s%s',
                            $user->getUser()->getName(),
                            $user->getIsAdmin() ? ' (admin)' : '',
                            !$user->getIsEnabled() ? ' (disabled)' : ''
                        );
                    }

                    return implode(', ', $formattedValue);
                }),
            DateTimeField::new('createdAt')->onlyOnIndex(),
            BooleanField::new('allowNonAdmins', 'Allow non admins to create public forecasts')->hideOnIndex(),
            TextField::new('accessToken')->onlyOnDetail(),
            TextField::new('refreshToken')->onlyOnDetail(),
            IntegerField::new('expires', 'token expiration')
                ->formatValue(function ($value) {
                    $interval = ((new \DateTime())->setTimestamp($value))->diff(new \DateTime());
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
            ->disable(Action::NEW, Action::EDIT);
    }
}
