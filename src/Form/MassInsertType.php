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
use App\DataSelector\HarvestDataSelector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MassInsertType extends AbstractType
{
    private HarvestDataSelector $harvestDataSelector;

    private ForecastDataSelector $forecastDataSelector;

    public function __construct(ForecastDataSelector $forecastDataSelector, HarvestDataSelector $harvestDataSelector)
    {
        $this->forecastDataSelector = $forecastDataSelector;
        $this->harvestDataSelector = $harvestDataSelector;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $people = $this->forecastDataSelector->getEnabledPeopleForChoice();
        $projects = $this->forecastDataSelector->getEnabledProjectsForChoice();
        $builder
            ->add('project', ChoiceType::class, [
                'choices' => $projects,
                'required' => true,
                'help' => 'Choose here the project to assign the users to.',
                'attr' => ['class' => 'select2'],
                'constraints' => [
                    new Choice([
                        'choices' => $projects,
                        'message' => 'Please choose a valid project.',
                    ]),
                ],
            ])
            ->add('date', DateType::class, [
                'help' => 'Choose the date for this assignment.',
                'required' => true,
                'widget' => 'single_text',
                'constraints' => [new Type([
                    'type' => \DateTimeInterface::class,
                ])],
                'format' => 'yyyy-MM-dd',
            ])
            ->add('duration', IntegerType::class, [
                'data' => 8,
                'help' => 'Choose the duration for this assignment, in hours (from 1 to 8)',
                'required' => true,
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 8,
                        'notInRangeMessage' => 'The duration must be between {{ min }} and {{ max }}.',
                    ]),
                ],
            ])
            ->add('people', ChoiceType::class, [
                'choices' => $people,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'data' => $people,
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'help' => 'Choose the people that you want to assign to this project.',
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => 'Please choose at least one person to assign to this project.',
                    ]),
                ],
            ])
            ->add('forecast', CheckboxType::class, [
                'help' => 'Add this entry in Forecast?',
                'data' => true,
                'required' => false,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
            ])
            ->add('harvest', CheckboxType::class, [
                'help' => 'Add this entry in Harvest?',
                'data' => true,
                'required' => false,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
            ])
            ->add('comment', TextareaType::class, [
                'help' => 'Add an optionnal comment for this assignment (for example, the name of the public holiday, the name of a conference, etc.).',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Insert these entries',
                'attr' => ['class' => 'btn btn-primary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [
                new Callback([$this, 'validateForecastOrHarvest']),
                new Callback([$this, 'validatePreexisting']),
            ],
        ]);
    }

    public function validateForecastOrHarvest($object, ExecutionContextInterface $context, $payload)
    {
        if (!$object['forecast'] && !$object['harvest']) {
            $context->buildViolation('Please choose at least one platform')
                ->atPath('[forecast]')
                ->addViolation()
            ;
        }
    }

    public function validatePreexisting($object, ExecutionContextInterface $context, $payload)
    {
        if ($object['forecast']) {
            $assignements = $this->forecastDataSelector->disableCacheForNextRequestOnly()->getAssignments(
                $object['date'],
                $object['date'],
                ['project_id' => $object['project']]
            );

            foreach ($assignements as $assignement) {
                if ($key = array_search($assignement->getPersonId(), $object['people'], true)) {
                    $context->buildViolation(sprintf('%s already has an assignment for this project on that day in Forecast.', $key))
                        ->atPath('[people]')
                        ->addViolation();
                }
            }
        }

        if ($object['harvest']) {
            $projects = $this->forecastDataSelector->getProjectsById(null, true);

            if (!isset($projects[$object['project']])) {
                $context->buildViolation('Could not find this project in Forecast.')
                    ->atPath('[project]')
                    ->addViolation();
            } else {
                $timeEntries = $this->harvestDataSelector->disableCacheForNextRequestOnly()->getTimeEntries(
                    $object['date'],
                    $object['date'],
                    ['project_id' => $projects[$object['project']]->getHarvestId()]
                );
                $forecastUsersByHarvestId = $this->forecastDataSelector->getPeopleById('getHarvestUserId');

                foreach ($timeEntries as $timeEntry) {
                    if (isset($forecastUsersByHarvestId[$timeEntry->getUser()->getId()])) {
                        $forecastUser = $forecastUsersByHarvestId[$timeEntry->getUser()->getId()];

                        if ($key = array_search($forecastUser->getId(), $object['people'], true)) {
                            $context->buildViolation(sprintf('%s already has an assignment for this project on that day in Harvest.', $key))
                                ->atPath('[people]')
                                ->addViolation();
                        }
                    }
                }
            }
        }
    }
}
