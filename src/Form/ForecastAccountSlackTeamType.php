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

use App\DataSelector\SlackDataSelector;
use App\Entity\ForecastAccountSlackTeam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForecastAccountSlackTeamType extends AbstractType
{
    public function __construct(private readonly SlackDataSelector $slackDataSelector)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $conversations = $this->slackDataSelector->getConversationsForChoice($event->getData()->getSlackTeam());
            $form
                ->add('channelId', ChoiceType::class, [
                    'choices' => $conversations,
                    'label' => 'Select a Channel',
                    'placeholder' => 'Do not post in this Slack team',
                    'required' => false,
                    'multiple' => false,
                    'help' => 'Please choose a channel to post to in this Slack team.',
                ]);
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $forecastAccountSlackTeam = $event->getData();

            if (null !== $forecastAccountSlackTeam->getChannelId()) {
                $conversations = array_flip($this->slackDataSelector->getConversationsForChoice($forecastAccountSlackTeam->getSlackTeam()));
                $forecastAccountSlackTeam->setChannel($conversations[$forecastAccountSlackTeam->getChannelId()]);
            } else {
                $forecastAccountSlackTeam->setChannel(null);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ForecastAccountSlackTeam::class,
        ]);
    }
}
