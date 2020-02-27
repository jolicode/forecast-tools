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

use App\Entity\InvoicingProcess;
use App\Repository\InvoicingProcessRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class InvoicingCreateType extends AbstractType
{
    private $invoicingProcessRepository;

    public function __construct(InvoicingProcessRepository $invoicingProcessRepository)
    {
        $this->invoicingProcessRepository = $invoicingProcessRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('billingPeriodStart', null, [
                'widget' => 'single_text',
                'help' => 'What is the start date of the period you intend to invoice for?',
            ])
            ->add('billingPeriodEnd', null, [
                'widget' => 'single_text',
                'help' => 'What is the end date of the period you intend to invoice for?',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Create this invoicing process',
                'attr' => ['class' => 'btn btn-primary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InvoicingProcess::class,
            'constraints' => [
                new Assert\Callback([$this, 'validateOverlappingDates']),
            ],
        ]);
    }

    public function validateOverlappingDates(InvoicingProcess $invoicingProcess, ExecutionContextInterface $context)
    {
        $conflicts = $this->invoicingProcessRepository->findOverlapping($invoicingProcess);

        if (\count($conflicts) > 0) {
            $context->buildViolation('There is already an invoicing process for this period')
                ->atPath('startDate')
                ->addViolation();
        }
    }
}
