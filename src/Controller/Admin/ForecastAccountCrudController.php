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
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
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
            IntegerField::new('forecastId'),
            AssociationField::new('userForecastAccounts', 'Users')->onlyOnIndex(),
            AssociationField::new('publicForecasts', 'Public forecasts')->onlyOnIndex(),
            AssociationField::new('invoicingProcesses', 'Invoicing processes')->onlyOnIndex(),
            AssociationField::new('forecastAccountSlackTeams', 'Slack teams')->onlyOnIndex(),
            DateTimeField::new('createdAt')->onlyOnIndex(),
            BooleanField::new('allowNonAdmins', 'Allow non admins to create public forecasts')->onlyOnForms(),
            TextField::new('accessToken')->onlyOnForms(),
            TextField::new('refreshToken')->onlyOnForms(),
            IntegerField::new('expires', 'token expiration')
            ->onlyOnIndex()
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

                    return implode(' ', $tmp);
                }),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT);
    }
}
