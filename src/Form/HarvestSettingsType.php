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
use App\Entity\HarvestAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HarvestSettingsType extends AbstractType
{
    public function __construct(private readonly HarvestDataSelector $harvestDataSelector)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $enabledClients = $this->harvestDataSelector->getClientsForChoice(true);
        $allClients = $this->harvestDataSelector->getClientsForChoice(null);
        $builder
            ->add('doNotCleanupClientIds', ChoiceType::class, [
                'choices' => $this->harvestDataSelector->getClientsForChoice(true),
                'required' => false,
                'multiple' => true,
                'help' => 'Please select clients for which you do not want to perform the cleanup & archive operations.',
            ])
            ->add('doNotCheckTimesheetsFor', ChoiceType::class, [
                'choices' => $this->harvestDataSelector->getEnabledUsersForChoice(),
                'required' => false,
                'multiple' => true,
                'help' => 'Please select users for whom you wish to disable timesheets checks.',
            ])
            ->add('hideSkippedUsers', null, [
                'help' => 'Completely hide those users from the timesheets verification steps?',
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
            ])
            ->add('invoiceDueDelayRequirements', CollectionType::class, [
                'entry_type' => InvoiceDueDelayRequirementType::class,
                'entry_options' => [
                    'enabledChoices' => $enabledClients,
                    'allChoices' => $allClients,
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
                    'enabledChoices' => $enabledClients,
                    'allChoices' => $allClients,
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HarvestAccount::class,
        ]);
    }
}
