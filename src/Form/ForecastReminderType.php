<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\DataSelector\ForecastDataSelector;
use App\Entity\ForecastReminder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForecastReminderType extends AbstractType
{
    public function __construct(private ForecastDataSelector $forecastDataSelector)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (true === $options['hasSlackTeams']) {
            $enabledClients = $this->forecastDataSelector->getClientsForChoice(true);
            $allClients = $this->forecastDataSelector->getClientsForChoice(null);
            $enabledProjects = $this->forecastDataSelector->getProjectsForChoice(true);
            $allProjects = $this->forecastDataSelector->getProjectsForChoice(null);
            $users = $this->forecastDataSelector->getPeopleForChoice(true);
        } else {
            $enabledClients = [];
            $allClients = [];
            $enabledProjects = [];
            $allProjects = [];
            $users = [];
        }

        $builder
            ->add('cronExpression', null, [
                'help' => 'Write here a cron-style execution expression, which will define when the reminder must be sent.',
            ])
            ->add('defaultActivityName', null, [
                'help' => 'Type here the text to display as the activity name when a user does not have any task assigned.',
            ])
            ->add('timeOffActivityName', null, [
                'help' => 'Type here the text to display when a user is assigned one of the "time off projects".',
            ])
            ->add('timeOffProjects', ChoiceType::class, [
                'choices' => $enabledProjects,
                'required' => false,
                'multiple' => true,
                'help' => 'Please choose here time off projects. They will display as configured in the "Time-off activity name" field.',
            ])
            ->add('clientOverrides', CollectionType::class, [
                'entry_type' => ClientOverrideType::class,
                'entry_options' => [
                    'enabledClients' => $enabledClients,
                    'allClients' => $allClients,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'help' => 'This section helps you customize the way client names are displayed.',
            ])
            ->add('projectOverrides', CollectionType::class, [
                'entry_type' => ProjectOverrideType::class,
                'entry_options' => [
                    'enabledProjects' => $enabledProjects,
                    'allProjects' => $allProjects,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'help' => 'This section helps you customize the way project names are displayed.',
            ])
            ->add('onlyUsers', ChoiceType::class, [
                'choices' => $users,
                'required' => false,
                'multiple' => true,
                'help' => 'Please choose here the users that you want to limit the reminder to. Let this field empty to include all users.',
            ])
            ->add('exceptUsers', ChoiceType::class, [
                'choices' => $users,
                'required' => false,
                'multiple' => true,
                'help' => 'Please choose here the users that you want <strong>exclude</strong> from the reminder. Let this field empty to include all users.',
            ])
        ;

        if (true === $options['hasSlackTeams']) {
            $builder
                ->add('forecastAccount', ForecastAccountForReminderType::class)
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ForecastReminder::class,
        ]);
        $resolver->setRequired([
            'hasSlackTeams',
        ]);
    }
}
