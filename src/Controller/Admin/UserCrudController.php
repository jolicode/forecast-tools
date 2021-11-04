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

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name')
                ->setFormTypeOptions(['disabled' => 'disabled']),
            CollectionField::new('userForecastAccounts', 'Forecast')->onlyOnDetail(),
            CollectionField::new('userHarvestAccounts', 'Harvest')->onlyOnDetail(),
            IntegerField::new('forecastId')->hideOnForm(),
            EmailField::new('email')
                ->setFormTypeOptions(['disabled' => 'disabled']),
            DateTimeField::new('createdAt')->hideOnForm(),
            IntegerField::new('expires', 'token expiration')
                ->hideOnForm()
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
            BooleanField::new('isSuperAdmin'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW)
        ;
    }
}
