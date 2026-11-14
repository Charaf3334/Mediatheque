<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Médiathèque');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(BookCrudController::class, 'Books', 'fas fa-book');
        yield MenuItem::linkTo(CopyCrudController::class, 'Copies', 'fas fa-copy');
        yield MenuItem::linkTo(LoanCrudController::class, 'Loans', 'fas fa-right-left');
        yield MenuItem::linkTo(ReservationCrudController::class, 'Reservations', 'fas fa-clock');
        yield MenuItem::linkTo(UserCrudController::class, 'Members', 'fas fa-users');
        yield MenuItem::linkToUrl('Public site', 'fas fa-globe', '/');
    }
}
