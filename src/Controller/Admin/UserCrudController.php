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
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class UserCrudController extends AbstractCrudController
{
    private $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

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
            CollectionField::new('userForecastAccounts', 'Forecast accounts')
                ->onlyOnDetail()
                ->addCssClass('field-boolean')
                ->formatValue(function ($value, $entity): string {
                    $formattedValue = [];
                    $accounts = $entity->getUserForecastAccounts();

                    foreach ($accounts as $account) {
                        $url = $this->adminUrlGenerator
                            ->unsetAll()
                            ->setController(ForecastAccountCrudController::class)
                            ->setAction(Action::DETAIL)
                            ->setEntityId($account->getForecastAccount()->getId())
                            ->generateUrl();
                        $formattedValue[] = sprintf(
                            '<a href="%s">%s</a>%s%s',
                            $url,
                            $account->getForecastAccount()->getName(),
                            $account->getIsAdmin() ? '&nbsp;<span class="badge badge-boolean-true">admin</span>' : '',
                            !$account->getIsEnabled() ? '&nbsp;<span class="badge badge-boolean-false">disabled</span>' : '',
                        );
                    }

                    return implode('<br />', $formattedValue);
                }),
            CollectionField::new('userHarvestAccounts', 'Harvest accounts')
                ->onlyOnDetail()
                ->addCssClass('field-boolean')
                ->formatValue(function ($value, $entity): string {
                    $formattedValue = [];
                    $accounts = $entity->getUserHarvestAccounts();

                    foreach ($accounts as $account) {
                        $url = $this->adminUrlGenerator
                            ->unsetAll()
                            ->setController(HarvestAccountCrudController::class)
                            ->setAction(Action::DETAIL)
                            ->setEntityId($account->getHarvestAccount()->getId())
                            ->generateUrl();
                        $formattedValue[] = sprintf(
                            '<a href="%s">%s</a>%s%s',
                            $url,
                            $account->getHarvestAccount()->getName(),
                            $account->getIsAdmin() ? '&nbsp;<span class="badge badge-boolean-true">admin</span>' : '',
                            !$account->getIsEnabled() ? '&nbsp;<span class="badge badge-boolean-false">disabled</span>' : '',
                        );
                    }

                    return implode('<br />', $formattedValue);
                }),
            IntegerField::new('forecastId')->hideOnForm(),
            EmailField::new('email')
                ->setFormTypeOptions(['disabled' => 'disabled']),
            DateTimeField::new('createdAt')->hideOnForm(),
            IntegerField::new('expires', 'Token expiration')
                ->hideOnForm()
                ->formatValue(function ($value): string {
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

                    foreach ($mapping as list($format, $toDisplay)) {
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
            BooleanField::new('isEnabled'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $impersonate = Action::new('impersonate', 'Impersonate')
            ->linkToUrl(function (User $entity) {
                return '/?_switch_user=' . $entity->getEmail();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $impersonate)
            ->add(Crud::PAGE_DETAIL, $impersonate)
            ->disable(Action::NEW)
        ;
    }
}
