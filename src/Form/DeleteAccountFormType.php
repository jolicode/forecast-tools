<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\EqualTo;

class DeleteAccountFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('yolo', null, [
                'help' => 'Please type here "YOLO" to get your account and all your data deleted.',
                'label_attr' => [ 'class' => 'd-none' ],
                'constraints' => [
                    new EqualTo([
                        'value' => 'YOLO',
                        'message' => 'Please type "YOLO" to confirm the account deletion'
                    ]),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Understood, delete my account',
                'attr' => ['class' => 'btn btn-danger'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
