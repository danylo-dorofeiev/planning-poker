<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('key', TextType::class, [
                'label' => 'Key',
                'attr' => [
                    'placeholder' => 'Key',
                ],
                'disabled' => !$options['editable'],
            ])
            ->add('title', TextType::class, [
                'label' => 'Titel',
                'attr' => [
                    'placeholder' => 'Title',
                ],
                'disabled' => !$options['editable'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Beschreibung',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Beschreibung',
                ],
                'disabled' => !$options['editable'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'editable' => true,
        ]);

        $resolver->setAllowedTypes('editable', ['bool']);
    }
}
