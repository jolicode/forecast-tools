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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientOverrideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'help' => 'Type here the name of your choice.',
            ])
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                $data = $event->getData();
                $choices = $options['enabledClients'];
                $help = 'Choose here a client name to cutomize.';

                if (null !== $data) {
                    $clientId = $data->getClientId();

                    if (null !== $clientId && !\in_array($clientId, $choices, true)) {
                        if (\in_array($clientId, $options['allClients'], true)) {
                            $clientName = array_keys($options['allClients'], $clientId, true)[0];
                            $choices[$clientName] = $clientId;
                        } else {
                            $choices['archived or removed client'] = $clientId;
                        }

                        $help .= '⚠ The currently selected client has been archived or removed.';
                    }
                }

                $form
                    ->add('clientId', ChoiceType::class, [
                        'label' => 'Client',
                        'attr' => ['class' => 'select2'],
                        'choices' => $choices,
                        'help' => $help,
                    ])
                ;
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ClientOverride::class,
        ]);
        $resolver->setRequired(['allClients', 'enabledClients']);
    }
}
