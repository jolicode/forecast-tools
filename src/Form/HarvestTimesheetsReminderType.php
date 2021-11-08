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

use App\DataSelector\HarvestDataSelector;
use App\Entity\ForecastAccountSlackTeam;
use App\Entity\HarvestAccount;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HarvestTimesheetsReminderType extends AbstractType
{
    private $harvestDataSelector;

    public function __construct(HarvestDataSelector $harvestDataSelector)
    {
        $this->harvestDataSelector = $harvestDataSelector;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('doNotSendTimesheetReminderFor', ChoiceType::class, [
                'choices' => $this->harvestDataSelector->getEnabledUsersForChoice(),
                'required' => false,
                'multiple' => true,
                'help' => 'Please select users for whom you wish to ignore the timesheet reminders.',
            ])
            ->add('timesheetReminderSlackTeam', EntityType::class, [
                'class' => ForecastAccountSlackTeam::class,
                'query_builder' => function (EntityRepository $er) use ($options): QueryBuilder {
                    return $er->createQueryBuilder('fast')
                        ->leftJoin('fast.slackTeam', 'st')
                        ->andWhere('fast.forecastAccount = :forecastAccount')
                        ->setParameter('forecastAccount', $options['data']->getForecastAccount())
                        ->orderBy('st.teamName', 'ASC')
                    ;
                },
                'choice_label' => 'slackTeam.teamName',
                'help' => 'Please choose a Slack team to send the notifications to. Please note that only the users having the exact same email address in Harvest and in this Slack organization will receive notifications.',
                'placeholder' => 'Do not send the Harvest timesheets reminder',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save settings',
                'attr' => ['class' => 'btn btn-primary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => HarvestAccount::class,
        ]);
    }
}
