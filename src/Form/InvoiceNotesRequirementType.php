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
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceNotesRequirementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('harvestClientId', ChoiceType::class, [
                'label' => 'Client',
                'attr' => ['class' => 'select2'],
                'choices' => $options['choices'],
                'help' => 'Choose here a client.',
            ])
            ->add('requirement', null, [
                'label' => 'Footnote requirement',
                'help' => 'Type here a string that must be contained in this client\'s invoices.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InvoiceNotesRequirement::class,
        ]);
        $resolver->setRequired('choices');
    }
}
