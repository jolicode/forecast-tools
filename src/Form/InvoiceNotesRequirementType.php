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

use App\Entity\InvoiceNotesRequirement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceNotesRequirementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('requirement', null, [
                'label' => 'Footnote requirement',
                'help' => 'Type here a string that must be contained in this client\'s invoices.',
            ])
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                $data = $event->getData();
                $choices = $options['enabledChoices'];
                $help = 'Choose here a client.';

                if (null !== $data) {
                    $clientId = $data->getHarvestClientId();

                    if (null !== $clientId && !\in_array($clientId, $choices, true)) {
                        if (\in_array($clientId, $options['allChoices'], true)) {
                            $clientName = array_keys($options['allChoices'], $clientId, true)[0];
                            $choices[$clientName] = $clientId;
                        } else {
                            $choices['archived or removed client'] = $clientId;
                        }

                        $help .= '⚠ The currently selected client has been archived or removed.';
                    }
                }

                $form
                    ->add('harvestClientId', ChoiceType::class, [
                        'label' => 'Client',
                        'attr' => ['class' => 'select2'],
                        'choices' => $choices,
                        'help' => $help,
                    ])
                ;
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceNotesRequirement::class,
        ]);
        $resolver->setRequired(['enabledChoices', 'allChoices']);
    }
}
