<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Plan;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ChoiceField, EmailField, PasswordField};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield EmailField::new('email');
        yield ChoiceField::new('plan')->setChoices(['Basic' => Plan::Basic, 'Premium' => Plan::Premium]);
        yield ChoiceField::new('roles')
            ->setChoices(['Librarian' => 'ROLE_LIBRARIAN', 'Admin' => 'ROLE_ADMIN'])
            ->allowMultipleChoices();
        yield PasswordField::new('password')->onlyWhenCreating();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityInstance->password = $this->hasher->hashPassword($entityInstance, $entityInstance->password);
        parent::persistEntity($entityManager, $entityInstance);
    }
}
