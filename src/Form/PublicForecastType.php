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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicForecastType extends AbstractType
{
    private $clients = [];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $clients = $this->buildClients($options['forecastAccount']);

        $builder
            ->add('name', null, [
                'help' => 'Give here a name to this public forecast, it will help you recognize it afterwards.',
            ])
            ->add('clients', ChoiceType::class, [
                'choices' => $clients,
                'required' => false,
                'multiple' => true,
                'help' => 'Please choose here the clients to display in the forecast.',
            ])
            ->add('projects', ChoiceType::class, [
                'choices' => $this->buildProjects($options['forecastAccount']),
                'required' => false,
                'multiple' => true,
                'help' => 'Please select here the projects to be displayed in the forecast. If you have also selected clients in the field above, please note that only projects matching these clients will be displayed.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PublicForecast::class,
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
}
