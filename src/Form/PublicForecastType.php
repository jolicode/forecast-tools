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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicForecastType extends AbstractType
{
    public function __construct(private ForecastDataSelector $forecastDataSelector)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'help' => 'Give here a name to this public forecast, it will help you recognize it afterwards.',
            ])
        ;

        $formModifier = function (FormInterface $form, $items, $enabledItems, $allItems, $baseHelp, $fieldName) {
            $hasError = false;

            foreach ($items as $itemId) {
                if (!\in_array($itemId, $enabledItems, true)) {
                    if (\in_array($itemId, $allItems, true)) {
                        $clientName = array_keys($allItems, $itemId, true)[0];
                        $enabledItems[$clientName] = $itemId;
                    } else {
                        $enabledItems['archived or removed project'] = $itemId;
                    }

                    ksort($enabledItems);
                    $hasError = true;
                }
            }

            if ($hasError) {
                $baseHelp .= sprintf(' ⚠ One of the currently selected %s has been archived or removed.', $fieldName);
            }

            $form
                ->add($fieldName, ChoiceType::class, [
                    'choices' => $enabledItems,
                    'required' => false,
                    'multiple' => true,
                    'help' => $baseHelp,
                ])
            ;
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $form = $event->getForm();
                $data = $event->getData();
                $formModifier(
                    $form,
                    $data->getClients(),
                    $this->forecastDataSelector->getClientsForChoice(true),
                    $this->forecastDataSelector->getClientsForChoice(null),
                    'Please choose here the clients to display in the forecast.',
                    'clients'
                );
                $formModifier(
                    $form,
                    $data->getProjects(),
                    $this->forecastDataSelector->getProjectsForChoice(true),
                    $this->forecastDataSelector->getProjectsForChoice(null),
                    'You can select here the projects to be displayed in the forecast. If you do not select a project, all the projects for the selected client will be displayed. If you have also selected clients in the field above, please note that only projects matching these clients will be displayed.',
                    'projects'
                );
                $formModifier(
                    $form,
                    $data->getPeople(),
                    $this->forecastDataSelector->getPeopleForChoice(true),
                    $this->forecastDataSelector->getPeopleForChoice(null),
                    'You can filter the public forecast for one or more people, if you wish to.',
                    'people'
                );
                $formModifier(
                    $form,
                    $data->getPlaceholders(),
                    $this->forecastDataSelector->getPlaceholderForChoice(true),
                    $this->forecastDataSelector->getPlaceholderForChoice(null),
                    'You can filter the public forecast for one or more placeholders, if you wish to.',
                    'placeholders'
                );
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PublicForecast::class,
        ]);
    }
}
