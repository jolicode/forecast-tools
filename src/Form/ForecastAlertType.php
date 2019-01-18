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

use App\Entity\ForecastAlert;
use App\Repository\ForecastAccountRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForecastAlertType extends AbstractType
{
    private $forecastAccountRepository;

    public function __construct(ForecastAccountRepository $forecastAccountRepository)
    {
        $this->forecastAccountRepository = $forecastAccountRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'help' => 'Give here a name to this alert, it will help you recognize it afterwards.',
            ])
            ->add('forecastAccount', null, [
                'help' => 'Please choose a Forecast account. If you change of account after you have configured some options, those will be reset.',
            ])
            ->add('cronExpression', null, [
                'help' => 'Write here a cron-style execution expression, which will define when the alert must be sent.',
            ])
            ->add('slackWebHook', null, [
                'help' => 'Type here a Slack web hook, in the form <code>TXXXXXXXX/BXXXXXXXXX/PXXXXXXXXXXXXXXXXXXXXXX</code>.',
            ])
            ->add('defaultActivityName', null, [
                'help' => 'Type here the text to display as the activity name when a user does not have any task assigned.',
            ])
            ->add('timeOffActivityName', null, [
                'help' => 'Type here the text to display when a user is assigned one of the "time off projects".',
            ])
            ->add('timeOffProjects', ChoiceType::class, [
                'choices' => [],
                'required' => false,
                'multiple' => true,
                'help' => 'Please choose here time off projects.',
            ])
            ->add('clientOverrides', CollectionType::class, [
                'entry_type' => ClientOverrideType::class,
                'entry_options' => [
                    'choices' => [],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'help' => 'This section helps you customize the way client names are displayed.',
            ])
            ->add('projectOverrides', CollectionType::class, [
                'entry_type' => ProjectOverrideType::class,
                'entry_options' => [
                    'choices' => [],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'help' => 'This section helps you customize the way project names are displayed.',
            ])
            ->add('onlyUsers', ChoiceType::class, [
                'choices' => [],
                'required' => false,
                'multiple' => true,
                'help' => 'Please choose here the users that you want to limit the alert to. Let the field empty to include all users.',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            if ($event->getData()) {
                $account = $event->getData()->getForecastAccount();

                if ($account) {
                    $this->updateAccount($event->getForm(), $account);
                }
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            if ($event->getData()) {
                $account = $this->forecastAccountRepository->findOneById($event->getData()['forecastAccount']);

                if ($account) {
                    $this->updateAccount($event->getForm(), $account);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ForecastAlert::class,
        ]);
    }

    protected function updateAccount($form, $account)
    {
        $projects = $this->buildProjects($account);
        $clients = $this->buildClients($account);
        $users = $this->buildUsers($account);

        $form->add('projectOverrides', CollectionType::class, [
            'entry_type' => ProjectOverrideType::class,
            'entry_options' => [
                'choices' => $projects,
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'help' => 'This section helps you customize the way project names are displayed.',
        ]);

        $form->add('clientOverrides', CollectionType::class, [
            'entry_type' => ClientOverrideType::class,
            'entry_options' => [
                'choices' => $clients,
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'help' => 'This section helps you customize the way client names are displayed.',
        ]);

        $form->add('timeOffProjects', ChoiceType::class, [
            'choices' => $projects,
            'required' => false,
            'multiple' => true,
            'help' => 'Please choose here time off projects.',
        ]);

        $form->add('onlyUsers', ChoiceType::class, [
            'choices' => $users,
            'required' => false,
            'multiple' => true,
            'help' => 'Please choose here the users that you want to limit the alert to. Let the field empty to include all users.',
        ]);
    }

    protected function buildClients($account): array
    {
        $choices = [];
        $client = \JoliCode\Forecast\ClientFactory::create(
            $account->getAccessToken(),
            $account->getForecastId()
        );
        $clients = $client->listClients()->getClients();

        foreach ($clients as $clientObject) {
            $choices[$clientObject->getName()] = $clientObject->getId();
        }

        return $choices;
    }

    protected function buildProjects($account): array
    {
        $choices = [];
        $client = \JoliCode\Forecast\ClientFactory::create(
            $account->getAccessToken(),
            $account->getForecastId()
        );
        $projects = $client->listProjects()->getProjects();

        foreach ($projects as $project) {
            $choices[$project->getName()] = $project->getId();
        }

        return $choices;
    }

    protected function buildUsers($account): array
    {
        $choices = [];
        $client = \JoliCode\Forecast\ClientFactory::create(
            $account->getAccessToken(),
            $account->getForecastId()
        );
        $users = $client->listPeople()->getPeople();

        foreach ($users as $user) {
            $choices[$user->getFirstName() . ' ' . $user->getLastName()] = $user->getId();
        }

        return $choices;
    }
}
