<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\TaskStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Название',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'attr' => ['rows' => 5],
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Срок выполнения',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('maxScore', IntegerType::class, [
                'label' => 'Максимальный балл',
            ])
            ->add('status', EnumType::class, [
                'label' => 'Статус',
                'class' => TaskStatus::class,
                'choice_label' => fn (TaskStatus $status) => $status->label(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
