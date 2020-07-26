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
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('defaultForecastAccount', EntityType::class, [
                'class' => ForecastAccount::class,
                'label' => 'Default account on login',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('fa')
                        ->leftJoin('fa.userForecastAccounts', 'ufa')
                        ->andWhere('ufa.user = :user')
                        ->orderBy('fa.name', 'ASC')
                        ->setParameter('user', $options['data'])
                    ;
                },
                'help' => 'Choose here the Forecast account that you want to be connected to when authenticating.',
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
            'data_class' => User::class,
        ]);
    }
}
