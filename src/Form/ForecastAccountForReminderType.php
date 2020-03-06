<?php

namespace App\Form;

use App\Entity\ForecastAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForecastAccountForReminderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('forecastAccountSlackTeams', CollectionType::class, [
                'entry_type' => ForecastAccountSlackTeamType::class,
                'entry_options' => [],
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => true,
                'help' => 'Select the channels to post to.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ForecastAccount::class,
        ]);
    }
}
