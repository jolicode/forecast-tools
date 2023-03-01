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

use App\Entity\SlackTeam;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class SlackTeamCrudController extends AbstractCrudController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return SlackTeam::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Slack teams')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            CollectionField::new('forecastAccountSlackTeams', 'Forecast organizations')
                ->formatValue(function ($value, $entity): string {
                    $formattedValue = [];
                    $forecastAccountSlackTeams = $entity->getForecastAccountSlackTeams();

                    foreach ($forecastAccountSlackTeams as $forecastAccountSlackTeam) {
                        $url = $this->adminUrlGenerator
                            ->unsetAll()
                            ->setController(ForecastAccountCrudController::class)
                            ->setAction(Action::DETAIL)
                            ->setEntityId($forecastAccountSlackTeam->getForecastAccount()->getId())
                            ->generateUrl();
                        $formattedValue[] = sprintf(
                            '<a href="%s">%s</a>',
                            $url,
                            $forecastAccountSlackTeam->getForecastAccount()->getName(),
                        );
                    }

                    return implode(', ', $formattedValue);
                }),
            BooleanField::new('accessToken', 'usable')
                ->formatValue(fn ($value): bool => '' !== $value)->setCustomOptions([
                    'renderAsSwitch' => false,
                ]),
            TextField::new('teamName'),
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
