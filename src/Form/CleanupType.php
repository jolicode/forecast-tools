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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CleanupType extends AbstractType
{
    public function __construct(
        private readonly HarvestDataSelector $harvestDataSelector
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $outdatedProjects = $this->harvestDataSelector->getOutdatedProjects($options['harvestAccount']->doNotCleanupClientIds ?? []);
        $outdatedClients = $this->harvestDataSelector->getOutdatedClients();
        $outdatedProjectLabels = [];
        $outdatedClientsLabels = [];

        foreach ($outdatedProjects as $label => $outdatedProject) {
            $outdatedProjectLabels[$label] = $outdatedProject['project']->getId();
        }

        foreach ($outdatedClients as $label => $outdatedClient) {
            $outdatedClientsLabels[$label] = $outdatedClient->getId();
        }

        $builder
            ->add('project', ChoiceType::class, [
                'choices' => $outdatedProjectLabels,
                'data' => $outdatedProjectLabels,
                'choice_attr' => $outdatedProjects,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'help' => 'Choose the outdated projects that you wish to archive.',
            ])
            ->add('client', ChoiceType::class, [
                'choices' => $outdatedClientsLabels,
                'data' => $outdatedClientsLabels,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'help' => 'Choose the outdated clients that you wish to archive.',
            ])
            ->add('archive', SubmitType::class, [
                'label' => 'Archive the selected items',
                'attr' => ['class' => 'btn btn-primary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'harvestAccount' => null,
            ])
            ->setAllowedTypes('harvestAccount', HarvestAccount::class)
        ;
    }
}
