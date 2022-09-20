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

use App\Entity\ProjectOverride;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectOverrideType extends AbstractType
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
                $choices = $options['enabledProjects'];
                $help = 'Choose here a project name to cutomize.';

                if (null !== $data) {
                    $projectId = $data->getProjectId();

                    if (null !== $projectId && !\in_array($projectId, $choices, true)) {
                        if (\in_array($projectId, $options['allProjects'], true)) {
                            $projectName = array_keys($options['allProjects'], $projectId, true)[0];
                            $choices[$projectName] = $projectId;
                        } else {
                            $choices['archived or removed project'] = $projectId;
                        }

                        $help .= '⚠ The currently selected project has been archived or removed.';
                    }
                }

                $form
                    ->add('projectId', ChoiceType::class, [
                        'label' => 'Project',
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
            'data_class' => ProjectOverride::class,
        ]);
        $resolver->setRequired(['allProjects', 'enabledProjects']);
    }
}
