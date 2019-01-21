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
use App\Entity\PublicForecast;
use App\Repository\ForecastAccountRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicForecastType extends AbstractType
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
                'help' => 'Give here a name to this public forecast, it will help you recognize it afterwards.',
            ])
            ->add('forecastAccount', EntityType::class, [
                'class' => ForecastAccount::class,
                'query_builder' => function (ForecastAccountRepository $forecastAccountRepository) use ($options) {
                    return $forecastAccountRepository->createQueryBuilder('a')
                        ->join('a.users', 'u')
                        ->where('u.id = :userId')
                        ->orderBy('a.name', 'ASC')
                        ->setParameter('userId', $options['currentUser']->getId())
                    ;
                },
                'help' => 'Please choose a Forecast account. If you change of account after you have configured some options below, those will be reset.',
            ])
            ->add('clients', ChoiceType::class, [
                'choices' => [],
                'required' => false,
                'multiple' => true,
                'help' => 'Please choose here the clients to display in the forecast.',
            ])
            ->add('projects', ChoiceType::class, [
                'choices' => [],
                'required' => false,
                'multiple' => true,
                'help' => 'Please select here the projects to be displayed in the forecast. If you have also selected clients in the field above, please note that only projects matching these clients will be displayed.',
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
            'data_class' => PublicForecast::class,
        ]);
        $resolver->setRequired([
            'currentUser',
        ]);
    }

    protected function updateAccount($form, $account)
    {
        $projects = $this->buildProjects($account);
        $clients = $this->buildClients($account);

        $form->add('clients', ChoiceType::class, [
            'choices' => $clients,
            'required' => false,
            'multiple' => true,
            'help' => 'Please choose here the clients to display in the forecast.',
        ]);

        $form->add('projects', ChoiceType::class, [
            'choices' => $projects,
            'required' => false,
            'multiple' => true,
            'help' => 'Please select here the projects to be displayed in the forecast. If you have also selected clients in the field above, please note that only projects matching these clients will be displayed.',
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
}
