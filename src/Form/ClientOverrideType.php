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

use App\Entity\ClientOverride;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientOverrideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('clientId', ChoiceType::class, [
                'label' => 'Client',
                'attr' => ['class' => 'select2'],
                'choices' => $options['choices'],
                'help' => 'Choose here a client name to cutomize.',
            ])
            ->add('name', null, [
                'help' => 'Type here the name of your choice.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ClientOverride::class,
        ]);
        $resolver->setRequired('choices');
    }
}
