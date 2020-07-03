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
use App\Entity\PublicForecast;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicForecastType extends AbstractType
{
    private $forecastDataSelector;

    public function __construct(ForecastDataSelector $forecastDataSelector)
    {
        $this->forecastDataSelector = $forecastDataSelector;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'help' => 'Give here a name to this public forecast, it will help you recognize it afterwards.',
            ])
            ->add('clients', ChoiceType::class, [
                'choices' => $this->forecastDataSelector->getEnabledClientsForChoice(),
                'required' => false,
                'multiple' => true,
                'help' => 'Please choose here the clients to display in the forecast.',
            ])
            ->add('projects', ChoiceType::class, [
                'choices' => $this->forecastDataSelector->getEnabledProjectsForChoice(),
                'required' => false,
                'multiple' => true,
                'help' => 'You can select here the projects to be displayed in the forecast. If you do not select a project, all the projects for the selected client will be displayed. If you have also selected clients in the field above, please note that only projects matching these clients will be displayed.',
            ])
            ->add('people', ChoiceType::class, [
                'choices' => $this->forecastDataSelector->getEnabledPeopleForChoice(),
                'required' => false,
                'multiple' => true,
                'help' => 'You can filter the public forecast for one or more people, if you wish to.',
            ])
            ->add('placeholders', ChoiceType::class, [
                'choices' => $this->forecastDataSelector->getEnabledPlaceholderForChoice(),
                'required' => false,
                'multiple' => true,
                'help' => 'You can filter the public forecast for one or more placeholder, if you wish to.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PublicForecast::class,
        ]);
    }
}
