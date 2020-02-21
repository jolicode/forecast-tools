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

use App\Entity\ForecastAccount;
use App\Entity\ForecastReminder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForecastReminderType extends AbstractType
{
    private $clients = [];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (\count($options['forecastAccount']->getSlackChannels()) > 0) {
            $clients = $this->buildClients($options['forecastAccount']);
            $projects = $this->buildProjects($options['forecastAccount']);
            $users = $this->buildUsers($options['forecastAccount']);
        } else {
            $clients = [];
            $projects = [];
            $users = [];
        }

        $builder
            ->add('isEnabled', null, [
                'help' => 'You can enable or mute this reminder. If muted, no notification will be sent to Slack.',
            ])
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
                'choices' => $projects,
                'required' => false,
                'multiple' => true,
                'help' => 'Please choose here time off projects. They will display as configured in the "Time-off activity name" field.',
            ])
            ->add('clientOverrides', CollectionType::class, [
                'entry_type' => ClientOverrideType::class,
                'entry_options' => [
                    'choices' => $clients,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'help' => 'This section helps you customize the way client names are displayed.',
            ])
            ->add('projectOverrides', CollectionType::class, [
                'entry_type' => ProjectOverrideType::class,
                'entry_options' => [
                    'choices' => $projects,
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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ForecastReminder::class,
        ]);
        $resolver->setRequired([
            'forecastAccount',
        ]);
    }

    protected function buildClients(ForecastAccount $forecastAccount): array
    {
        $choices = [];
        $client = \JoliCode\Forecast\ClientFactory::create(
            $forecastAccount->getAccessToken(),
            $forecastAccount->getForecastId()
        );
        $clients = $client->listClients()->getClients();

        foreach ($clients as $clientObject) {
            if (!$clientObject->getArchived()) {
                $choices[$clientObject->getName()] = $clientObject->getId();
            }

            $this->clients[$clientObject->getId()] = $clientObject;
        }

        ksort($choices);

        return $choices;
    }

    protected function buildProjects(ForecastAccount $forecastAccount): array
    {
        $choices = [];
        $client = \JoliCode\Forecast\ClientFactory::create(
            $forecastAccount->getAccessToken(),
            $forecastAccount->getForecastId()
        );
        $projects = $client->listProjects()->getProjects();

        foreach ($projects as $project) {
            if (!$project->getArchived()) {
                if (isset($this->clients[$project->getClientId()])) {
                    $key = sprintf('%s - %s', $this->clients[$project->getClientId()]->getName(), $project->getName());
                } else {
                    $key = $project->getName();
                }

                $choices[$key] = $project->getId();
            }
        }

        ksort($choices);

        return $choices;
    }

    protected function buildUsers(ForecastAccount $forecastAccount): array
    {
        $choices = [];
        $client = \JoliCode\Forecast\ClientFactory::create(
            $forecastAccount->getAccessToken(),
            $forecastAccount->getForecastId()
        );
        $users = $client->listPeople()->getPeople();

        foreach ($users as $user) {
            if (!$user->getArchived()) {
                $choices[$user->getFirstName() . ' ' . $user->getLastName()] = $user->getId();
            }
        }

        ksort($choices);

        return $choices;
    }
}
