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

use App\Entity\HarvestAccount;
use App\Invoicing\DataSelector\HarvestDataSelector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HarvestSettingsType extends AbstractType
{
    private $harvestDataSelector;

    public function __construct(HarvestDataSelector $harvestDataSelector)
    {
        $this->harvestDataSelector = $harvestDataSelector;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('doNotCheckTimesheetsFor', ChoiceType::class, [
                'choices' => $this->harvestDataSelector->getEnabledUsersForChoice(),
                'required' => false,
                'multiple' => true,
                'help' => 'Please select users for whom you wish to disable timesheets checks.',
            ])
            ->add('hideSkippedUsers', null, [
                'help' => 'Completely hide those users from the timesheets verification steps?',
            ])
            ->add('invoiceDueDelayRequirements', CollectionType::class, [
                'entry_type' => InvoiceDueDelayRequirementType::class,
                'entry_options' => [
                    'choices' => $this->harvestDataSelector->getEnabledClientsForChoice(),
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
                'help' => 'This section allow to create, for each client, invoice "due delay" requirements.',
            ])
            ->add('invoiceNotesRequirements', CollectionType::class, [
                'entry_type' => InvoiceNotesRequirementType::class,
                'entry_options' => [
                    'choices' => $this->harvestDataSelector->getEnabledClientsForChoice(),
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
                'help' => 'This section allow to create, for each client, invoice footnotes requirements.',
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
